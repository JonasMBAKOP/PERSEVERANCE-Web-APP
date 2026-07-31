<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Staff;
use App\Models\SchoolSetting;use App\Models\StaffPaySlip;
use App\Models\TeacherAssignment;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\TimetableGridService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use App\Models\StaffPosition;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    private const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
    ];

    // ── LISTE ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        // Début - Modification pour le design et filtres Enseignants & Personnel
        $query = Staff::with(['positions', 'user', 'teacherAssignments.classSubject.subject'])
            ->withCount('teacherAssignments');

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name',  'like', "%{$request->search}%")
                  ->orWhere('email',      'like', "%{$request->search}%")
                  ->orWhere('phone',      'like', "%{$request->search}%")
            );
        }

        if ($request->filled('position')) {
            $query->whereHas('positions', fn($q) =>
                $q->where('position', $request->position)
            );
        }

        if ($request->filled('subject_id')) {
            $query->whereHas('teacherAssignments.classSubject', fn($q) =>
                $q->where('subject_id', $request->subject_id)
            );
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('contract')) {
            $query->where('contract_type', $request->contract);
        }

        $staff = $query->orderBy('last_name')
                       ->orderBy('first_name')
                       ->paginate(15)
                       ->withQueryString();

        $activeYear = AcademicYear::active();
        $stats = [
            'total'      => Staff::count(),
            'active'     => Staff::where('is_active', true)->count(),
            'teachers'   => Staff::whereHas('positions',
                fn($q) => $q->where('position', 'enseignant'))->count(),
            'subjects_taught' => \App\Models\Subject::whereHas('classSubjects.teacherAssignments',
                fn($q) => $q->where('academic_year_id', $activeYear?->id)
            )->count(),
            'no_class'   => Staff::where('is_active', true)
                ->whereHas('positions', fn($q) => $q->where('position', 'enseignant'))
                ->whereDoesntHave('teacherAssignments', fn($q) => $q->where('academic_year_id', $activeYear?->id))
                ->count(),
        ];

        $subjects = \App\Models\Subject::orderBy('name_fr')->get();

        return view('staff.index', compact('staff', 'stats', 'subjects'));
        // Fin - Modification pour le design et filtres Enseignants & Personnel
    }

    // ── FORMULAIRE CRÉATION ───────────────────────────────────────────────
    public function create()
    {
        return view('staff.create', $this->formData());
    }

    // ── ENREGISTREMENT ────────────────────────────────────────────────────
    public function store(StoreStaffRequest $request)
    {
        $data = $request->except([
            'photo', 'positions', 'primary_position',
            'user_option', 'new_user_name', 'new_user_email',
            'new_user_password', 'new_user_role',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        // Upload photo
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('staff/photos', 'public');
        }

        try {
            $staffMember = DB::transaction(function () use ($request, $data) {
                // Gérer le compte utilisateur
                $createdUser = null;
                if ($request->input('user_option') === 'create') {
                    if ($request->filled('new_user_role') &&
                        ! $this->canAssignRole(Auth::user(), $request->new_user_role)) {
                        return back()->withInput()
                            ->with('error', 'Vous ne pouvez pas attribuer ce rôle.');
                    }

                    $createdUser = User::create([
                        'name'      => $request->new_user_name,
                        'email'     => $request->new_user_email,
                        'password'  => Hash::make($request->new_user_password),
                        'is_active' => true,
                    ]);
                    if ($request->filled('new_user_role')) {
                        $createdUser->assignRole($request->new_user_role);
                    }
                    $data['user_id'] = $createdUser->id;
                }

                $staff = Staff::create($data);

                $userToSync = $createdUser ?? ($staff->user_id ? User::find($staff->user_id) : null);
                if ($userToSync) {
                    $sharedPhotoPath = $staff->photo ?: $userToSync->photo;
                    if ($sharedPhotoPath && $staff->photo !== $sharedPhotoPath) {
                        $staff->update(['photo' => $sharedPhotoPath]);
                    }
                    $this->syncUserPhoto($userToSync, $sharedPhotoPath);
                }

                // Créer les postes
                $positions = $request->input('positions', []);
                if (empty($positions)) {
                    throw new \Exception('Au moins un poste doit être sélectionné.');
                }

                $this->syncPositions(
                    $staff,
                    $positions,
                    $request->input('primary_position')
                );

                $this->syncCenseurAssignments(
                    $staff,
                    $positions,
                    $request->input('censeur_assignments', [])
                );

                AuditLog::log('created', $staff, [], $staff->toArray());

                return $staff;
            });
        } catch (\Exception $e) {
            // Nettoyer la photo si créée mais que la transaction a échoué
            if (!empty($data['photo']) && Storage::disk('public')->exists($data['photo'])) {
                Storage::disk('public')->delete($data['photo']);
            }
            
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }

        return redirect()
            ->route('staff.show', $staffMember)
            ->with('success',
                "Dossier de {$staffMember->full_name} créé avec succès.");
    }

    // public function store(StoreStaffRequest $request)
    // {
    //     $staff = DB::transaction(function () use ($request) {
    //         $userId = $this->resolveUserId($request);

    //         $staff = Staff::create([
    //             'user_id'       => $userId,
    //             'first_name'    => $request->first_name,
    //             'last_name'     => $request->last_name,
    //             'gender'        => $request->gender,
    //             'date_of_birth' => $request->date_of_birth,
    //             'phone'         => $request->phone,
    //             'email'         => $request->email,
    //             'diploma'       => $request->diploma,
    //             'start_date'    => $request->start_date,
    //             'contract_type' => $request->contract_type,
    //             'is_active'     => true,
    //         ]);

    //         if ($request->hasFile('photo')) {
    //             $staff->update([
    //                 'photo' => $request->file('photo')->store('staff', 'public'),
    //             ]);
    //         }

    //         $this->syncPositions($staff, $request->positions, $request->primary_position);

    //         return $staff;
    //     });

    //     AuditLog::log('created', $staff, [], $staff->toArray());

    //     return redirect()
    //         ->route('staff.show', $staff)
    //         ->with('success', "Fiche de {$staff->full_name} créée avec succès.");
    // }

    // ── DÉTAIL ──────────────────────────────────────────────────────────────
    public function show(Staff $staff)
    {
        $staff->load([
            'positions',
            'user.roles',
            'teacherAssignments.classSubject.subject',
            'teacherAssignments.classSubject.classGroup.level.section',
            'teacherAssignments.academicYear',
            'titularClasses.level.section',
            'titularClasses.academicYear',
        ]);

        $activeYear = AcademicYear::active();

        // $titularClasses = $staff->titularClasses()
        //     ->when($activeYear, fn ($q) =>
        //         $q->where('academic_year_id', $activeYear->id)
        //     )
        //     ->with(['level.section', 'academicYear'])
        //     ->get();

        // Cours de cette année
        $currentAssignments = $staff->teacherAssignments
            ->filter(fn($ta) => $ta->academic_year_id === $activeYear?->id);

        $scheduleSlots = collect();
        $gridRows = [];
        $scheduleTeacherSubjectCount = 0;
        $scheduleTotalHours = 0;

        if ($staff->isTeacher() && $activeYear) {
            $classSubjectIds = $staff->teacherAssignments
                ->where('academic_year_id', $activeYear->id)
                ->pluck('class_subject_id')
                ->filter()
                ->values();

            if ($classSubjectIds->isNotEmpty()) {
                $scheduleSlots = TimetableSlot::whereIn('class_subject_id', $classSubjectIds)
                    ->where('academic_year_id', $activeYear->id)
                    ->with(['classGroup.level.section', 'classSubject.subject'])
                    ->orderBy('day_of_week')
                    ->orderBy('period_index')
                    ->orderBy('start_time')
                    ->get();
            }

            $setting = TimetableSetting::current();
            $gridRows = app(TimetableGridService::class)
                ->buildGrid($setting, self::DAYS)['rows'];

            $scheduleTeacherSubjectCount = $scheduleSlots
                ->map(fn($slot) => $slot->classSubject?->subject?->name_fr)
                ->filter()
                ->unique()
                ->count();

            $scheduleTotalHours = $scheduleSlots->sum('periods_count');
        }

        return view('staff.show', compact(
            'staff', 'activeYear', 'currentAssignments',
            'scheduleSlots', 'gridRows', 'scheduleTeacherSubjectCount', 'scheduleTotalHours'
        ));
    }

    public function printList(Request $request)
    {
        $query = Staff::with(['positions']);

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
            );
        }
        if ($request->filled('position')) {
            $query->whereHas('positions', fn ($q) => $q->where('position', $request->position));
        }
        if ($request->filled('subject_id')) {
            $query->whereHas('teacherAssignments.classSubject', fn ($q) => $q->where('subject_id', $request->subject_id));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        if ($request->filled('contract')) {
            $query->where('contract_type', $request->contract);
        }

        return view('staff.print-list', [
            'staff' => $query->orderBy('last_name')->orderBy('first_name')->get(),
            'school' => SchoolSetting::instance(),
            'academicYear' => AcademicYear::active(),
            'phones' => collect(),
            'agreements' => collect(),
        ]);
    }

    // ── SALAIRES DU PERSONNEL ───────────────────────────────────────────────
    public function salaries(Request $request)
    {
        $query = Staff::with(['positions', 'user'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($request->filled('contract')) {
            $query->where('contract_type', $request->contract);
        }

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name',  'like', "%{$request->search}%")
                  ->orWhere('email',      'like', "%{$request->search}%")
                  ->orWhere('phone',      'like', "%{$request->search}%")
            );
        }

        $staff = $query->paginate(20)->withQueryString();

        $contractCounts = Staff::select('contract_type')
            ->selectRaw('count(*) as total')
            ->groupBy('contract_type')
            ->pluck('total', 'contract_type')
            ->toArray();

        return view('staff.salaries', compact('staff', 'contractCounts'));
    }

    // ── EDITION DU SALAIRE DU PERSONNEL ─────────────────────────────────────
    public function editSalary(Staff $staff)
    {
        $staff->load('positions', 'user');

        return view('staff.salary-edit', compact('staff'));
    }

    private function staffDocumentContext(Staff $staff): array
    {
        $staff->load(['positions', 'user']);
        $school = \App\Models\SchoolSetting::instance();
        $phones = \App\Models\SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();
        $activeYear = AcademicYear::active();
        $logoSrc = $this->resolveSchoolLogo($school);

        return compact('staff', 'school', 'phones', 'activeYear', 'logoSrc');
    }

    private function resolveSchoolLogo($school): ?string
    {
        $logoSrc = null;

        if ($school->logo) {
            $logoPath = ltrim($school->logo, '/');
            $candidates = [public_path('storage/' . $logoPath), public_path($logoPath)];
            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    $logoSrc = asset(str_replace('\\', '/', str_replace(public_path(), '', $candidate)));
                    break;
                }
            }
        }

        if (! $logoSrc && file_exists(public_path('images/logo.jpg'))) {
            $logoSrc = asset('images/logo.jpg');
        }

        return $logoSrc;
    }

    private function formatPaySlipPeriod(?string $value): string
    {
        if (blank($value)) {
            return now()->locale('fr')->translatedFormat('F Y');
        }

        $date = \Carbon\Carbon::createFromFormat('Y-m', $value);

        return $date->locale('fr')->translatedFormat('F Y');
    }

    public function printSalaryList(Request $request)
    {
        $query = Staff::with(['positions', 'user'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($request->filled('contract')) {
            $query->where('contract_type', $request->contract);
        }

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
            );
        }

        $staff = $query->get();
        $data = $this->staffDocumentContext(new Staff());
        $data['staff'] = $staff;

        return view('staff.documents.salary-list', $data);
    }

    public function paySlip(Staff $staff, ?Request $request = null)
    {
        $data = $this->staffDocumentContext($staff);
        // Prefill with any saved slip for the current month
        $currentPeriod = now()->format('Y-m');
        $saved = StaffPaySlip::where('staff_id', $staff->id)
            ->where('period', $currentPeriod)
            ->first();

        $data['amountReceived'] = $saved?->amount_received ?? null;
        $data['periodLabel'] = now()->locale('fr')->translatedFormat('F Y');
        $data['previewMode'] = false;

        return view('staff.documents.pay-slip-form', $data);
    }

    public function previewPaySlip(Request $request, Staff $staff)
    {
        $request->validate([
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'period' => ['nullable', 'string'],
        ]);

        $amountReceived = $request->input('amount_received');
        $period = $request->input('period');

        // Preview should not persist; saving is done explicitly via storePaySlip()

        $data = $this->staffDocumentContext($staff);
        $data['amountReceived'] = $amountReceived;
        $data['periodLabel'] = $this->formatPaySlipPeriod($period);
        $data['previewMode'] = true;

        return view('staff.documents.pay-slip', $data);
    }

    public function storePaySlip(Request $request, Staff $staff)
    {
        $request->validate([
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'period' => ['nullable', 'string'],
        ]);

        $amountReceived = $request->input('amount_received');
        $period = $request->input('period');

        StaffPaySlip::updateOrCreate(
            ['staff_id' => $staff->id, 'period' => $period],
            ['amount_received' => $amountReceived]
        );

        return redirect()->back()->with('success', 'Montant enregistré.');
    }

    public function annualPaySlip(Staff $staff)
    {
        $data = $this->staffDocumentContext($staff);
        $activeYear = $data['activeYear'];
        $start = $activeYear?->start_date ? $activeYear->start_date->copy()->startOfMonth() : now()->copy()->startOfYear();
        $end = $activeYear?->end_date ? $activeYear->end_date->copy()->startOfMonth() : now()->copy()->endOfYear();

        $periods = $this->buildPaySlipPeriods($start, $end);
        $savedSlips = StaffPaySlip::where('staff_id', $staff->id)
            ->whereIn('period', $periods->keys()->all())
            ->get()
            ->keyBy('period');

        $rows = $periods->map(function ($label, $period) use ($savedSlips) {
            return [
                'period' => $period,
                'label' => $label,
                'amount_received' => optional($savedSlips->get($period))->amount_received,
            ];
        });

        $data['rows'] = $rows;
        $data['totalReceived'] = $rows->sum('amount_received');
        $data['yearLabel'] = $activeYear?->label ?? now()->year;

        return view('staff.documents.annual-pay-slip', $data);
    }

    private function buildPaySlipPeriods(
        \Carbon\Carbon $start,
        \Carbon\Carbon $end
    ) : \Illuminate\Support\Collection {
        $periods = collect();
        $current = $start->copy();

        while ($current->lessThanOrEqualTo($end)) {
            $periods->put(
                $current->format('Y-m'),
                $current->locale('fr')->translatedFormat('F Y')
            );
            $current->addMonth();
        }

        return $periods;
    }

    public function updateSalary(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate'    => ['nullable', 'numeric', 'min:0'],
            'period_rate'    => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($staff->contract_type === 'permanent') {
            $request->validate([
                'monthly_salary' => ['required'],
            ]);
        } elseif (in_array($staff->contract_type, ['vacataire', 'stagiaire'], true)) {
            $request->validate([
                'hourly_rate' => ['required'],
            ]);
        }

        $staff->update($data);

        return redirect()->route('staff.salaries')
            ->with('success', "Salaire mis à jour pour {$staff->full_name}.");
    }

    // ── FORMULAIRE MODIFICATION ─────────────────────────────────────────────
    public function edit(Staff $staff)
    {
        $staff->load('positions', 'user', 'censeurAssignments');

        return view('staff.edit', array_merge(
            ['staff' => $staff],
            $this->formData($staff)
        ));
    }

    // ── MISE À JOUR ─────────────────────────────────────────────────────────
    
    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        $data = $request->except([
            'photo', 'positions', 'primary_position',
            'user_option', 'new_user_name', 'new_user_email',
            'new_user_password', 'new_user_role',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $newPhotoPath = null;
        $oldStaffPhotoPath = $staff->photo;
        if ($request->hasFile('photo')) {
            $newPhotoPath = $request->file('photo')
                ->store('staff/photos', 'public');
            $data['photo'] = $newPhotoPath;
        }

        // Gérer le compte utilisateur
        $createdUser = null;
        if ($request->input('user_option') === 'create') {
            if ($request->filled('new_user_role') &&
                ! $this->canAssignRole(Auth::user(), $request->new_user_role)) {
                return back()->withInput()
                    ->with('error', 'Vous ne pouvez pas attribuer ce rôle.');
            }

            $createdUser = User::create([
                'name'      => $request->new_user_name,
                'email'     => $request->new_user_email,
                'password'  => Hash::make($request->new_user_password),
                'is_active' => true,
            ]);
            if ($request->filled('new_user_role')) {
                $createdUser->assignRole($request->new_user_role);
            }
            $data['user_id'] = $createdUser->id;
        } elseif ($request->input('user_option') === 'existing') {
            $data['user_id'] = $request->user_id ?: null;
        } elseif ($request->input('user_option') === 'none') {
            $data['user_id'] = null;
        }

        $old = $staff->toArray();

        try {
            DB::transaction(function () use ($staff, $data, $request, $createdUser) {
                $staff->update($data);

                $userToSync = $createdUser ?? ($staff->user_id ? User::find($staff->user_id) : null);
                if ($userToSync) {
                    $sharedPhotoPath = $staff->photo ?: $userToSync->photo;
                    if ($sharedPhotoPath && $staff->photo !== $sharedPhotoPath) {
                        $staff->update(['photo' => $sharedPhotoPath]);
                    }
                    $this->syncUserPhoto($userToSync, $sharedPhotoPath);
                }

                $positions = $request->input('positions', []);
                $this->syncPositions(
                    $staff,
                    $positions,
                    $request->input('primary_position')
                );

                $this->syncCenseurAssignments(
                    $staff,
                    $positions,
                    $request->input('censeur_assignments', [])
                );
            });
        } catch (\Throwable $e) {
            if ($newPhotoPath && Storage::disk('public')->exists($newPhotoPath)) {
                Storage::disk('public')->delete($newPhotoPath);
            }
            return back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }

        if ($newPhotoPath && $oldStaffPhotoPath && $oldStaffPhotoPath !== $newPhotoPath) {
            Storage::disk('public')->delete($oldStaffPhotoPath);
        }

        $staff->refresh();
        AuditLog::log('updated', $staff, $old, $staff->toArray());

        return redirect()
            ->route('staff.show', $staff)
            ->with('success', "Dossier de {$staff->full_name} mis à jour.");
    }

    /** Keep the linked login account on the same physical photo as the staff record. */
    private function syncUserPhoto(User $user, ?string $sharedPhotoPath): void
    {
        if (! $sharedPhotoPath || $user->photo === $sharedPhotoPath) {
            return;
        }

        $previousPhotoPath = $user->photo;
        $user->update(['photo' => $sharedPhotoPath]);

        if ($previousPhotoPath && Storage::disk('public')->exists($previousPhotoPath)) {
            Storage::disk('public')->delete($previousPhotoPath);
        }
    }
     
    // public function update(UpdateStaffRequest $request, Staff $staff)
    // {
    //     $oldValues = $staff->toArray();

    //     DB::transaction(function () use ($request, $staff) {
    //         $data = [
    //             'user_id'       => $request->user_id ?: null,
    //             'first_name'    => $request->first_name,
    //             'last_name'     => $request->last_name,
    //             'gender'        => $request->gender,
    //             'date_of_birth' => $request->date_of_birth,
    //             'phone'         => $request->phone,
    //             'email'         => $request->email,
    //             'diploma'       => $request->diploma,
    //             'start_date'    => $request->start_date,
    //             'contract_type' => $request->contract_type,
    //             'is_active'     => $request->boolean('is_active'),
    //         ];

    //         if ($request->boolean('remove_photo')) {
    //             $this->deletePhoto($staff);
    //             $data['photo'] = null;
    //         }

    //         if ($request->hasFile('photo')) {
    //             $this->deletePhoto($staff);
    //             $data['photo'] = $request->file('photo')->store('staff', 'public');
    //         }

    //         $staff->update($data);
    //         $this->syncPositions($staff, $request->positions, $request->primary_position);
    //     });

    //     AuditLog::log('updated', $staff, $oldValues, $staff->fresh()->toArray());

    //     return redirect()
    //         ->route('staff.show', $staff)
    //         ->with('success', "Fiche de {$staff->full_name} mise à jour.");
    // }

    // ── DÉSACTIVATION (soft delete) ─────────────────────────────────────────
    public function destroy(Staff $staff)
    {
        // if ($staff->titularClasses()->exists()) {
        //     return back()->with('error',
        //         'Impossible de supprimer ce membre : il est titulaire d\'au moins une classe.');
        // }

        // Vérifier s'il a des assignations actives
        $hasAssignments = $staff->teacherAssignments()->count() > 0;

        if ($hasAssignments) {
            return back()->with('error',
                "Impossible de supprimer {$staff->full_name} : "
                . "il/elle a des cours assignés.");
        }

        $name = $staff->full_name;
        $user = $staff->user; // Récupérer le User avant suppression du Staff

        if ($staff->photo) {
            Storage::disk('public')->delete($staff->photo);
        }

        $staff->positions()->delete();
        $staff->delete();
        
        // Supprimer aussi le compte utilisateur associé s'il existe
        if ($user) {
            $user->delete();
        }

        AuditLog::log('deleted', null, ['name' => $name], []);

        return redirect()
            ->route('staff.index')
            ->with('success', "Dossier de {$name} supprimé.");

        // $oldValues = $staff->toArray();
        // $this->deletePhoto($staff);
        // $staff->update(['is_active' => false, 'user_id' => null]);
        // $staff->delete();

        // AuditLog::log('deleted', null, $oldValues);

        // return redirect()
        //     ->route('staff.index')
        //     ->with('success', "Fiche de {$name} archivée.");
    }

    // ── TOGGLE ACTIF / INACTIF ──────────────────────────────────────────────
    public function toggleActive(Staff $staff)
    {
        // if ($staff->is_active && $staff->titularClasses()->exists()) {
        //     return back()->with('error',
        //         'Impossible de désactiver ce membre : il est titulaire d\'au moins une classe.');
        // }

        $staff->update(['is_active' => !$staff->is_active]);
        $status = $staff->is_active ? 'activé(e)' : 'désactivé(e)';

        AuditLog::log('status_changed', $staff);

        return back()->with('success', "Dossier de {$staff->full_name} {$status}.");
    }

    // ── HELPERS ───────────────────────────────────────────────────────────
    private function formData(?Staff $staff = null): array
    {
        $authUser = Auth::user();

        $linkedUserIds = Staff::whereNotNull('user_id')
            ->when($staff, fn ($q) => $q->where('id', '!=', $staff->id))
            ->pluck('user_id');

        $availableUsers = User::whereDoesntHave('roles', fn ($q) =>
                $q->where('name', 'super-admin')
            )
            ->where(function ($q) use ($linkedUserIds, $staff) {
                $q->whereNotIn('id', $linkedUserIds);
                if ($staff?->user_id) {
                    $q->orWhere('id', $staff->user_id);
                }
            })
            ->orderBy('name')
            ->get();

        $roles = $this->allowedAccountRoles($authUser);

        return compact('availableUsers', 'roles') + [
            'sections'       => \App\Models\Section::orderBy('name')->get(),
            'positionLabels' => Staff::positionLabels(),
            'contractLabels' => Staff::contractLabels(),
            'diplomas'       => Staff::DIPLOMAS,
        ];
    }

    private function resolveUserId(StoreStaffRequest $request): ?int
    {
        if ($request->boolean('create_account')) {
            $user = User::create([
                'name'      => trim("{$request->last_name} {$request->first_name}"),
                'email'     => $request->account_email,
                'password'  => $request->account_password,
                'phone'     => $request->phone,
                'is_active' => true,
            ]);

            if ($request->filled('account_roles')) {
                $user->syncRoles($request->account_roles);
            }

            return $user->id;
        }

        return $request->user_id ?: null;
    }

    private function allowedAccountRoles(User $authUser)
    {
        $referenceUser = new User();

        return Role::orderBy('name')->get()->filter(function ($role) use ($authUser, $referenceUser) {
            return $authUser->hasRole('super-admin')
                || $authUser->getRoleLevel() > $referenceUser->getRoleLevelByName($role->name);
        });
    }

    private function canAssignRole(User $authUser, string $roleName): bool
    {
        return $authUser->hasRole('super-admin')
            || $authUser->getRoleLevel() > $authUser->getRoleLevelByName($roleName);
    }

    private function syncPositions(
        Staff $staff,
        array $positions,
        ?string $primaryPosition = null
    ): void {
        $staff->positions()->delete();

        // $hasPrimary = false;
        
        foreach ($positions as $position) {
            // if (empty($position['name'])) continue;
            if (empty($position)) continue;
            // $isPrimary = isset($position['primary']) && $position['primary'];
            // if ($isPrimary) $hasPrimary = true;

            StaffPosition::create([
                'staff_id'   => $staff->id,
                'position'   => $position,
                // 'is_primary' => $isPrimary,
                'is_primary' => $position === $primaryPosition,
            ]);
        }

        // Si aucun principal défini, définir le premier
        // if (!$hasPrimary) {
        if (!$primaryPosition || !in_array($primaryPosition, $positions)) {
            $staff->positions()->first()?->update(['is_primary' => true]);
        }
    }

    private function syncCenseurAssignments(
        Staff $staff,
        array $positions,
        array $assignmentsData
    ): void {
        $isCenseur = in_array('censeur', $positions, true) || in_array('prefet_des_etudes', $positions, true);
        
        $staff->censeurAssignments()->delete();

        if ($isCenseur && !empty($assignmentsData)) {
            foreach ($assignmentsData as $sectionId => $cycles) {
                if (is_array($cycles)) {
                    foreach ($cycles as $cycle) {
                        if (in_array($cycle, ['1er', '2nd'], true)) {
                            \App\Models\CenseurAssignment::create([
                                'staff_id'   => $staff->id,
                                'section_id' => $sectionId,
                                'cycle'      => $cycle,
                            ]);
                        }
                    }
                }
            }
        }
    }

    // private function syncPositions(Staff $staff, array $positions, string $primary): void
    // {
    //     $staff->positions()->delete();

    //     foreach (array_unique($positions) as $position) {
    //         $staff->positions()->create([
    //             'position'   => $position,
    //             'is_primary' => $position === $primary,
    //         ]);
    //     }
    // }

    // ── SUPPRESSION PHOTO ─────────────────────────────────────────────────
    public function deletePhoto(Staff $staff)
    {
        $user = $staff->user;
        $photosToDelete = array_filter([$staff->photo, $user?->photo]);
        $staff->update(['photo' => null]);
        if ($user) {
            $user->update(['photo' => null]);
        }
        foreach (array_unique($photosToDelete) as $photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return back()->with('success', 'Photo supprimée.');
    }

    // private function deletePhoto(Staff $staff): void
    // {
    //     if ($staff->photo && Storage::disk('public')->exists($staff->photo)) {
    //         Storage::disk('public')->delete($staff->photo);
    //     }
    // }
}
