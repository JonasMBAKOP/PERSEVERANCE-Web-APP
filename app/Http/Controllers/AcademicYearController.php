<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\Trimester;
use App\Models\Sequence;
use App\Models\AuditLog;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use App\Models\ClassGroup;
use App\Models\ClassSubject;
use App\Models\FeeInstallment;
use App\Models\FeeStructure;
use App\Models\GradeLock;
use App\Services\EnrollmentService;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments
    ) {}
    // ── ANNÉES SCOLAIRES — AFFICHAGE ───────────────────────────────────────
    public function index()
    {
        $years = AcademicYear::withCount([
            /* 'sequences', */
            'classGroups',
            'studentEnrollments',
        ])
        ->with(['trimesters.sequences'])
        ->orderByDesc('start_date')
        ->get();

        // Année active séparée
        $activeYear    = $years->where('is_active', true)->first();
        $otherYears    = $years->where('is_active', false)->values();

        // Séquence en cours (première séquence non verrouillée)
        $currentSequence = null;

        if ($activeYear) {
            $currentSequence = Sequence::where('academic_year_id', $activeYear->id)
                ->where('is_grades_locked', false)
                ->orderBy('number')
                ->first();
        }

        // Nombre de sections par année (via les classes)
        $sectionsCount = \App\Models\Section::count();

        // Données de croissance (5 dernières années) — effectifs annuels par année scolaire
        $growthData = AcademicYear::withCount('studentEnrollments')
            ->orderBy('start_date')
            ->get()
            ->map(fn($y) => [
                'label' => $y->label,
                'count' => $y->student_enrollments_count,
                'is_active' => $y->is_active,
                'status' => $y->is_active
                    ? 'Active'
                    : ($y->end_date < now() ? 'Clôturée' : 'En préparation'),
            ]);

        // Taux de croissance
        $growthRate = null;
        if ($growthData->count() >= 2) {
            $first = $growthData->first()['count'];
            $last  = $growthData->last()['count'];
            if ($first > 0) {
                $growthRate = round((($last - $first) / $first) * 100, 1);
            }
        }

        // Moyenne générale de l'année active
        $overallAverage = null;
        if ($activeYear) {
            $avg = \App\Models\Grade::whereHas('sequence', fn($q) =>
                $q->where('academic_year_id', $activeYear->id)
            )->whereNotNull('grade')->avg('grade');
            $overallAverage = $avg ? number_format((float)$avg, 2) : null;
        }

        // Dernière séquence validée pour l'average info
        $lastLockedSequence = null;
        if ($activeYear) {
            $lastLockedSequence = Sequence::where('academic_year_id', $activeYear->id)
                ->where('is_grades_locked', true)
                ->orderByDesc('number')
                ->first();
        }

        return view('academic-years.index', compact(
            'years', 'activeYear', 'otherYears',
            'currentSequence', 'sectionsCount',
            'growthData', 'growthRate',
            'overallAverage', 'lastLockedSequence'
        ));
    }

    // ── ANNÉES SCOLAIRES — CRÉATION ────────────────────────────────────────
    public function create()
    {
        $previousYears = AcademicYear::withCount('classGroups')
            ->orderByDesc('start_date')
            ->get();

        // Suggestion automatique du libellé
        $suggestedLabel = $this->suggestNextLabel();

        return view('academic-years.create',
            compact('previousYears', 'suggestedLabel'));
    }

    // ── ENREGISTREMENT ────────────────────────────────────────────────────
    public function store(StoreAcademicYearRequest $request)
    {
        // Créer l'année scolaire
        $year = DB::transaction(function () use ($request) {
            $year = AcademicYear::create([
                'label'      => $request->label,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'is_active'  => false,
            ]);

        // Auto-générer les 3 trimestres et 6 séquences
            $this->generateCalendar($year, $request);

        // Copie depuis une année précédente
            if ($request->filled('copy_from')) {
                $sourceYear = AcademicYear::find($request->copy_from);

                if ($sourceYear && $request->boolean('copy_classes', true)) {
                    $this->copyClassesFrom($year, $sourceYear,
                        $request->boolean('copy_subjects'),
                        $request->boolean('copy_fees'));
                }
            }

            AuditLog::log('created', $year, [], $year->toArray());

            return $year;
        });

        return redirect()
            ->route('academic-years.index')
            ->with('success',
                "Année scolaire {$year->label} créée avec succès.");
    }

    // // ── DÉTAIL ────────────────────────────────────────────────────────────
    // ── VUE DÉTAILS (lecture seule) ────────────────────────────────────────────────────────────
    public function show(AcademicYear $academicYear)
    {
        // $academicYear->load([
        //     'trimesters.sequences.gradeLocks',
        // ]);

        $academicYear->load(['trimesters.sequences']);

        $stats = [
            'classes'  => $academicYear->classGroups()->count(),
            'students' => $academicYear->studentEnrollments()->count(),
            'grades'   => \App\Models\Grade::whereHas('sequence', fn($q) =>
                              $q->where('academic_year_id', $academicYear->id)
                          )->where(fn($q) => $q->whereNotNull('grade')->orWhere('is_absent', true))->count(),
        ];

        $associated = [
            'sections' => \App\Models\Section::whereHas('levels.classGroups', fn($q) =>
                $q->where('academic_year_id', $academicYear->id)
            )->count(),
            'enrollments' => $academicYear->studentEnrollments()->count(),
            'bulletins' => \App\Models\BulletinReport::whereHas('sequence', fn($q) =>
                $q->where('academic_year_id', $academicYear->id)
            )->count(),
            'payments_count' => \App\Models\StudentPayment::visible()
                ->whereHas('studentEnrollment', fn($q) =>
                    $q->where('academic_year_id', $academicYear->id)
                )->count(),
            'payments_amount' => \App\Models\StudentPayment::visible()
                ->whereHas('studentEnrollment', fn($q) =>
                    $q->where('academic_year_id', $academicYear->id)
                )->sum('amount_paid'),
        ];

        return view('academic-years.show',
            compact('academicYear', 'stats', 'associated'));
    }

    // ── ANNÉES SCOLAIRES — MODIFICATION ────────────────────────────────────
    // public function update(UpdateAcademicYearRequest $request,
    //                        AcademicYear $academicYear)
    // {
    //     $old = $academicYear->toArray();
    //     $academicYear->update($request->validated());
    //     AuditLog::log('updated', $academicYear, $old, $academicYear->toArray());

    //     return back()->with('success', 'Année scolaire mise à jour.');
    // }
    public function edit(AcademicYear $academicYear)
    {
        // Année clôturée = non modifiable
        if ($academicYear->isClosed()) {
            return redirect()
                ->route('academic-years.show', $academicYear)
                ->with('error',
                    'Cette année est clôturée et ne peut plus être modifiée.');
        }

        $academicYear->load(['trimesters.sequences']);
        return view('academic-years.edit', compact('academicYear'));
    }

    // ── ENREGISTREMENT UNIFIÉ (libellé + dates année + dates séquences) ───
    public function updateAll(Request $request, AcademicYear $academicYear)
    {
        // Protection clôture
        if ($academicYear->isClosed()) {
            return back()->with('error',
                'Cette année est clôturée et ne peut plus être modifiée.');
        }

        $request->validate([
            'label'               => ['required', 'string', 'max:20',
                                      "unique:academic_years,label,{$academicYear->id}",
                                      'regex:/^\d{4}-\d{4}$/'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['required', 'date', 'after:start_date'],
            'sequences.*.label'   => ['required', 'string', 'max:50'],
            'sequences.*.start'   => ['nullable', 'date'],
            'sequences.*.end'     => ['nullable', 'date'],
        ], [
            'label.regex'    => 'Format attendu : AAAA-AAAA (ex: 2025-2026).',
            'label.unique'   => 'Ce libellé existe déjà.',
            'end_date.after' => 'La date de fin doit être après le début.',
        ]);

        $old = $academicYear->toArray();

        // 1. Mettre à jour l'année
        $academicYear->update([
            'label'      => $request->label,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        // 2. Mettre à jour les séquences et recalculer les trimestres
        foreach ($request->input('sequences', []) as $seqId => $dates) {
            $sequence = Sequence::find($seqId);
            if (!$sequence ||
                $sequence->academicYear->id !== $academicYear->id) {
                continue;
            }

            $sequence->update([
                'label'      => $dates['label'],
                'start_date' => $dates['start'] ?: null,
                'end_date'   => $dates['end']   ?: null,
            ]);

            // Recalcul automatique des dates du trimestre parent
            $this->recalculateTrimesterDates($sequence->trimester);
        }

        AuditLog::log('updated', $academicYear, $old, $academicYear->toArray());

        return redirect()
            ->route('academic-years.show', $academicYear)
            ->with('success',
                'Modifications de l\'année '
                . $academicYear->label
                . ' enregistrées.');
    }

    // ── ACTIVATION ────────────────────────────────────────────────────────
    public function activate(AcademicYear $academicYear)
    {
        if (false && $academicYear->isClosed()) {
            return back()->with('error',
                'Impossible d\'activer une année clôturée.');
        }

        if ($academicYear->is_active) {
            return back()->with('error',
                'Cette année est déjà active.');
        }

        $finalizedCount = 0;
        $reactivatedCount = 0;

        DB::transaction(function () use ($academicYear, &$finalizedCount, &$reactivatedCount) {
            $previouslyActive = AcademicYear::active();

            $academicYear->activate();
            $reactivatedCount = $this->enrollments
                ->reactivateYearEnrollments($academicYear);

            if ($previouslyActive && $previouslyActive->id !== $academicYear->id) {
                $finalizedCount = $this->enrollments
                    ->finalizeYearEnrollments($previouslyActive);
            }
        });

        AuditLog::log('activated', $academicYear);

        $message = "Année {$academicYear->label} activée. "
            . "Elle est maintenant l'année scolaire en cours.";

        if ($finalizedCount > 0) {
            $message .= " {$finalizedCount} inscription(s) de l'année précédente "
                . "ont été clôturées — renouvelez les élèves pour la nouvelle année.";
        }

        if ($reactivatedCount > 0) {
            $message .= " {$reactivatedCount} inscription(s) de cette annee ont ete reactivees.";
        }

        return back()->with('success', $message);
    }

    // ── CLÔTURE ───────────────────────────────────────────────────────────
    public function close(AcademicYear $academicYear)
    {
        if (!$academicYear->is_active) {
            return back()->with('error',
                'Seule l\'année active peut être clôturée.');
        }

        $finalizedCount = 0;

        DB::transaction(function () use ($academicYear, &$finalizedCount) {
            $finalizedCount = $this->enrollments
                ->finalizeYearEnrollments($academicYear);

            $academicYear->update([
                'is_active' => false,
                'is_locked' => false,
            ]);

            $reactivatedCount = $this->enrollments
                ->reactivateYearEnrollments($academicYear);
        });

        AuditLog::log('closed', $academicYear);

        return back()->with('success',
            "Année {$academicYear->label} clôturée. "
            . "{$finalizedCount} inscription(s) passée(s) en attente de renouvellement. "
            . "Les données sont conservées en lecture seule.");
    }

    // ── MISE À JOUR D'UN TRIMESTRE ────────────────────────────────────────
    public function updateTrimester(Request $request, Trimester $trimester)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date',
                             'after_or_equal:start_date'],
        ]);

        $trimester->update($request->only('start_date', 'end_date'));

        return back()->with('success',
            "{$trimester->label} mis à jour.");
    }

    // // ── MISE À JOUR D'UNE SÉQUENCE ────────────────────────────────────────
    // public function updateSequence(Request $request, Sequence $sequence)
    // {
    //     $request->validate([
    //         'start_date' => ['nullable', 'date'],
    //         'end_date'   => ['nullable', 'date',
    //                          'after_or_equal:start_date'],
    //     ]);

    //     $sequence->update($request->only('start_date', 'end_date'));

    //     return back()->with('success',
    //         "{$sequence->label} mise à jour.");
    // }

    // ── VERROUILLAGE GLOBAL D'UNE SÉQUENCE ───────────────────────────────
    public function toggleSequenceLock(Sequence $sequence)
    {
        // Protection clôture
        if ($sequence->academicYear->isClosed()) {
            return back()->with('error',
                'Cette année est clôturée. Impossible de modifier les verrous.');
        }

        $sequence->update([
            'is_grades_locked' => !$sequence->is_grades_locked,
        ]);

        $status = $sequence->is_grades_locked
            ? 'verrouillée' : 'déverrouillée';

        AuditLog::log(
            $sequence->is_grades_locked
                ? 'sequence_locked' : 'sequence_unlocked',
            $sequence
        );

        return back()->with('success',
            "{$sequence->label} {$status} pour toutes les classes.");
    }

    // ── MÉTHODES PRIVÉES ──────────────────────────────────────────────────

    // Recalcule les dates d'un trimestre depuis ses séquences
    private function recalculateTrimesterDates(Trimester $trimester): void
    {
        $sequences = $trimester->sequences()
                               ->orderBy('number')
                               ->get();

        // start_date = date de début de la 1ère séquence du trimestre
        $startDate = $sequences->whereNotNull('start_date')
                               ->sortBy('number')
                               ->first()?->start_date;

        // end_date = date de fin de la dernière séquence du trimestre
        $endDate = $sequences->whereNotNull('end_date')
                             ->sortByDesc('number')
                             ->first()?->end_date;

        $trimester->update([
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
    }

    // Génère automatiquement les trimestres et séquences
    private function generateCalendar(
        AcademicYear $year, Request $request): void
    {
        $trimesters = [
            1 => 'Trimestre 1',
            2 => 'Trimestre 2',
            3 => 'Trimestre 3',
        ];

        $sequenceConfig = $this->defaultSequenceConfig();

        foreach ($trimesters as $num => $label) {
            // // $trimData = $request->input("trimesters.{$num}", []);

            $trimester = Trimester::create([
                'academic_year_id' => $year->id,
                'number'           => $num,
                'label'            => $label,
                'start_date'       => null,
                'end_date'         => null,
            ]);

            foreach ($sequenceConfig[$num] as $seqNum => $defaultLabel) {
                $seqData = $request->input("sequences.{$seqNum}", []);

                Sequence::create([
                    'academic_year_id' => $year->id,
                    'trimester_id'     => $trimester->id,
                    'number'           => $seqNum,
                    'label'            => $seqData['label'] ?? $defaultLabel,
                    'start_date' => $seqData['start_date'] ?? null,
                    'end_date'   => $seqData['end_date']   ?? null,
                    'is_grades_locked' => false,
                ]);
            }

            // Recalcul après création des séquences
            $this->recalculateTrimesterDates($trimester);
        }
    }

    // Copie les classes d'une année vers une autre
    private function defaultSequenceConfig(): array
    {
        return AcademicYear::sequenceCalendar();
    }

    private function copyClassesFrom(
        AcademicYear $target,
        AcademicYear $source,
        bool $copySubjects,
        bool $copyFees): void
    {
        $sourceClasses = ClassGroup::where('academic_year_id', $source->id)
                                   ->with(['classSubjects', 'level'])
                                   ->get();

        foreach ($sourceClasses as $sourceClass) {
            $series = '';
            $subGroup = trim((string) $sourceClass->sub_group);
            $name = ClassGroup::composeName($sourceClass->level?->name ?? '', null, $subGroup);

            $newClass = ClassGroup::updateOrCreate([
                'academic_year_id' => $target->id,
                'level_id'         => $sourceClass->level_id,
                'series'           => $series,
                'sub_group'        => $subGroup,
            ], [
                'name'              => $name,
                'max_students'      => $sourceClass->max_students,
                'titular_staff_id'  => $sourceClass->titular_staff_id,
                'room'              => $sourceClass->room,
            ]);

            if ($copySubjects) {
                foreach ($sourceClass->classSubjects as $cs) {
                    $newCs = ClassSubject::updateOrCreate([
                        'class_group_id' => $newClass->id,
                        'subject_id'     => $cs->subject_id,
                    ], [
                        'coefficient'    => $cs->coefficient,
                        'hours_per_week' => $cs->hours_per_week,
                        'is_active'      => $cs->is_active,
                    ]);
                }
            }

            if ($copyFees) {
                $sourceFee = FeeStructure::where([
                    'academic_year_id' => $source->id,
                    'class_group_id'   => $sourceClass->id,
                ])->with('installments')->first();

                if ($sourceFee) {
                    $newFee = FeeStructure::updateOrCreate([
                        'academic_year_id' => $target->id,
                        'class_group_id'   => $newClass->id,
                    ], [
                        'total_amount'     => $sourceFee->total_amount,
                    ]);

                    foreach ($sourceFee->installments as $inst) {
                        FeeInstallment::updateOrCreate([
                            'fee_structure_id'   => $newFee->id,
                            'installment_number' => $inst->installment_number,
                        ], [
                            'label'              => $inst->label,
                            'amount'             => $inst->amount,
                            'due_date_start'     => $inst->due_date_start,
                            'due_date_end'       => $inst->due_date_end,
                        ]);
                    }
                }
            }
        }
    }

    // Suggère le libellé de la prochaine année
    private function suggestNextLabel(): string
    {
        $last = AcademicYear::orderByDesc('start_date')->first();
        if (!$last) {
            $year = (int) date('Y');
            return "{$year}-" . ($year + 1);
        }
        [$start, $end] = explode('-', $last->label);
        return ($start + 1) . '-' . ($end + 1);
    }

    // ── ANNÉES SCOLAIRES — SUPPRESSION (uniquement si en préparation et sans données) ────────
    public function destroy(AcademicYear $academicYear)
    {
        // Protection 1 : pas d'année active
        if ($academicYear->is_active) {
            return back()->with('error',
                'Impossible de supprimer l\'année active.');
        }

        // Protection 2 : pas d'année clôturée
        if ($academicYear->isClosed()) {
            return back()->with('error',
                'Impossible de supprimer une année clôturée.');
        }

        // Protection 3 : pas de données liées
        if ($academicYear->classGroups()->count() > 0) {
            return back()->with('error',
                'Impossible de supprimer cette année : elle contient des classes.');
        }

        if ($academicYear->studentEnrollments()->count() > 0) {
            return back()->with('error',
                'Impossible de supprimer cette année : elle contient des inscriptions.');
        }

        $label = $academicYear->label;

        // Supprimer les trimestres et séquences associés
        foreach ($academicYear->trimesters as $trimester) {
            $trimester->sequences()->delete();
            $trimester->delete();
        }

        $academicYear->delete();
        AuditLog::log('deleted', null, ['label' => $label], []);

        return redirect()
            ->route('academic-years.index')
            ->with('success', "Année {$label} supprimée.");
    }


    // ── SÉQUENCES — MODIFICATION ───────────────────────────────────────────
    // public function updateSequence(UpdateSequenceRequest $request, Sequence $sequence)
    // {
    //     $oldValues = $sequence->toArray();

    //     $sequence->update([
    //         'number'     => $request->number,
    //         'label'      => $request->label,
    //         'start_date' => $request->start_date,
    //         'end_date'   => $request->end_date,
    //     ]);

    //     AuditLog::log('updated', $sequence, $oldValues, $sequence->toArray());

    //     return back()
    //         ->with('success', "La séquence {$sequence->label} a été mise à jour.")
    //         ->with('active_tab', 'sequences');
    // }

    // ── SÉQUENCES — SUPPRESSION ────────────────────────────────────────────
    // public function destroySequence(Sequence $sequence)
    // {
    //     // Vérifier les dépendances
    //     if ($sequence->grades()->exists()) {
    //         return back()->with('error',
    //             'Impossible de supprimer cette séquence : elle contient des notes.');
    //     }

    //     if ($sequence->gradeLocks()->exists()) {
    //         return back()->with('error',
    //             'Impossible de supprimer cette séquence : elle est verrouillée.');
    //     }

    //     $label = $sequence->label;
    //     AuditLog::log('deleted', $sequence, $sequence->toArray(), []);

    //     $sequence->delete();

    //     return back()
    //         ->with('success', "La séquence {$label} a été supprimée.")
    //         ->with('active_tab', 'sequences');
    // }
}
