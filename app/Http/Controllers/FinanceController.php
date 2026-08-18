<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeStructureRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\FeeInstallment;
use App\Models\FeeStructure;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\StudentPayment;
use App\Models\User;
use App\Models\ManualInsolvable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    // ── TABLEAU DE BORD FINANCIER ─────────────────────────────────────────
    public function index(Request $request)
    {
        $activeYear       = AcademicYear::active();
        $selectedYearId   = $request->input('year_id', $activeYear?->id);
        $selectedSectionId = $request->input('section_id');
        $selectedYear     = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : null;
        $selectedSection  = $selectedSectionId
            ? Section::find($selectedSectionId)
            : null;

        $years    = AcademicYear::orderByDesc('start_date')->get();
        $sections = Section::orderBy('id')->get();

        // Classes de l'année sélectionnée avec leurs structures de frais
        $classes = collect();
        if ($selectedYear) {
            $query = ClassGroup::where('academic_year_id', $selectedYear->id)
                ->with([
                    'level.section',
                    'feeStructures.installments',
                    'studentEnrollments' => fn($q) =>
                        $q->where('status', 'active'),
                ]);

            if ($selectedSectionId) {
                $query->whereHas('level.section', fn($q) =>
                    $q->where('id', $selectedSectionId)
                );
            }

            $classes = $query->orderBy('name')->get();

            foreach ($classes as $class) {
                foreach ($class->studentEnrollments as $enrollment) {
                    $this->cleanupOverpaidPayments($enrollment);
                }
            }
        }

        // Stats globales et paiements récents
        $studentCount = $classes->sum(fn($class) => $class->studentEnrollments->count());
        $totalExpected = $classes->sum(function ($class) {
            $feeTotal = $class->feeStructures->first()?->installments->sum('amount') ?? 0;
            return $feeTotal * $class->studentEnrollments->count();
        });

        $paymentQuery = StudentPayment::visible()
            ->whereHas('studentEnrollment', function ($query) use ($selectedYearId, $selectedSectionId) {
                $query->where('academic_year_id', $selectedYearId)
                      ->where('status', 'active');

                if ($selectedSectionId) {
                    $query->whereHas('classGroup.level.section', fn($q) =>
                        $q->where('id', $selectedSectionId)
                    );
                }
            });

        $totalCollected = (int) $paymentQuery->sum(DB::raw('COALESCE(amount_paid, 0) + COALESCE(scholarship_amount, 0)'));
        $totalScholarships = (int) $paymentQuery->sum('scholarship_amount');
        $totalOutstanding = max(0, $totalExpected - $totalCollected);
        $rate = $totalExpected > 0
            ? round(($totalCollected / $totalExpected) * 100)
            : 0;

        $stats = [
            'expected'     => $totalExpected,
            'collected'    => $totalCollected,
            'scholarships' => $totalScholarships,
            'outstanding'  => $totalOutstanding,
            'rate'         => $rate,
            'students'     => $studentCount,
        ];

        $recentPayments = $paymentQuery->with([
                'studentEnrollment.student',
                'studentEnrollment.classGroup.level.section',
                'feeInstallment',
            ])
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('finances.index', compact(
            'selectedYear', 'selectedSection', 'selectedSectionId',
            'years', 'sections', 'classes', 'stats', 'recentPayments', 'activeYear'
        ));
    }

    // ── CONFIGURATION DES FRAIS D'UNE CLASSE ─────────────────────────────
    public function configureFees(ClassGroup $classGroup)
    {
        $classGroup->load([
            'level.section', 'academicYear',
            'feeStructures.installments',
        ]);

        $feeStructure = $classGroup->feeStructures->first();

        return view('finances.fees',
            compact('classGroup', 'feeStructure'));
    }

    // ── ENREGISTREMENT DES FRAIS ──────────────────────────────────────────
    public function saveFees(StoreFeeStructureRequest $request,
                             ClassGroup $classGroup)
    {
        if ($classGroup->academicYear->isClosed()) {
            return back()->with('error',
                'Année clôturée — modification impossible.');
        }

        // Créer ou récupérer la structure de frais
        $feeStructure = FeeStructure::firstOrCreate([
            'academic_year_id' => $classGroup->academic_year_id,
            'class_group_id'   => $classGroup->id,
        ], ['total_amount' => 0]);

        // Supprimer les anciennes tranches sans paiements
        $feeStructure->installments()
            ->whereDoesntHave('payments')
            ->delete();

        $total = 0;
        foreach ($request->input('installments', []) as $i => $item) {
            if (empty($item['label']) || !isset($item['amount'])) continue;

            FeeInstallment::updateOrCreate(
                [
                    'fee_structure_id'   => $feeStructure->id,
                    'installment_number' => $i + 1,
                ],
                [
                    'label'          => $item['label'],
                    'amount'         => $item['amount'],
                    'due_date_start' => $item['due_date_start'] ?: null,
                    'due_date_end'   => $item['due_date_end']   ?: null,
                ]
            );
            $total += $item['amount'];
        }

        $feeStructure->update(['total_amount' => $total]);
        AuditLog::log('fees_configured', $feeStructure);

        return redirect()
            ->route('finances.index')
            ->with('success',
                "Frais de {$classGroup->full_name} configurés. "
                . "Total : " . number_format($total) . " FCFA");
    }

    // ── COMPTE FINANCIER D'UN ÉLÈVE ───────────────────────────────────────
    public function studentAccount(StudentEnrollment $enrollment)
    {
        $enrollment->load([
            'student',
            'classGroup.level.section',
            'classGroup.feeStructures.installments',
            'academicYear',
        ]);

        $this->cleanupOverpaidPayments($enrollment);

        $feeStructure = $enrollment->classGroup()->with('feeStructures.installments')->first()?->feeStructures->first();

        // Calculer le statut de chaque tranche
        $installments = collect();
        if ($feeStructure) {
            foreach ($feeStructure->installments->sortBy('installment_number')
                as $inst) {
                $paid = StudentPayment::where('student_enrollment_id', $enrollment->id)
                    ->where('fee_installment_id', $inst->id)
                    ->sum('amount_paid')
                    + StudentPayment::where('student_enrollment_id', $enrollment->id)
                    ->where('fee_installment_id', $inst->id)
                    ->sum('scholarship_amount');
                $remaining  = max(0, $inst->amount - $paid);
                $status     = $paid <= 0 ? 'unpaid'
                    : ($paid >= $inst->amount ? 'paid' : 'partial');

                $installments->push([
                    'installment' => $inst,
                    'paid'        => $paid,
                    'remaining'   => $remaining,
                    'status'      => $status,
                ]);
            }
        }

        $totalDue = $feeStructure?->total_amount ?? 0;
        $totalPaid = StudentPayment::visible()->where('student_enrollment_id', $enrollment->id)
            ->sum('amount_paid');
        $totalScholarship = StudentPayment::visible()->where('student_enrollment_id', $enrollment->id)
            ->sum('scholarship_amount');
        $totalRemaining = max(0, $totalDue - ($totalPaid + $totalScholarship));

        // Historique des paiements
        $payments = StudentPayment::visible()->where(
            'student_enrollment_id', $enrollment->id
        )->with(['feeInstallment', 'recordedBy'])
         ->orderByDesc('payment_date')
         ->orderByDesc('created_at')
         ->get();

        return view('finances.student', compact(
            'enrollment', 'feeStructure',
            'installments', 'totalDue', 'totalPaid',
            'totalScholarship', 'totalRemaining', 'payments'
        ));
    }

    public function deletePayment(StudentPayment $payment)
    {
        $enrollmentId = $payment->student_enrollment_id;
        $receiptNumber = $payment->receipt_number;
        $amount = (int) $payment->amount_paid;
        $isManual = $payment->is_manual_insolvable_payment;

        DB::transaction(function () use ($payment, $enrollmentId, $receiptNumber, $amount, $isManual): void {
            if ($payment->is_bulk) {
                StudentPayment::where('parent_payment_id', $payment->id)->delete();
            }

            $payment->delete();

            if ($isManual) {
                $manual = ManualInsolvable::where('student_enrollment_id', $enrollmentId)
                    ->latest('id')
                    ->first();

                if ($manual) {
                    $totalPaid = max(0, (int) $manual->total_paid - $amount);
                    $manual->update([
                        'total_paid' => $totalPaid,
                        'remaining' => max(0, (int) $manual->total_due - $totalPaid),
                    ]);
                }
            }

            AuditLog::log('payment_deleted', null, [], [
                'payment_id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'student_enrollment_id' => $enrollmentId,
                'amount_paid' => $amount,
                'was_bulk' => (bool) $payment->is_bulk,
            ]);
        });

        return redirect()->route('finances.student', $enrollmentId)
            ->with('success', 'Paiement supprimé. Les soldes financiers ont été recalculés.');
    }

    private function cleanupOverpaidPayments(StudentEnrollment $enrollment): void
    {
        $feeStructure = $enrollment->classGroup()->with('feeStructures.installments')->first()?->feeStructures->first();
        if (! $feeStructure) {
            return;
        }

        $expected = (int) $feeStructure->installments->sum('amount');

        $payments = StudentPayment::where('student_enrollment_id', $enrollment->id)
            ->orderByDesc('created_at')
            ->get();

        $actualPayments = $payments->filter(fn($payment) => ! is_null($payment->fee_installment_id));
        $totalPaid = $actualPayments->sum(fn($p) =>
            (int) $p->amount_paid + (int) $p->scholarship_amount);

        if ($totalPaid <= $expected) {
            return;
        }

        $remaining = $totalPaid;
        $deleteIds = [];

        foreach ($actualPayments as $payment) {
            if ($remaining <= $expected) {
                break;
            }

            $deleteIds[] = $payment->id;
            $remaining -= (int) $payment->amount_paid + (int) $payment->scholarship_amount;

            if ($payment->parent_payment_id) {
                $deleteIds[] = $payment->parent_payment_id;
            }
        }

        $deleteIds = array_unique($deleteIds);
        if (! empty($deleteIds)) {
            StudentPayment::whereIn('id', $deleteIds)->delete();
            AuditLog::log('overpayment_cleanup', $enrollment, [], [
                'deleted_payment_ids' => $deleteIds,
            ]);
        }
    }
    public function classStudents(ClassGroup $classGroup)
    {
        $classGroup->load([
            'level.section',
            'academicYear',
            'feeStructures.installments',
        ]);

        $feeStructure = $classGroup->feeStructures->first();

        $enrollments = StudentEnrollment::where('class_group_id', $classGroup->id)
            ->where('status', 'active')
            ->with('student')
            ->orderBy(
                \App\Models\Student::select('last_name')
                    ->whereColumn('students.id', 'student_enrollments.student_id'),
                'asc'
            )
            ->get();

        $enrollments->each(fn($enrollment) => $this->cleanupOverpaidPayments($enrollment));

        $enrollments = $enrollments->map(function($enrollment) use ($feeStructure) {
                $due  = $feeStructure?->installments->sum('amount') ?? 0;
                $paidAmount = StudentPayment::visible()->where(
                    'student_enrollment_id', $enrollment->id
                )->sum('amount_paid');
                $scholarship = StudentPayment::visible()->where(
                    'student_enrollment_id', $enrollment->id
                )->sum('scholarship_amount');
                $paid = $paidAmount + $scholarship;
                $remaining = max(0, $due - $paid);
                $rate = $due > 0
                    ? round(($paid / $due) * 100)
                    : 0;
                $status = $paid <= 0 ? 'unpaid'
                    : ($paid >= $due ? 'paid' : 'partial');
                
                return compact(
                    'enrollment', 'due', 'paid', 'scholarship', 'remaining', 'rate', 'status'
                );
            });

        $totalDue         = $enrollments->sum('due');
        $totalPaid        = $enrollments->sum('paid');
        $totalScholarship = $enrollments->sum('scholarship');
        $totalRemaining   = max(0, $totalDue - $totalPaid);
        $globalRate       = $totalDue > 0
            ? round(($totalPaid / $totalDue) * 100) : 0;

        return view('finances.class-students', compact(
            'classGroup', 'feeStructure', 'enrollments',
            'totalDue', 'totalPaid', 'totalScholarship',
            'totalRemaining', 'globalRate'
        ));
    }

    // ── ENREGISTRER UN PAIEMENT ───────────────────────────────────────────
    public function recordPayment(StorePaymentRequest $request,
                                  StudentEnrollment $enrollment)
    {
        $installment = FeeInstallment::find($request->fee_installment_id);

        if (! $installment) {
            return back()->with('error', 'Tranche introuvable pour ce paiement.');
        }

        // Vérifier qu'on ne dépasse pas le montant de la tranche en tenant compte des bourses déjà appliquées
        $alreadyPaid = StudentPayment::where([
            'student_enrollment_id' => $enrollment->id,
            'fee_installment_id'    => $installment->id,
        ])->sum('amount_paid')
          + StudentPayment::where([
            'student_enrollment_id' => $enrollment->id,
            'fee_installment_id'    => $installment->id,
        ])->sum('scholarship_amount');

        $remaining = max(0, (int) $installment->amount - $alreadyPaid);

        if ($remaining <= 0) {
            return back()->with('error', 'Cette tranche est déjà soldée, aucun paiement supplémentaire n’est possible.');
        }

        if ((int) round($request->amount_paid) > $remaining) {
            return back()->with('error',
                "Le montant saisi ({$request->amount_paid} FCFA) dépasse le restant dû "
                . "de cette tranche ({$remaining} FCFA). Le paiement a été refusé.");
        }

        $payment = StudentPayment::create([
            'student_enrollment_id' => $enrollment->id,
            'fee_installment_id'    => $request->fee_installment_id,
            'amount_paid'           => $request->amount_paid,
            'payment_date'          => $request->payment_date,
            'payment_method'        => $request->payment_method,
            'reference'             => $request->reference,
            'receipt_number'        => StudentPayment::generateReceiptNumber(),
            'recorded_by'           => Auth::id(),
            'notes'                 => $request->notes,
        ]);

        AuditLog::log('payment_recorded', $payment);

        return redirect()
            ->route('finances.student', $enrollment)
            ->with('success',
                "Paiement de " . number_format($request->amount_paid)
                . " FCFA enregistré. Reçu : {$payment->receipt_number}");
    }

    public function bulkPay(Request $request, StudentEnrollment $enrollment)
    {
        $request->validate([
            'amount_paid'       => ['required', 'numeric', 'min:0'],
            'scholarship_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $feeStructure = $enrollment->classGroup()->with('feeStructures.installments')->first()?->feeStructures->first();
        if (! $feeStructure) {
            return back()->with('error', 'Aucune structure de frais n\'est configurée pour cette classe.');
        }

        $remainingAmount = (int) round($request->amount_paid);
        $scholarshipAmount = (int) round($request->input('scholarship_amount', 0));

        if ($remainingAmount <= 0 && $scholarshipAmount <= 0) {
            return back()->with('error', 'Le montant à payer ou la bourse doit être supérieur à 0.');
        }

        if ($scholarshipAmount < 0) {
            return back()->with('error', 'Le montant de la bourse ne peut pas être négatif.');
        }

        $installments = $feeStructure->installments()->with('payments')->get();
        $remainingByInstallment = [];
        foreach ($installments as $installment) {
            $paidAlready = $installment->payments
                ->where('student_enrollment_id', $enrollment->id)
                ->sum(fn($payment) => (int) $payment->amount_paid + (int) $payment->scholarship_amount);
            $remainingByInstallment[$installment->id] = max(0, (int) $installment->amount - $paidAlready);
        }

        $totalRemaining = array_sum($remainingByInstallment);
        $coverage = $remainingAmount + $scholarshipAmount;

        if ($totalRemaining <= 0) {
            $message = 'Le compte de cet élève est déjà soldé, aucun paiement supplémentaire n’est possible.';
            if ($request->ajax()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->with('error', $message);
        }

        if ($coverage > $totalRemaining) {
            $message = 'Le montant total du paiement et de la bourse dépasse le restant dû ('
                . number_format($totalRemaining) . ' FCFA). Le paiement a été refusé.';
            if ($request->ajax()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->with('error', $message);
        }

        if ($scholarshipAmount > 0 && StudentPayment::where('student_enrollment_id', $enrollment->id)
                ->where('scholarship_amount', '>', 0)
                ->exists()) {
            $message = 'Une seule bourse par année scolaire est autorisée pour cet élève.';
            if ($request->ajax()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $paymentDate = $request->filled('payment_date')
            ? $request->input('payment_date')
            : now()->toDateString();
        $paymentMethod = $request->filled('payment_method')
            ? $request->input('payment_method')
            : 'cash';

        $recordedById = Auth::id()
            ?? optional($request->user())->id
            ?? User::query()->value('id');

        $bulkPayment = StudentPayment::create([
            'student_enrollment_id' => $enrollment->id,
            'fee_installment_id'    => null,
            'amount_paid'           => $remainingAmount,
            'scholarship_amount'    => $scholarshipAmount,
            'payment_date'          => $paymentDate,
            'payment_method'        => $paymentMethod,
            'reference'             => null,
            'receipt_number'        => StudentPayment::generateReceiptNumber(),
            'recorded_by'           => $recordedById,
            'notes'                 => $scholarshipAmount > 0
                ? 'Paiement en bloc — bourse de ' . number_format($scholarshipAmount) . ' FCFA'
                : 'Paiement en bloc',
            'is_bulk'               => true,
        ]);

        // Distribute combined coverage (cash + scholarship) across installments.
        $coverage = $remainingAmount + $scholarshipAmount;
        $allocated = 0;
        $allocationIndex = 0;
        $remainingScholarship = $scholarshipAmount;
        $remainingCash = $remainingAmount;

        foreach ($installments->sortBy('installment_number') as $installment) {
            if ($allocated >= $coverage) {
                break;
            }

            $available = $remainingByInstallment[$installment->id] ?? 0;
            if ($available <= 0) {
                continue;
            }

            $need = min($available, $coverage - $allocated);
            if ($need <= 0) {
                continue;
            }

            // Consume scholarship first, then cash for the required amount
            $useScholarship = min($need, $remainingScholarship);
            $useCash = $need - $useScholarship;

            $allocationIndex++;

            StudentPayment::create([
                'student_enrollment_id' => $enrollment->id,
                'parent_payment_id'     => $bulkPayment->id,
                'fee_installment_id'    => $installment->id,
                'amount_paid'           => $useCash,
                'scholarship_amount'    => $useScholarship,
                'payment_date'          => $paymentDate,
                'payment_method'        => $paymentMethod,
                'reference'             => null,
                'receipt_number'        => $bulkPayment->receipt_number . '-A' . $allocationIndex,
                'recorded_by'           => $recordedById,
                'notes'                 => 'Paiement en bloc',
                'is_bulk'               => false,
            ]);

            $allocated += $need;
            $remainingByInstallment[$installment->id] = $available - $need;
            $remainingScholarship -= $useScholarship;
            $remainingCash -= $useCash;
        }

        // Update parent's snapshot totals to reflect current state
        $feeTotal = $feeStructure->installments->sum('amount');
        $totalPaid = (int) StudentPayment::visible()->where('student_enrollment_id', $enrollment->id)->sum('amount_paid');
        $totalScholarship = (int) StudentPayment::visible()->where('student_enrollment_id', $enrollment->id)->sum('scholarship_amount');
        $totalRemaining = max(0, $feeTotal - ($totalPaid + $totalScholarship));

        $bulkPayment->forceFill([
            'snapshot_total_due'       => $feeTotal,
            'snapshot_total_paid'      => $totalPaid,
            'snapshot_total_remaining' => $totalRemaining,
        ])->saveQuietly();

        AuditLog::log('bulk_payment_recorded', $bulkPayment);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'receipt_url' => route('finances.receipt', $bulkPayment),
            ]);
        }

        return redirect()
            ->route('finances.receipt', $bulkPayment)
            ->with('success', 'Paiement en bloc enregistré. Reçu : ' . $bulkPayment->receipt_number);
    }

    // ── LISTE DE TOUS LES PAIEMENTS ───────────────────────────────────────
    public function payments(Request $request)
    {
        /** @var \App\Models\User $user */
        $user              = Auth::user();
        $isAdmin           = $user->hasRole('super-admin')
            || $user->hasRole('directeur')
            || $user->hasRole('fondateur');
        $activeYear        = AcademicYear::active();
        $selectedYearId    = $request->input('year_id', $activeYear?->id);
        $selectedResponsible = $request->input('responsible', $isAdmin ? 'global' : 'me');

        $query = StudentPayment::visible()
            ->with([
                'studentEnrollment.student',
                'studentEnrollment.classGroup.level.section',
                'feeInstallment',
                'recordedBy',
            ]);

        if ($selectedYearId) {
            $query->whereHas('studentEnrollment', fn($q) =>
                $q->where('academic_year_id', $selectedYearId)
            );
        }

        if ($request->filled('class_id')) {
            $query->whereHas('studentEnrollment', fn($q) =>
                $q->where('class_group_id', $request->class_id)
            );
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('receipt_number', 'like', "%{$s}%")
                  ->orWhereHas('studentEnrollment.student', fn($q2) =>
                      $q2->where('first_name', 'like', "%{$s}%")
                         ->orWhere('last_name',  'like', "%{$s}%")
                         ->orWhere('matricule',  'like', "%{$s}%")
                  )
            );
        }

        if (! $isAdmin) {
            $query->where('recorded_by', $user->id);
        } elseif ($selectedResponsible === 'me') {
            $query->where('recorded_by', $user->id);
        } elseif ($selectedResponsible !== 'global') {
            $query->where('recorded_by', $selectedResponsible);
        }

        $payments = $query->orderByDesc('payment_date')
                          ->orderByDesc('created_at')
                          ->paginate(20)
                          ->withQueryString();

        $years   = AcademicYear::orderByDesc('start_date')->get();
        $classes = $selectedYearId
            ? ClassGroup::where('academic_year_id', $selectedYearId)
                ->orderBy('name')->get()
            : collect();

        $totalFiltered = $query->sum('amount_paid') + $query->sum('scholarship_amount');

        $recorders = $isAdmin
            ? User::whereIn('id', StudentPayment::visible()
                    ->when($selectedYearId, fn($q) => $q->whereHas('studentEnrollment', fn($q2) =>
                        $q2->where('academic_year_id', $selectedYearId)
                    ))
                    ->when($request->filled('class_id'), fn($q) => $q->whereHas('studentEnrollment', fn($q2) =>
                        $q2->where('class_group_id', $request->class_id)
                    ))
                    ->whereNotNull('recorded_by')
                    ->pluck('recorded_by')
                    ->unique()
                    ->filter()
                    ->toArray())
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
            : collect([$user]);

        return view('finances.payments', compact(
            'payments', 'years', 'classes',
            'selectedYearId', 'totalFiltered',
            'recorders', 'selectedResponsible', 'isAdmin'
        ));
    }

    // // ── REÇU ──────────────────────────────────────────────────────────────
    // public function receipt(StudentPayment $payment)
    // {
    //     $payment->load([
    //         'studentEnrollment.student',
    //         'studentEnrollment.classGroup.level.section',
    //         'studentEnrollment.academicYear',
    //         'feeInstallment.feeStructure',
    //         'recordedBy',
    //     ]);

    //     $school = \App\Models\SchoolSetting::instance();

    //     return view('finances.receipt',
    //         compact('payment', 'school'));
    // }

    // ── REÇU PAIEMENT UNIQUE ──────────────────────────────────────────────
    public function receipt(StudentPayment $payment)
    {
        $payment->load([
            'studentEnrollment.student',
            'studentEnrollment.classGroup.level.section',
            'studentEnrollment.academicYear',
            'studentEnrollment.classGroup.feeStructures.installments',
            'feeInstallment',
            'allocations.feeInstallment',
            'recordedBy',
        ]);

        $school      = \App\Models\SchoolSetting::instance();
        $phones      = \App\Models\SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();
        $enrollment  = $payment->studentEnrollment;
        if (! $enrollment) {
            abort(404, 'Inscription de l\'élève introuvable pour ce paiement.');
        }
        $section     = $enrollment->classGroup?->level?->section;

        ['totalDue' => $totalDue, 'totalPaid' => $totalPaid, 'totalRemaining' => $totalRemaining] = $this->resolveReceiptTotals($payment);

        $isEnglishReceipt = $section?->isAnglophone() ?? false;

        return view('finances.receipt',
            compact('payment', 'school', 'phones',
                    'totalDue', 'totalPaid', 'totalRemaining', 'isEnglishReceipt'));
    }

    private function resolveReceiptTotals(StudentPayment $payment): array
    {
        $enrollment = $payment->studentEnrollment;
        if (! $enrollment) {
            return ['totalDue' => 0, 'totalPaid' => 0, 'totalRemaining' => 0];
        }

        $manual = $payment->is_manual_insolvable_payment;
        $manualRecord = $manual
            ? ManualInsolvable::where('student_enrollment_id', $payment->student_enrollment_id)->latest('id')->first()
            : null;

        if ($manual) {
            $totalDue = (int) ($payment->snapshot_total_due ?? $manualRecord?->total_due ?? 0);
            $totalPaid = (int) ($payment->snapshot_total_paid ?? StudentPayment::visible()
                ->where('student_enrollment_id', $payment->student_enrollment_id)
                ->whereNull('fee_installment_id')->where('is_bulk', false)
                ->where('id', '<=', $payment->id)->sum('amount_paid'));
            return [
                'totalDue' => $totalDue,
                'totalPaid' => $totalPaid,
                'totalRemaining' => (int) ($payment->snapshot_total_remaining ?? max(0, $totalDue - $totalPaid)),
            ];
        }

        $feeStructure = $enrollment->classGroup()->with('feeStructures.installments')->first()?->feeStructures->first();
        $totalDue = (int) ($payment->snapshot_total_due ?? $feeStructure?->installments->sum('amount') ?? 0);
        $totalPaid = (int) ($payment->snapshot_total_paid ?? StudentPayment::visible()->where('student_enrollment_id', $enrollment->id)->sum('amount_paid'));
        $scholarships = (int) StudentPayment::visible()->where('student_enrollment_id', $enrollment->id)->sum('scholarship_amount');

        return [
            'totalDue' => $totalDue,
            'totalPaid' => $totalPaid,
            'totalRemaining' => (int) ($payment->snapshot_total_remaining ?? max(0, $totalDue - $totalPaid - $scholarships)),
        ];
    }

    // ── REÇU GLOBAL (tous les paiements d'un élève) ───────────────────────
    public function globalReceipt(StudentEnrollment $enrollment)
    {
        $enrollment->load([
            'student',
            'classGroup.level.section',
            'academicYear',
            'classGroup.feeStructures.installments',
        ]);

        // Paiements du plus récent au plus ancien
        $payments = StudentPayment::visible()->where('student_enrollment_id', $enrollment->id)
            ->with(['feeInstallment', 'recordedBy'])
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->get();

        $feeStructure = $enrollment->classGroup()->with('feeStructures.installments')->first()?->feeStructures->first();

        $allocationsPayments = StudentPayment::where('student_enrollment_id', $enrollment->id)
            ->where('is_bulk', false)
            ->get();

        // Résumé par tranche
        $installmentSummary = collect();
        if ($feeStructure) {
            foreach ($feeStructure->installments
                        ->sortBy('installment_number') as $inst) {
                $paid = $allocationsPayments
                    ->where('fee_installment_id', $inst->id)
                    ->sum(fn($p) => (int) $p->amount_paid + (int) $p->scholarship_amount);

                $installmentSummary->push([
                    'label'     => $inst->label,
                    'amount'    => $inst->amount,
                    'paid'      => $paid,
                    'remaining' => max(0, $inst->amount - $paid),
                    'due_date'  => $inst->due_date_end,
                ]);
            }
        }

        $totalDue       = $feeStructure?->total_amount ?? 0;
        $totalPaid       = $payments->sum(fn ($payment) => (int) $payment->amount_paid + (int) $payment->scholarship_amount);
        $totalScholarship = $payments->sum('scholarship_amount');
        $totalRemaining  = max(0, $totalDue - $totalPaid);
        $school          = \App\Models\SchoolSetting::instance();
        $phones         = \App\Models\SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();
        $isEnglishReceipt = $enrollment->classGroup->level->section?->isAnglophone() ?? false;

        return view('finances.receipt-global', compact(
            'enrollment', 'payments', 'feeStructure',
            'installmentSummary', 'totalDue', 'totalPaid',
            'totalRemaining', 'school', 'phones', 'isEnglishReceipt'
        ));
    }

    // ── IMPRESSION GROUPÉE (2 reçus / page A4 paysage) ───────────────────
    public function batchReceipts(Request $request)
    {
        $ids = array_filter(explode(',', $request->input('ids', '')));

        if (empty($ids)) {
            return back()->with('error', 'Aucun paiement sélectionné.');
        }

        $payments = StudentPayment::whereIn('id', $ids)
            ->with([
                'studentEnrollment.student',
                'studentEnrollment.classGroup.level.section',
                'studentEnrollment.academicYear',
                'studentEnrollment.classGroup.feeStructures.installments',
                'feeInstallment',
                'allocations.feeInstallment',
                'recordedBy',
            ])
            ->orderByDesc('payment_date')
            ->get();

        $school = \App\Models\SchoolSetting::instance();
        $phones = \App\Models\SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();
        $isEnglishReceipt = $payments->contains(function ($payment) {
            return $payment->studentEnrollment->classGroup->level->section?->isAnglophone() ?? false;
        });

        $receiptsData = $payments->map(function($payment) {
            $enrollment   = $payment->studentEnrollment;
            $feeStructure = $enrollment->classGroup()->with('feeStructures.installments')->first()?->feeStructures->first();
            $totalDue     = $payment->snapshot_total_due ?? ($feeStructure?->installments->sum('amount') ?? 0);
            $totalScholarship = StudentPayment::visible()->where(
                'student_enrollment_id', $enrollment->id
            )->sum('scholarship_amount');
            $totalPaid    = $payment->snapshot_total_paid ?? StudentPayment::visible()->where(
                                'student_enrollment_id', $enrollment->id
                            )->sum('amount_paid');
            $totalRemaining = $payment->snapshot_total_remaining ?? max(0, $totalDue - ($totalPaid + $totalScholarship));
            return compact('payment', 'totalDue', 'totalPaid', 'totalRemaining');
        });

        return view('finances.receipt-batch',
            compact('receiptsData', 'school', 'phones', 'isEnglishReceipt'));
    }

    // ── LISTE PAIEMENTS (tri du plus récent au plus ancien) ───────────────
    
    
    // ── LISTE CLASSES POUR CONFIGURATION DES FRAIS ───────────────────────
    public function feesList(Request $request)
    {
        $activeYear       = AcademicYear::active();
        $selectedYearId   = $request->input('year_id', $activeYear?->id);
        $selectedSectionId = $request->input('section_id');
        $selectedYear     = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : null;
        $selectedSection  = $selectedSectionId
            ? Section::find($selectedSectionId)
            : null;

        $years    = AcademicYear::orderByDesc('start_date')->get();
        $sections = Section::orderBy('id')->get();

        $classes = collect();
        if ($selectedYear) {
            $query = ClassGroup::where('academic_year_id', $selectedYear->id)
                ->with([
                    'level.section',
                    'feeStructures.installments',
                    'studentEnrollments' => fn($q) =>
                        $q->where('status', 'active'),
                ]);

            if ($selectedSectionId) {
                $query->whereHas('level.section', fn ($q) =>
                    $q->where('id', $selectedSectionId)
                );
            }

            $classes = $query->orderBy('name')->get();
        }

        return view('finances.fees-list', compact(
            'classes',
            'years',
            'sections',
            'selectedYear',
            'selectedSection',
            'selectedSectionId',
            'activeYear'
        ));
    }

    // // ── RAPPORTS FINANCIERS ───────────────────────────────────────────────
    // public function reports(Request $request)
    // {
    //     $activeYear     = AcademicYear::active();
    //     $selectedYearId = $request->input('year_id', $activeYear?->id);
    //     $selectedYear   = $selectedYearId
    //         ? AcademicYear::find($selectedYearId)
    //         : null;

    //     $years = AcademicYear::orderByDesc('start_date')->get();

    //     // ── Stats par classe
    //     $classeStats = collect();
    //     if ($selectedYear) {
    //         $classes = ClassGroup::where('academic_year_id', $selectedYear->id)
    //             ->with([
    //                 'level.section',
    //                 'feeStructures.installments',
    //             ])
    //             ->withCount([
    //                 'studentEnrollments as enrolled_count' => fn($q) =>
    //                     $q->where('status', 'active'),
    //             ])
    //             ->get();

    //         foreach ($classes as $class) {
    //             $fee       = $class->feeStructures->first();
    //             $feeTotal  = $fee?->installments->sum('amount') ?? 0;
    //             $expected  = $feeTotal * $class->enrolled_count;
    //             $collected = \App\Models\StudentPayment::whereHas(
    //                 'studentEnrollment', fn($q) =>
    //                     $q->where('class_group_id', $class->id)
    //                     ->where('status', 'active')
    //             )->sum('amount_paid');

    //             $classeStats->push([
    //                 'class'     => $class,
    //                 'expected'  => $expected,
    //                 'collected' => $collected,
    //                 'remaining' => max(0, $expected - $collected),
    //                 'rate'      => $expected > 0
    //                     ? round(($collected / $expected) * 100) : 0,
    //             ]);
    //         }
    //     }

    //     // ── Stats par mode de paiement
    //     $paymentMethods = \App\Models\StudentPayment::selectRaw(
    //         'payment_method, SUM(amount_paid) as total, COUNT(*) as count'
    //     )
    //     ->when($selectedYear, fn($q) =>
    //         $q->whereHas('studentEnrollment', fn($q2) =>
    //             $q2->where('academic_year_id', $selectedYear->id)
    //         )
    //     )
    //     ->groupBy('payment_method')
    //     ->get();

    //     // ── Stats par tranche
    //     $installmentStats = FeeInstallment::selectRaw(
    //         'fee_installments.label,
    //         fee_installments.amount,
    //         SUM(student_payments.amount_paid) as collected,
    //         COUNT(DISTINCT student_payments.student_enrollment_id) as payers'
    //     )
    //     ->leftJoin('student_payments',
    //         'student_payments.fee_installment_id', '=', 'fee_installments.id')
    //     ->leftJoin('fee_structures',
    //         'fee_structures.id', '=', 'fee_installments.fee_structure_id')
    //     ->when($selectedYear, fn($q) =>
    //         $q->where('fee_structures.academic_year_id', $selectedYear->id)
    //     )
    //     ->groupBy('fee_installments.id',
    //             'fee_installments.label',
    //             'fee_installments.amount')
    //     ->orderBy('fee_installments.installment_number')
    //     ->get();

    //     // ── Élèves avec solde impayé
    //     $debtors = \App\Models\StudentEnrollment::where('status', 'active')
    //         ->when($selectedYear, fn($q) =>
    //             $q->where('academic_year_id', $selectedYear->id)
    //         )
    //         ->with([
    //             'student',
    //             'classGroup.level.section',
    //             'classGroup.feeStructures.installments',
    //         ])
    //         ->get()
    //         ->map(function($e) {
    //             $fee      = $e->classGroup->feeStructures->first();
    //             $due      = $fee?->installments->sum('amount') ?? 0;
    //             $paid     = \App\Models\StudentPayment::where(
    //                 'student_enrollment_id', $e->id
    //             )->sum('amount_paid');
    //             $remaining = max(0, $due - $paid);
    //             return ['enrollment' => $e, 'due' => $due,
    //                     'paid' => $paid, 'remaining' => $remaining];
    //         })
    //         ->filter(fn($e) => $e['remaining'] > 0)
    //         ->sortByDesc('remaining')
    //         ->values();

    //     // ── Totaux globaux
    //     $globalStats = [
    //         'expected'  => $classeStats->sum('expected'),
    //         'collected' => $classeStats->sum('collected'),
    //         'remaining' => $classeStats->sum('remaining'),
    //         'rate'      => $classeStats->sum('expected') > 0
    //             ? round(($classeStats->sum('collected')
    //                 / $classeStats->sum('expected')) * 100)
    //             : 0,
    //         'debtors'   => $debtors->count(),
    //     ];

    //     return view('finances.reports', compact(
    //         'selectedYear', 'years', 'classeStats',
    //         'paymentMethods', 'installmentStats',
    //         'debtors', 'globalStats'
    //     ));
    // }

    // ── RAPPORTS FINANCIERS ───────────────────────────────────────────────────
    public function reports(Request $request)
    {
        /** @var \App\Models\User $user */
        $user      = Auth::user();
        $activeYear = AcademicYear::active();
        $years     = AcademicYear::orderByDesc('start_date')->get();
        $isAdmin   = $user->hasAnyRole(['super-admin', 'directeur', 'fondateur']);

        // ── Filtres ─────────────────────────────────────────────────────────
        $allowedTypes = ['journalier', 'hebdomadaire', 'mensuel', 'annuel', 'entre-2-dates'];
        $type       = $request->input('type', 'mensuel');
        if (! in_array($type, $allowedTypes, true)) {
            $type = 'mensuel';
        }

        $yearId     = $request->input('year_id', $activeYear?->id);
        $month      = (int) $request->input('month', now()->month);
        $date       = $request->input('date', now()->toDateString());
        $week       = $request->input('week', now()->format('o-\WW'));
        $startDate  = $request->input('start_date', now()->toDateString());
        $endDate    = $request->input('end_date', now()->toDateString());
        $whoFilter  = $isAdmin
            ? $request->input('who', 'global') // global | me | econome
            : 'me'; // économe voit seulement ses propres données

        $selectedYear = $yearId ? AcademicYear::find($yearId) : $activeYear;

        // ── Construire la requête de base ───────────────────────────────────
        $paymentsQuery = StudentPayment::with([
            'studentEnrollment.student',
            'studentEnrollment.classGroup.level.section',
            'feeInstallment',
            'recordedBy',
        ]);

        // Filtrer par année scolaire
        if ($selectedYear) {
            $paymentsQuery->whereHas('studentEnrollment', fn($q) =>
                $q->where('academic_year_id', $selectedYear->id)
            );
        }

        // Filtrer par responsable
        if ($whoFilter === 'me') {
            $paymentsQuery->where('recorded_by', $user->id);
        } elseif ($whoFilter === 'econome') {
            // Trouver le(s) compte(s) économe
            $economeIds = \Spatie\Permission\Models\Role::findByName('econome')
                ->users->pluck('id');
            $paymentsQuery->whereIn('recorded_by', $economeIds);
        }
        // 'global' = pas de filtre sur recorded_by

        // Filtrer par période
        if ($type === 'journalier') {
            $paymentsQuery->whereDate('payment_date', $date);
        } elseif ($type === 'hebdomadaire') {
            try {
                $weekStart = Carbon::parse($week . '-1')->startOfWeek(Carbon::MONDAY)->toDateString();
            } catch (\Throwable $e) {
                $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
            }
            $weekEnd = Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY)->toDateString();
            $paymentsQuery->whereBetween('payment_date', [$weekStart, $weekEnd]);
        } elseif ($type === 'mensuel') {
            $paymentYear = $this->paymentCalendarYearForMonth($selectedYear, $month);
            $paymentsQuery
                ->whereYear('payment_date', $paymentYear)
                ->whereMonth('payment_date', $month);
        } elseif ($type === 'entre-2-dates') {
            try {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($endDate)->endOfDay();
            } catch (\Throwable $e) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($startDate)->endOfDay();
            }
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }
            $paymentsQuery->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);
        }

        $allPaymentRows = $paymentsQuery
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->get();
        $allPayments = $allPaymentRows->filter(fn ($payment) =>
            is_null($payment->parent_payment_id) || $payment->is_bulk
        )->values();

        // ── Stats globales ────────────────────────────────────────────────
        $totalCollected = (int)$allPayments->sum('amount_paid');

        $installmentExpectations = $this->buildInstallmentFinancialStats($selectedYear, $allPaymentRows);
        $byInstallment = $installmentExpectations->map(fn ($item) => [
            'label' => $item->label,
            'total' => (int) $item->collected,
            'expected' => (int) $item->expected,
            'remaining' => (int) $item->remaining,
            'rate' => (int) $item->rate,
            'count' => (int) $item->count,
        ])->values();
        $byMethod = $allPayments->groupBy('payment_method')
            ->map(fn($g) => [
                'method' => $g->first()?->payment_method,
                'label'  => $g->first()?->payment_method_label ?? '—',
                'total'  => (int)$g->sum('amount_paid'),
                'count'  => $g->count(),
            ])->sortByDesc('total')->values();

        // Par section
        $bySection = $allPayments->groupBy(
            fn($p) => $p->studentEnrollment?->classGroup?->level?->section?->name ?? 'Inconnue'
        )->map(fn($g, $name) => [
            'name'  => $name,
            'total' => (int)$g->sum('amount_paid'),
            'count' => $g->count(),
        ])->sortByDesc('total')->values();

        // Évolution (pour graphique mensuel ou mensuel de l'année)
        $evolution = ($type === 'annuel' && $selectedYear)
            ? $this->buildYearlyEvolution($allPayments, $selectedYear)
            : [];

        // ── Économes disponibles (pour directeur) ─────────────────────────
        $economes = $isAdmin
            ? \App\Models\User::role('econome')->orderBy('name')->get()
            : collect();

        return view('finances.reports', compact(
            'user', 'isAdmin', 'selectedYear', 'years',
            'type', 'month', 'date', 'week', 'startDate', 'endDate', 'whoFilter',
            'allPayments', 'totalCollected',
            'byInstallment', 'byMethod', 'bySection', 'evolution',
            'economes'
        ));
    }


    private function buildInstallmentFinancialStats(?AcademicYear $academicYear, $payments = null)
    {
        if (! $academicYear) {
            return collect();
        }

        $payments = $payments ?: StudentPayment::whereHas('studentEnrollment', fn ($q) =>
            $q->where('academic_year_id', $academicYear->id)
        )->get();

        $classes = ClassGroup::where('academic_year_id', $academicYear->id)
            ->with(['feeStructures.installments'])
            ->withCount([
                'studentEnrollments as enrolled_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->get();

        $stats = collect();

        foreach ($classes as $class) {
            $fee = $class->feeStructures->first();
            if (! $fee) continue;

            foreach ($fee->installments as $installment) {
                $key = strtolower(trim((string) $installment->label));
                $current = $stats->get($key, (object) [
                    'label' => $installment->label,
                    'installment_number' => $installment->installment_number,
                    'expected' => 0.0,
                    'collected' => 0.0,
                    'payers' => 0,
                    'count' => 0,
                    'rate' => 0,
                ]);

                $installmentPayments = $payments->where('fee_installment_id', $installment->id);
                $current->expected += (float) $installment->amount * (int) $class->enrolled_count;
                $current->collected += (float) $installmentPayments->sum(fn ($payment) => (int) $payment->amount_paid + (int) $payment->scholarship_amount);
                $current->payers += $installmentPayments->pluck('student_enrollment_id')->unique()->count();
                $current->count += $installmentPayments->count();
                $current->installment_number = min((int) $current->installment_number, (int) $installment->installment_number);

                $stats->put($key, $current);
            }
        }

        return $stats->values()
            ->map(function ($item) {
                $item->remaining = max(0, $item->expected - $item->collected);
                $item->rate = $item->expected > 0 ? min(100, round(($item->collected / $item->expected) * 100)) : 0;
                return $item;
            })
            ->sortBy('installment_number')
            ->values();
    }

    private function paymentCalendarYearForMonth(?AcademicYear $academicYear, int $month): int
    {
        if (! $academicYear || ! $academicYear->start_date) {
            return now()->year;
        }

        $startYear  = (int) $academicYear->start_date->format('Y');
        $endYear    = $academicYear->end_date ? (int) $academicYear->end_date->format('Y') : $startYear;
        $startMonth = (int) $academicYear->start_date->format('n');

        if ($startYear === $endYear) {
            return $startYear;
        }

        return $month >= $startMonth ? $startYear : $endYear;
    }

    private function buildYearlyEvolution($payments, ?AcademicYear $academicYear = null): array
    {
        if (! $academicYear) {
            return [];
        }

        $result = [];

        foreach ($academicYear->monthPeriods() as $period) {
            $filtered = $payments->filter(fn ($p) =>
                (int) $p->payment_date->format('n') === $period['month']
                && (int) $p->payment_date->format('Y') === $period['year']
            );

            $result[] = [
                'label'      => $period['label'],
                'full_label' => $period['full_label'],
                'month'      => $period['month'],
                'year'       => $period['year'],
                'total'      => (int) $filtered->sum('amount_paid'),
                'count'      => $filtered->count(),
            ];
        }

        return $result;
    }

    // ── APERÇU IMPRIMABLE (comme certificats / fiches) ─────────────────────
    public function exportReport(Request $request)
    {
        $reportData = $this->buildReportData($request);
        $school     = \App\Models\SchoolSetting::instance();
        $phones     = \App\Models\SchoolPhone::orderByDesc('is_primary')->get();
        $agreements = \App\Models\SchoolAgreement::orderBy('id')->get();

        return view(
            'finances.reports-pdf',
            array_merge($reportData, compact('school', 'phones', 'agreements'))
        );
    }

    private function buildReportData(Request $request): array
    {
        /** @var \App\Models\User $user */
        $user       = Auth::user();
        $activeYear = AcademicYear::active();
        $years      = AcademicYear::orderByDesc('start_date')->get();
        $isAdmin    = $user->hasAnyRole(['super-admin','directeur','fondateur']);

        $allowedTypes = ['journalier', 'hebdomadaire', 'mensuel', 'annuel', 'entre-2-dates'];
        $type      = $request->input('type', 'mensuel');
        if (! in_array($type, $allowedTypes, true)) {
            $type = 'mensuel';
        }

        $yearId    = $request->input('year_id', $activeYear?->id);
        $month     = (int)$request->input('month', now()->month);
        $date      = $request->input('date', now()->toDateString());
        $week      = $request->input('week', now()->format('o-\WW'));
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());
        $whoFilter = $isAdmin ? $request->input('who', 'global') : 'me';

        $selectedYear = $yearId ? AcademicYear::find($yearId) : $activeYear;

        $paymentsQuery = StudentPayment::with([
            'studentEnrollment.student',
            'studentEnrollment.classGroup.level.section',
            'feeInstallment',
            'recordedBy',
        ]);

        if ($selectedYear) {
            $paymentsQuery->whereHas('studentEnrollment', fn($q) =>
                $q->where('academic_year_id', $selectedYear->id)
            );
        }

        if ($type === 'journalier') {
            $paymentsQuery->whereDate('payment_date', $date);
        } elseif ($type === 'hebdomadaire') {
            try {
                $weekStart = Carbon::parse($week . '-1')->startOfWeek(Carbon::MONDAY)->toDateString();
            } catch (\Throwable $e) {
                $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
            }
            $weekEnd = Carbon::parse($weekStart)->endOfWeek(Carbon::SUNDAY)->toDateString();
            $paymentsQuery->whereBetween('payment_date', [$weekStart, $weekEnd]);
        } elseif ($type === 'mensuel') {
            $paymentYear = $this->paymentCalendarYearForMonth($selectedYear, $month);
            $paymentsQuery
                ->whereYear('payment_date', $paymentYear)
                ->whereMonth('payment_date', $month);
        } elseif ($type === 'entre-2-dates') {
            try {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($endDate)->endOfDay();
            } catch (\Throwable $e) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($startDate)->endOfDay();
            }
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }
            $paymentsQuery->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);
        }

        if ($whoFilter === 'me') {
            $paymentsQuery->where('recorded_by', $user->id);
        } elseif ($whoFilter === 'econome') {
            $economeIds = \Spatie\Permission\Models\Role::findByName('econome')
                ->users->pluck('id');
            $paymentsQuery->whereIn('recorded_by', $economeIds);
        }

        $allPaymentRows = $paymentsQuery
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->get();
        $allPayments = $allPaymentRows->filter(fn ($payment) =>
            is_null($payment->parent_payment_id) || $payment->is_bulk
        )->values();
        $totalCollected = (int)$allPayments->sum('amount_paid');
        $installmentExpectations = $this->buildInstallmentFinancialStats($selectedYear, $allPaymentRows);
        $byInstallment = $installmentExpectations->map(fn ($item) => [
            'label' => $item->label,
            'total' => (int) $item->collected,
            'expected' => (int) $item->expected,
            'remaining' => (int) $item->remaining,
            'rate' => (int) $item->rate,
            'count' => (int) $item->count,
        ])->values();
        $byMethod = $allPayments->groupBy('payment_method')
            ->map(fn($g) => [
                'method' => $g->first()?->payment_method,
                'label' => $g->first()?->payment_method_label ?? '—',
                'total' => (int)$g->sum('amount_paid'),
                'count' => $g->count(),
            ])->sortByDesc('total')->values();

        $bySection = $allPayments->groupBy(
            fn($p) => $p->studentEnrollment?->classGroup?->level?->section?->name ?? '—'
        )->map(fn($g, $name) => [
            'name'  => $name,
            'total' => (int)$g->sum('amount_paid'),
            'count' => $g->count(),
        ])->sortByDesc('total')->values();

        $evolution = ($type === 'annuel' && $selectedYear)
            ? $this->buildYearlyEvolution($allPayments, $selectedYear)
            : [];

        $economes = $isAdmin
            ? \App\Models\User::role('econome')->orderBy('name')->get()
            : collect();

        return compact(
            'user', 'isAdmin', 'selectedYear', 'years',
            'type', 'month', 'date', 'week', 'startDate', 'endDate', 'whoFilter',
            'allPayments', 'totalCollected',
            'byInstallment', 'byMethod', 'bySection', 'evolution',
            'economes'
        );
    }

    // ── TABLEAU DE BORD DE GESTION GLOBALE ───────────────────────────────
    public function scholarships(Request $request)
    {
        $filters = $this->resolveScholarshipFilters($request);
        $query = $this->scholarshipQuery($filters);

        $totalScholarships = (clone $query)->sum('scholarship_amount');
        $scholarshipCount = (clone $query)->count();
        $scholarships = $query
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('finances.scholarships', array_merge($filters, [
            'years' => AcademicYear::orderByDesc('start_date')->get(),
            'cashiers' => $this->scholarshipCashiers(),
            'scholarships' => $scholarships,
            'totalScholarships' => $totalScholarships,
            'scholarshipCount' => $scholarshipCount,
            'periodLabel' => $this->scholarshipPeriodLabel($filters),
        ]));
    }

    public function printScholarships(Request $request)
    {
        $filters = $this->resolveScholarshipFilters($request);
        $query = $this->scholarshipQuery($filters);

        $scholarships = $query
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->get();

        return view('finances.scholarships-print', array_merge($filters, [
            'school' => \App\Models\SchoolSetting::instance(),
            'phones' => \App\Models\SchoolPhone::orderBy('id')->get(),
            'agreements' => \App\Models\SchoolAgreement::orderBy('id')->get(),
            'scholarships' => $scholarships,
            'totalScholarships' => $scholarships->sum('scholarship_amount'),
            'scholarshipCount' => $scholarships->count(),
            'periodLabel' => $this->scholarshipPeriodLabel($filters),
        ]));
    }

    private function resolveScholarshipFilters(Request $request): array
    {
        $activeYear = AcademicYear::active();
        $selectedYearId = $request->input('year_id', $activeYear?->id);
        $selectedYear = $selectedYearId ? AcademicYear::find($selectedYearId) : null;
        $type = $request->input('type', 'annuel');
        $date = $request->input('date', now()->toDateString());
        $week = $request->input('week', now()->format('o-\\WW'));
        $month = (int) $request->input('month', now()->month);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $cashierId = $request->input('cashier_id');

        $from = null;
        $to = null;

        if ($type === 'journalier') {
            $from = Carbon::parse($date)->startOfDay();
            $to = Carbon::parse($date)->endOfDay();
        } elseif ($type === 'hebdomadaire') {
            try {
                $from = Carbon::parse($week . '-1')->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable $e) {
                $from = now()->startOfWeek(Carbon::MONDAY);
                $week = $from->format('o-\\WW');
            }
            $to = $from->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($type === 'mensuel') {
            $month = max(1, min(12, $month));
            // Instead of computing a specific calendar year for the chosen month
            // (which is ambiguous when start month == end month, e.g. July→July),
            // we scope to the academic year's full date range and filter by month number.
            // The monthFilter key is used in scholarshipQuery via whereMonth().
            if ($selectedYear) {
                $from = $selectedYear->start_date
                    ? Carbon::parse($selectedYear->start_date)->startOfDay()
                    : null;
                $to = $selectedYear->end_date
                    ? Carbon::parse($selectedYear->end_date)->endOfDay()
                    : null;
            }
            // $from/$to intentionally left null when no year selected (fallback: no date range)
        } elseif ($type === 'entre-2-dates') {
            $from = Carbon::parse($startDate ?: now()->toDateString())->startOfDay();
            $to = Carbon::parse($endDate ?: now()->toDateString())->endOfDay();
            $startDate = $from->toDateString();
            $endDate = $to->toDateString();
        } elseif ($selectedYear) {
            $from = $selectedYear->start_date ? Carbon::parse($selectedYear->start_date)->startOfDay() : null;
            $to = $selectedYear->end_date ? Carbon::parse($selectedYear->end_date)->endOfDay() : null;
        }

        return [
            'selectedYear' => $selectedYear,
            'type'        => $type,
            'date'        => $date,
            'week'        => $week,
            'month'       => $month,
            'monthFilter' => $type === 'mensuel' ? $month : null,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'cashierId'   => $cashierId,
            'from'        => $from,
            'to'          => $to,
        ];
    }

    private function scholarshipQuery(array $filters)
    {
        return StudentPayment::visible()
            ->where('scholarship_amount', '>', 0)
            ->with([
                'studentEnrollment.student',
                'studentEnrollment.classGroup.level.section',
                'feeInstallment',
                'recordedBy',
            ])
            ->when($filters['selectedYear'], fn ($q) =>
                $q->whereHas('studentEnrollment', fn ($q2) =>
                    $q2->where('academic_year_id', $filters['selectedYear']->id)
                )
            )
            ->when($filters['cashierId'], fn ($q) =>
                $q->where('recorded_by', $filters['cashierId'])
            )
            // Monthly filter: scope by month number within the academic year date range
            ->when($filters['monthFilter'] ?? null, fn ($q) =>
                $q->whereMonth('payment_date', $filters['monthFilter'])
            )
            ->when($filters['from'], fn ($q) =>
                $q->whereDate('payment_date', '>=', $filters['from']->toDateString())
            )
            ->when($filters['to'], fn ($q) =>
                $q->whereDate('payment_date', '<=', $filters['to']->toDateString())
            );
    }

    private function scholarshipCashiers()
    {
        $ids = StudentPayment::visible()
            ->where('scholarship_amount', '>', 0)
            ->whereNotNull('recorded_by')
            ->pluck('recorded_by')
            ->unique()
            ->values();

        return User::whereIn('id', $ids)->orderBy('name')->get();
    }

    private function scholarshipPeriodLabel(array $filters): string
    {
        return match ($filters['type']) {
            'journalier' => 'Journee du ' . Carbon::parse($filters['date'])->format('d/m/Y'),
            'hebdomadaire' => 'Semaine du ' . $filters['from']->format('d/m/Y') . ' au ' . $filters['to']->format('d/m/Y'),
            'mensuel' => 'Mois de ' . Carbon::create(null, (int) $filters['month'], 1)->locale('fr')->translatedFormat('F'),
            'entre-2-dates' => 'Du ' . $filters['from']->format('d/m/Y') . ' au ' . $filters['to']->format('d/m/Y'),
            default => 'Annee scolaire ' . ($filters['selectedYear']?->label ?? '-'),
        };
    }
    public function insolvables(Request $request)
    {
        return view('finances.insolvables', $this->insolvencyContext($request, true));
    }

    public function createManualInsolvable(Request $request)
    {
        $selectedYear = $request->filled('year_id')
            ? AcademicYear::find($request->integer('year_id'))
            : AcademicYear::active();

        return view('finances.insolvables-create', [
            'selectedYear' => $selectedYear,
            'years' => AcademicYear::orderByDesc('start_date')->get(),
        ]);
    }

    public function searchEnrollments(Request $request)
    {
        $data = $request->validate([
            'year_id' => ['required', 'exists:academic_years,id'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $query = StudentEnrollment::query()
            ->where('academic_year_id', $data['year_id'])
            ->with(['student', 'classGroup.level.section']);

        if (AcademicYear::active()?->id === (int) $data['year_id']) {
            $query->where('status', 'active');
        }

        $term = trim((string) ($data['q'] ?? ''));
        if ($term !== '') {
            $query->whereHas('student', fn ($student) => $student
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('matricule', 'like', "%{$term}%"));
        }

        return response()->json($query->orderByDesc('id')->limit(15)->get()->map(fn ($enrollment) => [
            'id' => $enrollment->id,
            'label' => $enrollment->student->full_name . ' - ' . $enrollment->student->matricule,
            'name' => $enrollment->student->full_name,
            'matricule' => $enrollment->student->matricule,
            'class' => $enrollment->classGroup?->full_name,
            'section' => $enrollment->classGroup?->level?->section?->name,
        ])->values());
    }

    public function enrollmentInstallments(StudentEnrollment $enrollment)
    {
        $enrollment->load('classGroup.feeStructures.installments');
        $structure = $enrollment->classGroup?->feeStructures->first();
        $installments = $structure?->installments->sortBy('installment_number')->map(fn ($installment) => [
            'id' => $installment->id,
            'label' => $installment->label,
            'amount' => (int) $installment->amount,
        ])->values() ?? collect();

        return response()->json([
            'installments' => $installments,
            'fee_total' => (int) $installments->sum('amount'),
        ]);
    }

    public function storeManualInsolvable(Request $request)
    {
        $data = $request->validate([
            'student_enrollment_id' => ['required', 'exists:student_enrollments,id'],
            'year_id' => ['nullable', 'exists:academic_years,id'],
            'total_due' => ['nullable', 'numeric', 'min:0'],
            'total_paid' => ['required', 'numeric', 'min:0'],
            'selected_installments' => ['nullable', 'array'],
            'selected_installments.*' => ['integer', 'exists:fee_installments,id'],
        ]);

        $enrollment = StudentEnrollment::with('classGroup.feeStructures.installments')->findOrFail($data['student_enrollment_id']);
        $totalDue = isset($data['total_due']) ? (int) round($data['total_due']) : (int) ($enrollment->classGroup?->feeStructures->first()?->installments->sum('amount') ?? 0);
        $totalPaid = (int) round($data['total_paid']);

        if ($totalPaid > $totalDue) {
            return back()->withInput()->with('error', 'Le montant paye ne peut pas depasser le total des frais.');
        }

        if (ManualInsolvable::where('student_enrollment_id', $enrollment->id)->exists()) {
            return back()->withInput()->with('warning', 'Un insolvable manuel existe deja pour cette inscription.');
        }

        ManualInsolvable::create([
            'student_enrollment_id' => $enrollment->id,
            'academic_year_id' => $data['year_id'] ?? $enrollment->academic_year_id,
            'total_due' => $totalDue,
            'total_paid' => $totalPaid,
            'remaining' => max(0, $totalDue - $totalPaid),
            'selected_installments' => $data['selected_installments'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        return redirect()->route('finances.insolvables')->with('success', 'Eleve ajoute au registre des insolvables.');
    }

    public function payManualInsolvable(Request $request)
    {
        $data = $request->validate([
            'manual_insolvable_id' => ['required', 'exists:manual_insolvables,id'],
            'student_enrollment_id' => ['required', 'exists:student_enrollments,id'],
            'amount_paid' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,orange_money,mtn_momo,bank_transfer,other'],
        ]);

        $manual = ManualInsolvable::findOrFail($data['manual_insolvable_id']);
        abort_unless($manual->student_enrollment_id === (int) $data['student_enrollment_id'], 422);
        $amount = (int) round($data['amount_paid']);
        $remaining = (int) $manual->remaining;

        if ($amount > $remaining) {
            return back()->with('error', 'Le montant saisi depasse le restant du registre des insolvables.');
        }

        $newPaid = (int) $manual->total_paid + $amount;
        $newRemaining = max(0, (int) $manual->total_due - $newPaid);
        $payment = StudentPayment::create([
            'student_enrollment_id' => $manual->student_enrollment_id,
            'fee_installment_id' => null,
            'amount_paid' => $amount,
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'receipt_number' => StudentPayment::generateReceiptNumber(),
            'recorded_by' => Auth::id(),
            'notes' => 'Paiement manuel d\'un insolvable',
            'snapshot_total_due' => (int) $manual->total_due,
            'snapshot_total_paid' => $newPaid,
            'snapshot_total_remaining' => $newRemaining,
        ]);

        $manual->update(['total_paid' => $newPaid, 'remaining' => $newRemaining]);
        AuditLog::log('manual_insolvable_payment', $payment, [], ['manual_insolvable_id' => $manual->id, 'paid_amount' => $amount]);

        return redirect()->route('finances.receipt', $payment)->with('success', 'Paiement enregistre.');
    }

    public function printInsolvables(Request $request)
    {
        $context = $this->insolvencyContext($request);
        $context['school'] = \App\Models\SchoolSetting::instance();
        $context['phones'] = \App\Models\SchoolPhone::orderBy('id')->get();
        $context['agreements'] = \App\Models\SchoolAgreement::orderBy('id')->get();

        return view('finances.insolvables-print', $context);
    }

    private function insolvencyContext(Request $request, bool $paginate = false): array
    {
        $activeYear = AcademicYear::active();
        $selectedYear = $request->filled('year_id')
            ? AcademicYear::find($request->integer('year_id'))
            : $activeYear;
        $selectedSectionId = $request->filled('section_id') ? $request->integer('section_id') : null;
        $selectedClassId = $request->filled('class_id') ? $request->integer('class_id') : null;
        $selectedInstallmentLabel = $request->filled('installment_label')
            ? trim((string) $request->input('installment_label'))
            : null;

        $years = AcademicYear::orderByDesc('start_date')->get();
        $sections = Section::orderBy('name')->get();
        $classes = ClassGroup::query()
            ->when($selectedYear, fn ($query) => $query->where('academic_year_id', $selectedYear->id))
            ->when($selectedSectionId, fn ($query) => $query->whereHas('level', fn ($level) =>
                $level->where('section_id', $selectedSectionId)
            ))
            ->with(['level.section', 'feeStructures.installments'])
            ->orderBy('name')
            ->get();

        if ($selectedClassId && ! $classes->contains('id', $selectedClassId)) {
            $selectedClassId = null;
        }

        $installmentLabels = $classes
            ->flatMap(fn ($class) => $class->feeStructures->flatMap(fn ($structure) => $structure->installments))
            ->pluck('label')->filter()->unique()->sort()->values();

        if ($selectedInstallmentLabel && ! $installmentLabels->contains($selectedInstallmentLabel)) {
            $selectedInstallmentLabel = null;
        }

        $installmentLabel = $selectedInstallmentLabel ?: 'Toutes les tranches';
        $rows = $this->buildInsolvencyRows($selectedYear, $selectedSectionId, $selectedClassId, $selectedInstallmentLabel);
        $summary = [
            'count' => $rows->count(),
            'due' => $rows->sum('total_due'),
            'paid' => $rows->sum('total_paid'),
            'remaining' => $rows->sum('remaining'),
            'unpaid_count' => $rows->where('status', 'unpaid')->count(),
        ];

        $isPaginated = $paginate;
        if ($isPaginated) {
            $rows = $this->paginateInsolvencyRows($rows, $request);
        }

        return compact('selectedYear', 'selectedSectionId', 'selectedClassId', 'years', 'sections', 'classes', 'installmentLabels', 'selectedInstallmentLabel', 'installmentLabel', 'isPaginated', 'rows', 'summary');
    }

    private function paginateInsolvencyRows($rows, Request $request)
    {
        $perPage = 30;
        $page = max(1, $request->integer('page', 1));

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    private function buildInsolvencyRows(?AcademicYear $selectedYear, ?int $sectionId = null, ?int $classId = null, ?string $installmentLabel = null)
    {
        if (! $selectedYear) {
            return collect();
        }

        $enrollments = StudentEnrollment::query()
            ->where('status', 'active')
            ->where('academic_year_id', $selectedYear->id)
            ->when($sectionId, fn ($query) => $query->whereHas('classGroup.level', fn ($level) => $level->where('section_id', $sectionId)))
            ->when($classId, fn ($query) => $query->where('class_group_id', $classId))
            ->with(['student', 'classGroup.level.section', 'classGroup.feeStructures.installments'])
            ->get();

        $manualEnrollmentIds = ManualInsolvable::where('academic_year_id', $selectedYear->id)
            ->pluck('student_enrollment_id');
        if ($manualEnrollmentIds->isNotEmpty()) {
            $manualEnrollments = StudentEnrollment::with(['student', 'classGroup.level.section', 'classGroup.feeStructures.installments'])
                ->whereIn('id', $manualEnrollmentIds)
                ->when($sectionId, fn ($query) => $query->whereHas('classGroup.level', fn ($level) => $level->where('section_id', $sectionId)))
                ->when($classId, fn ($query) => $query->where('class_group_id', $classId))
                ->get();
            $enrollments = $enrollments->merge($manualEnrollments)->unique('id')->values();
        }

        if ($enrollments->isEmpty()) return collect();

        $enrollmentIds = $enrollments->pluck('id');
        $paymentsByEnrollment = StudentPayment::query()->whereIn('student_enrollment_id', $enrollmentIds)->get()->groupBy('student_enrollment_id');
        $visiblePayments = StudentPayment::visible()->whereIn('student_enrollment_id', $enrollmentIds)->get()->groupBy('student_enrollment_id');

        $rows = $enrollments->map(function (StudentEnrollment $enrollment) use ($paymentsByEnrollment, $visiblePayments, $installmentLabel) {
            $installments = $enrollment->classGroup?->feeStructures->first()?->installments->sortBy('installment_number') ?? collect();
            $paymentsByInstallment = $paymentsByEnrollment->get($enrollment->id, collect())
                ->filter(fn ($payment) => ! is_null($payment->fee_installment_id))
                ->groupBy('fee_installment_id');
            $scopedInstallments = $installmentLabel ? $installments->where('label', $installmentLabel) : $installments;
            $totalDue = (int) $scopedInstallments->sum('amount');
            $totalPaid = $installmentLabel
                ? (int) $scopedInstallments->sum(fn ($installment) => $paymentsByInstallment->get($installment->id, collect())->sum(fn ($payment) => (int) $payment->amount_paid + (int) $payment->scholarship_amount))
                : (int) $visiblePayments->get($enrollment->id, collect())->sum(fn ($payment) => (int) $payment->amount_paid + (int) $payment->scholarship_amount);
            $remaining = max(0, $totalDue - $totalPaid);
            $remainingFees = $installmentLabel ? collect() : $installments->map(function ($installment) use ($paymentsByInstallment) {
                $paid = (int) $paymentsByInstallment->get($installment->id, collect())->sum(fn ($payment) => (int) $payment->amount_paid + (int) $payment->scholarship_amount);
                $remaining = max(0, (int) $installment->amount - $paid);
                return $remaining > 0 ? ['label' => $installment->label, 'remaining' => $remaining] : null;
            })->filter()->values();

            return [
                'enrollment' => $enrollment,
                'total_due' => $totalDue,
                'total_paid' => $totalPaid,
                'remaining' => $remaining,
                'remaining_fees' => $remainingFees,
                'status' => $totalPaid <= 0 ? 'unpaid' : 'partial',
            ];
        })->filter(fn (array $row) => $row['remaining'] > 0)->values();

        $manuals = ManualInsolvable::with('enrollment.student')
            ->where('academic_year_id', $selectedYear->id)
            ->when($sectionId, fn ($query) => $query->whereHas('enrollment.classGroup.level', fn ($level) => $level->where('section_id', $sectionId)))
            ->when($classId, fn ($query) => $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('class_group_id', $classId)))
            ->when($installmentLabel, fn ($query) => $query->whereHas('enrollment.classGroup.feeStructures.installments', fn ($installment) => $installment->where('label', $installmentLabel)))
            ->get();

        foreach ($manuals as $manual) {
            if (! $manual->enrollment || (int) $manual->remaining <= 0) continue;
            $existingIndex = $rows->search(fn ($row) => $row['enrollment']->id === $manual->student_enrollment_id);
            $manualRow = [
                'enrollment' => $manual->enrollment,
                'total_due' => (int) $manual->total_due,
                'total_paid' => (int) $manual->total_paid,
                'remaining' => (int) $manual->remaining,
                'remaining_fees' => collect(),
                'status' => (int) $manual->total_paid <= 0 ? 'unpaid' : 'partial',
                'manual' => true,
                'manual_note' => $manual->note,
                'manual_id' => $manual->id,
            ];
            if ($existingIndex === false) $rows->push($manualRow);
            else $rows->put($existingIndex, $manualRow);
        }

        return $rows->sortBy(fn (array $row) => mb_strtoupper($row['enrollment']->student->full_name), SORT_NATURAL)->values();
    }

    public function global(Request $request)
    {
        $activeYear     = AcademicYear::active();
        $selectedYearId = $request->input('year_id', $activeYear?->id);
        $selectedYear   = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : null;

        $years = AcademicYear::orderByDesc('start_date')->get();

        // ── Effectif total des élèves actifs pour l'année sélectionnée
        $totalEnrolled = 0;
        if ($selectedYear) {
            $totalEnrolled = StudentEnrollment::where('academic_year_id', $selectedYear->id)
                ->where('status', 'active')
                ->count();
        }

        // ── Stats par classe
        $classeStats = collect();
        if ($selectedYear) {
            $classes = ClassGroup::where('academic_year_id', $selectedYear->id)
                ->with([
                    'level.section',
                    'feeStructures.installments',
                ])
                ->withCount([
                    'studentEnrollments as enrolled_count' => fn($q) =>
                        $q->where('status', 'active'),
                ])
                ->get();

            foreach ($classes as $class) {
                $fee       = $class->feeStructures->first();
                $feeTotal  = $fee?->installments->sum('amount') ?? 0;
                $expected  = $feeTotal * $class->enrolled_count;
                $paidAmount = \App\Models\StudentPayment::visible()->whereHas(
                    'studentEnrollment', fn($q) =>
                        $q->where('class_group_id', $class->id)
                        ->where('status', 'active')
                )->sum('amount_paid');
                $scholarshipAmount = \App\Models\StudentPayment::visible()->whereHas(
                    'studentEnrollment', fn($q) =>
                        $q->where('class_group_id', $class->id)
                        ->where('status', 'active')
                )->sum('scholarship_amount');
                $collected = $paidAmount + $scholarshipAmount;

                $classeStats->push([
                    'class'     => $class,
                    'expected'  => $expected,
                    'collected' => $collected,
                    'remaining' => max(0, $expected - $collected),
                    'rate'      => $expected > 0
                        ? round(($collected / $expected) * 100) : 0,
                ]);
            }
        }

        // ── Stats par mode de paiement
        $paymentMethods = \App\Models\StudentPayment::visible()->selectRaw(
            'payment_method, SUM(amount_paid) as total, COUNT(*) as count'
        )
        ->when($selectedYear, fn($q) =>
            $q->whereHas('studentEnrollment', fn($q2) =>
                $q2->where('academic_year_id', $selectedYear->id)
            )
        )
        ->groupBy('payment_method')
        ->get();

        // ── Stats par tranche (agrégées par libellé, toutes classes confondues)
        $installmentStats = $this->buildInstallmentFinancialStats($selectedYear);
        $debtors = \App\Models\StudentEnrollment::where('status', 'active')
            ->when($selectedYear, fn($q) =>
                $q->where('academic_year_id', $selectedYear->id)
            )
            ->with([
                'student',
                'classGroup.level.section',
                'classGroup.feeStructures.installments',
            ])
            ->get()
            ->map(function($e) {
                $fee      = $e->classGroup->feeStructures->first();
                $due      = $fee?->installments->sum('amount') ?? 0;
                $paidAmount = \App\Models\StudentPayment::visible()->where(
                    'student_enrollment_id', $e->id
                )->sum('amount_paid');
                $scholarshipAmount = \App\Models\StudentPayment::visible()->where(
                    'student_enrollment_id', $e->id
                )->sum('scholarship_amount');
                $paid = $paidAmount + $scholarshipAmount;
                $remaining = max(0, $due - $paid);
                return ['enrollment' => $e, 'due' => $due,
                        'paid' => $paid, 'remaining' => $remaining];
            })
            ->filter(fn($e) => $e['remaining'] > 0)
            ->sortByDesc('remaining')
            ->values();

        // ── Totaux globaux
        $globalStats = [
            'expected'  => $classeStats->sum('expected'),
            'collected' => $classeStats->sum('collected'),
            'remaining' => $classeStats->sum('remaining'),
            'rate'      => $classeStats->sum('expected') > 0
                ? round(($classeStats->sum('collected')
                    / $classeStats->sum('expected')) * 100)
                : 0,
            'debtors'   => $debtors->count(),
        ];

        // ── Bourses accordées
        $totalScholarships = \App\Models\StudentPayment::visible()
            ->when($selectedYear, fn($q) =>
                $q->whereHas('studentEnrollment', fn($q2) =>
                    $q2->where('academic_year_id', $selectedYear->id)
                )
            )
            ->sum('scholarship_amount');

        // ── Calcul du taux d'élèves à jour
        $debtorsCount = $debtors->count();
        $paidInFullCount = max(0, $totalEnrolled - $debtorsCount);
        $paidInFullRate = $totalEnrolled > 0 ? round(($paidInFullCount / $totalEnrolled) * 100) : 0;

        // ── Stats par section
        $sectionStats = collect();
        $sections = Section::all();
        foreach ($sections as $sec) {
            $sectionStats->put($sec->id, [
                'section' => $sec,
                'expected' => 0,
                'collected' => 0,
            ]);
        }
        foreach ($classeStats as $row) {
            $secId = $row['class']->level->section_id;
            if ($sectionStats->has($secId)) {
                $current = $sectionStats->get($secId);
                $current['expected'] += $row['expected'];
                $current['collected'] += $row['collected'];
                $sectionStats->put($secId, $current);
            }
        }
        $sectionStats = $sectionStats->map(function($item) {
            $expected = $item['expected'];
            $collected = $item['collected'];
            $remaining = max(0, $expected - $collected);
            $rate = $expected > 0 ? round(($collected / $expected) * 100) : 0;
            return (object) array_merge($item, [
                'remaining' => $remaining,
                'rate' => $rate,
            ]);
        });

        // ── Encaissements du jour
        $todayPaymentsCount = StudentPayment::visible()->whereDate('payment_date', today())->count();
        $todayPaymentsAmount = StudentPayment::visible()->whereDate('payment_date', today())->get()->sum(fn ($p) => (int) $p->amount_paid + (int) $p->scholarship_amount);
        $lastPaymentTime = StudentPayment::visible()->orderByDesc('created_at')->first()?->created_at?->format('H:i') ?? '--:--';

        // ── Paiements récents
        $recentPayments = StudentPayment::visible()->with([
            'studentEnrollment.student',
            'studentEnrollment.classGroup.level.section',
            'feeInstallment',
        ])
        ->when($selectedYear, fn($q) =>
            $q->whereHas('studentEnrollment', fn($q2) =>
                $q2->where('academic_year_id', $selectedYear->id)
            )
        )
        ->orderByDesc('created_at')
        ->take(5)
        ->get();

        // ── Évolution mensuelle (période de l'année scolaire)
        $recentScholarships = StudentPayment::visible()->with([
            'studentEnrollment.student',
            'studentEnrollment.classGroup.level.section',
            'feeInstallment',
            'recordedBy',
        ])
        ->where('scholarship_amount', '>', 0)
        ->when($selectedYear, fn($q) =>
            $q->whereHas('studentEnrollment', fn($q2) =>
                $q2->where('academic_year_id', $selectedYear->id)
            )
        )
        ->orderByDesc('payment_date')
        ->orderByDesc('created_at')
        ->take(10)
        ->get();
        $monthlyData = collect();

        if ($selectedYear) {
            $yearPayments = StudentPayment::visible()->when($selectedYear, fn ($q) =>
                $q->whereHas('studentEnrollment', fn ($q2) =>
                    $q2->where('academic_year_id', $selectedYear->id)
                )
            )->get();

            foreach ($selectedYear->monthPeriods() as $period) {
                $total = $yearPayments
                    ->filter(fn ($p) =>
                        (int) $p->payment_date->format('n') === $period['month']
                        && (int) $p->payment_date->format('Y') === $period['year']
                    )
                    ->sum(fn ($p) => (int) $p->amount_paid + (int) $p->scholarship_amount);

                $monthlyData->push((object) [
                    'label'      => $period['label'],
                    'full_label' => $period['full_label'],
                    'total'      => (float) $total,
                ]);
            }
        }

        return view('finances.global', compact(
            'selectedYear', 'years', 'classeStats', 'totalEnrolled',
            'paymentMethods', 'installmentStats', 'debtors', 'globalStats',
            'paidInFullRate', 'sectionStats', 'todayPaymentsCount',
            'todayPaymentsAmount', 'lastPaymentTime', 'recentPayments',
            'monthlyData', 'totalScholarships', 'recentScholarships'
        ));
    }
}
