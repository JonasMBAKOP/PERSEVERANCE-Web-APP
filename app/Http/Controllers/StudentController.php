<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPayment;
use App\Services\EnrollmentService;
use App\Services\StudentDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly StudentDocumentService $documents
    ) {}
    // ── LISTE ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $activeYear = AcademicYear::active();
        $selectedYearId = $request->input('year_id', $activeYear?->id);
        $selectedYear   = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : null;

        $query = Student::query();
        $renewalFilter = $request->input('renewal') === 'pending';

        // Filtrer par classe / année
        if ($selectedYear) {
            if ($renewalFilter && $selectedYear->is_active) {
                $query->whereDoesntHave('enrollments', fn ($q) =>
                    $q->where('academic_year_id', $selectedYear->id)
                      ->where('status', StudentEnrollment::STATUS_ACTIVE)
                )->whereHas('enrollments', fn ($q) =>
                    $q->whereHas('academicYear', fn ($y) =>
                        $y->where('start_date', '<', $selectedYear->start_date)
                    )
                );
            } else {
                $query->whereHas('enrollments', fn ($q) =>
                    $q->where('academic_year_id', $selectedYear->id)
                );
            }
        }

        // Filtrer par classe spécifique
        if ($request->filled('class_id')) {
            $query->whereHas('enrollments', function ($q) use ($request, $selectedYear) {
                $q->where('class_group_id', $request->class_id);

                if ($selectedYear) {
                    $q->where('academic_year_id', $selectedYear->id);
                }
            });
        }

        // Filtrer par section
        if ($request->filled('section_id')) {
            $query->whereHas('enrollments', function ($q) use ($request, $selectedYear) {
                $q->whereHas('classGroup.level', fn($levelQuery) =>
                    $levelQuery->where('section_id', $request->section_id)
                );

                if ($selectedYear) {
                    $q->where('academic_year_id', $selectedYear->id);
                }
            });
        }

        // Recherche
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('first_name',  'like', "%{$s}%")
                  ->orWhere('last_name',  'like', "%{$s}%")
                  ->orWhere('matricule',  'like', "%{$s}%")
            );
        }

        $students = $query
            ->with([
                'enrollments' => function ($q) use ($selectedYear, $renewalFilter) {
                    $q->with('classGroup.level.section', 'academicYear');

                    if ($renewalFilter && $selectedYear) {
                        $q->whereHas('academicYear', fn ($y) =>
                            $y->where('start_date', '<', $selectedYear->start_date)
                        );
                    } elseif ($selectedYear) {
                        $q->where('academic_year_id', $selectedYear->id);
                    }
                },
            ])
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(20)->withQueryString();

        $years    = AcademicYear::orderByDesc('start_date')->get();
        $sections = Section::orderBy('id')->get();
        $classes  = $selectedYear
            ? ClassGroup::where('academic_year_id', $selectedYear->id)
                ->with('level.section')->orderBy('name')->get()
            : collect();

        // Stats (uniquement inscriptions actives)
        $stats = [
            'total'     => $selectedYear
                ? StudentEnrollment::where('academic_year_id', $selectedYear->id)
                    ->where('status', StudentEnrollment::STATUS_ACTIVE)
                    ->count()
                : StudentEnrollment::where('status', StudentEnrollment::STATUS_ACTIVE)->count(),
            'boys'      => $selectedYear
                ? StudentEnrollment::where('academic_year_id', $selectedYear->id)
                    ->where('status', StudentEnrollment::STATUS_ACTIVE)
                    ->whereHas('student', fn($q) => $q->where('gender', 'M'))
                    ->count()
                : 0,
            'girls'     => $selectedYear
                ? StudentEnrollment::where('academic_year_id', $selectedYear->id)
                    ->where('status', StudentEnrollment::STATUS_ACTIVE)
                    ->whereHas('student', fn($q) => $q->where('gender', 'F'))
                    ->count()
                : 0,
            'repeating' => $selectedYear
                ? StudentEnrollment::where('academic_year_id', $selectedYear->id)
                    ->where('status', StudentEnrollment::STATUS_ACTIVE)
                    ->where('is_repeating', true)->count()
                : 0,
        ];

        // Année éditable = active uniquement
        $isYearEditable = $selectedYear && $selectedYear->is_active;
        $pendingRenewal = $activeYear
            ? $this->enrollments->pendingRenewalCount($activeYear)
            : 0;

        $listPrintParams = $this->documents->listPrintParamsFromIndexFilters(
            $selectedYear,
            $request->input('class_id'),
            $request->input('section_id'),
            $renewalFilter
        );

        return view('students.index', compact(
            'students', 'years', 'sections', 'classes',
            'selectedYear', 'stats', 'activeYear', 'isYearEditable',
            'renewalFilter', 'pendingRenewal', 'listPrintParams'
        ));
    }

    // ── FORMULAIRE CRÉATION ───────────────────────────────────────────────
    public function create(Request $request)
    {
        $activeYear = AcademicYear::active();
        $suggestedMatricule = Student::generateMatricule();

        $preSelectedClass = $request->filled('class_id')
            ? ClassGroup::with('level.section', 'academicYear')->find($request->class_id)
            : null;

        $sectionsJson = Section::query()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn ($section) => ['id' => $section->id, 'name' => $section->name])
            ->values()
            ->toArray();

        $classesJson = [];
        if ($activeYear) {
            $classes = ClassGroup::where('academic_year_id', $activeYear->id)
                ->with(['level.section'])
                ->orderBy('name')
                ->get();

            foreach ($classes as $classGroup) {
                $classesJson[] = [
                    'id' => $classGroup->id,
                    'full_name' => $classGroup->full_name,
                    'level_name' => $classGroup->level?->name,
                    'section_id' => $classGroup->level?->section_id,
                    'section_code' => $classGroup->level?->section?->code,
                    'section_name' => $classGroup->level?->section?->name,
                    'max_students' => $classGroup->max_students,
                    'students_count' => $classGroup->studentEnrollments()->where('status', 'active')->count(),
                ];
            }
        }

        $allClasses = ClassGroup::with('level.section', 'academicYear')
            ->whereHas('academicYear', fn ($query) => $query->where('id', '!=', $activeYear?->id))
            ->orderBy('name')
            ->get();

        return view('students.create', compact(
            'activeYear', 'suggestedMatricule', 'preSelectedClass',
            'sectionsJson', 'classesJson', 'allClasses'
        ));
    }
    public function store(StoreStudentRequest $request)
    {
        // Validation préalable des données critiques
        try {
            $class = ClassGroup::findOrFail($request->class_group_id);
            $academicYear = AcademicYear::findOrFail($request->academic_year_id);
            
            // Vérifier que la classe appartient à l'année
            if ($class->academic_year_id !== $academicYear->id) {
                return back()
                    ->withInput()
                    ->with('error', 'La classe sélectionnée n\'appartient pas à l\'année académique choisie.');
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Classe ou année académique invalide.');
        }

        $data = $request->except(['photo']);

        if (empty($data['matricule'])) {
            $data['matricule'] = Student::generateMatricule();
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')
                ->store('students/photos', 'public');
        }

        try {
            $student = DB::transaction(function () use ($request, $data, $class, $academicYear) {
                // 0. Vérifier que le matricule n'existe pas déjà dans les élèves existants (pas de soft delete, on utilise forceDelete)
                $existingStudent = Student::where('matricule', $data['matricule'])->first();
                
                if ($existingStudent) {
                    $currentEnrollment = $existingStudent->enrollments()
                        ->where('status', StudentEnrollment::STATUS_ACTIVE)
                        ->with('classGroup')
                        ->first();
                    
                    if ($currentEnrollment) {
                        throw new \Exception(
                            "Un élève avec le matricule '{$data['matricule']}' est déjà inscrit dans la classe {$currentEnrollment->classGroup->full_name}. "
                            . "Vous ne pouvez pas créer un doublon avec le même matricule."
                        );
                    } else {
                        throw new \Exception(
                            "Un élève avec le matricule '{$data['matricule']}' existe déjà dans le système. "
                            . "Vous ne pouvez pas créer un doublon."
                        );
                    }
                }

                // 1. Vérifier la capacité AVANT de créer l'élève
                try {
                    $this->enrollments->assertClassHasCapacity($class);
                } catch (\InvalidArgumentException $e) {
                    throw new \Exception('Classe pleine : ' . $e->getMessage());
                }

                // 2. Créer l'élève
                $student = Student::create($data);
                AuditLog::log('created', $student, [], $student->toArray());

                // 3. Vérifier qu'il n'existe pas d'inscription dupliquée
                try {
                    $this->enrollments->assertNoDuplicateEnrollment(
                        $student,
                        $request->academic_year_id
                    );
                } catch (\InvalidArgumentException $e) {
                    // Annuler la création de l'élève si inscription dupliquée
                    $student->delete();
                    throw new \Exception('Inscription dupliquée : ' . $e->getMessage());
                }

                // 4. Créer l'inscription
                $enrollment = StudentEnrollment::create([
                    'student_id'              => $student->id,
                    'academic_year_id'        => $request->academic_year_id,
                    'class_group_id'          => $request->class_group_id,
                    'enrollment_date'         => $request->enrollment_date,
                    'is_repeating'            => $request->boolean('is_repeating'),
                    'previous_class_group_id' => $request->boolean('is_repeating')
                        ? $request->class_group_id
                        : $request->previous_class_group_id,
                    'previous_class_label'    => $request->previous_class_label,
                    'origin_school'           => $request->origin_school,
                    'status'                  => StudentEnrollment::STATUS_ACTIVE,
                ]);
                AuditLog::log('enrolled', $enrollment);

                return $student;
            });
        } catch (\Exception $e) {
            // Nettoyer la photo si créée mais que la transaction a échoué
            if (!empty($data['photo']) && Storage::disk('public')->exists($data['photo'])) {
                Storage::disk('public')->delete($data['photo']);
            }
            
            return back()
                ->withInput()
                ->with('error', 'Erreur lors de l\'inscription : ' . $e->getMessage());
        }

        return redirect()
            ->route('students.show', $student)
            ->with('success',
                "{$student->full_name} ajouté(e) et inscrit(e) avec succès. "
                . "Matricule : {$student->matricule}");
    }

    // ── DÉTAIL ────────────────────────────────────────────────────────────
    public function show(Student $student)
    {
        $student->load([
            'enrollments' => fn($q) => $q
                ->with([
                    'classGroup.level.section',
                    'academicYear',
                    'absences' => fn($absenceQuery) => $absenceQuery
                        ->with(['classSubject.subject', 'recordedBy'])
                        ->orderByDesc('absence_date'),
                ])
                ->orderByDesc('created_at'),
        ]);

        $activeYear       = $this->enrollments->activeYear();
        $activeEnrollment = $activeYear
            ? $this->enrollments->activeEnrollmentForYear($student, $activeYear->id)
            : null;

        if ($activeEnrollment) {
            $activeEnrollment->load([
                'classGroup.classSubjects' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('subject'),
                'grades' => fn ($query) => $query
                    ->with(['classSubject.subject', 'sequence'])
                    ->orderByDesc('updated_at'),
            ]);
        }

        if ($activeYear) {
            $activeYear->load('sequences');
        }

        $previousEnrollment = ($activeYear && ! $activeEnrollment)
            ? $this->enrollments->previousEnrollmentForRenewal($student, $activeYear)
            : null;
        $isEditable = $this->enrollments->isEditableInActiveYear($student);
        $canEnroll  = $this->enrollments->canEnrollInActiveYear($student);
        $transferClasses = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->with('level.section')
                ->orderBy('name')
                ->get()
            : collect();

        return view('students.show', compact(
            'student', 'activeEnrollment', 'previousEnrollment',
            'activeYear', 'isEditable', 'canEnroll', 'transferClasses'
        ));
    }

    // ── FORMULAIRE MODIFICATION ───────────────────────────────────────────
    public function edit(Student $student)
    {
        if (! $this->enrollments->activeYear()) {
            return redirect()->route('students.show', $student)
                ->with('error', 'Aucune année scolaire active. Modification impossible.');
        }

        if (! $this->enrollments->isEditableInActiveYear($student)) {
            return redirect()->route('students.show', $student)
                ->with('error', 'Cet élève est rattaché à une année clôturée. Aucune modification autorisée.');
        }

        return view('students.edit', compact('student'));
    }

    // ── MISE À JOUR ───────────────────────────────────────────────────────
    public function update(UpdateStudentRequest $request, Student $student)
    {
        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $data['photo'] = $request->file('photo')
                ->store('students/photos', 'public');
        }

        $old = $student->toArray();
        $student->update($data);
        AuditLog::log('updated', $student, $old, $student->toArray());

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Fiche de {$student->full_name} mise à jour.");
    }

    // ── SUPPRESSION PHOTO ─────────────────────────────────────────────────
    public function deletePhoto(Student $student)
    {
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
            $student->update(['photo' => null]);
        }
        return back()->with('success', 'Photo supprimée.');
    }

    private function deleteStudentAndRelatedData(Student $student): void
    {
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }

        $enrollmentIds = $student->enrollments()->pluck('id');

        // Supprimer tous les paiements liés aux inscriptions, y compris les paiements en bloc et leurs allocations
        if ($enrollmentIds->isNotEmpty()) {
            StudentPayment::whereIn('student_enrollment_id', $enrollmentIds)
                ->forceDelete();
        }

        // Supprimer toutes les inscriptions liées à l'élève
        $student->enrollments()->delete();

        // Supprimer l'élève lui-même (suppression permanente)
        $student->forceDelete();
    }

    // ── SUPPRESSION ───────────────────────────────────────────────────────
    public function destroy(Student $student)
    {
        $name = $student->full_name;
        $studentId = $student->id;

        try {
            DB::transaction(function () use ($student) {
                $this->deleteStudentAndRelatedData($student);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Impossible de supprimer cet élève : ' . $e->getMessage());
        }

        AuditLog::log('deleted', null, ['name' => $name, 'id' => $studentId], []);

        return redirect()->route('students.index')
            ->with('success', "Élève {$name} supprimé(e) définitivement ainsi que ses inscriptions et données associées.");
    }

    // ── FORMULAIRE INSCRIPTION ────────────────────────────────────────────
    public function enroll(Student $student)
    {
        $activeYear = AcademicYear::active();

        if (! $activeYear) {
            return redirect()->route('students.show', $student)
                ->with('error', 'Aucune année scolaire active. Renouvellement impossible.');
        }

        if (! $this->enrollments->canEnrollInActiveYear($student)) {
            return redirect()->route('students.show', $student)
                ->with('error',
                    "{$student->full_name} est déjà inscrit(e) pour {$activeYear->label}.");
        }

        $previousEnrollment = $this->enrollments
            ->previousEnrollmentForRenewal($student, $activeYear);

        $existingEnrollment = $this->enrollments
            ->activeEnrollmentForYear($student, $activeYear->id);

        $repeatClasses = collect();
        $promotionClasses = collect();

        if ($previousEnrollment?->classGroup?->level) {
            $level = $previousEnrollment->classGroup->level;
            $repeatClasses    = $this->enrollments->classesForRepeat($level, $activeYear);
            $promotionClasses = $this->enrollments->classesForPromotion($level, $activeYear);
        }

        $allClasses = ClassGroup::with('level.section', 'academicYear')
            ->whereHas('academicYear', fn ($q) =>
                $q->where('id', '!=', $activeYear->id)
            )
            ->orderBy('name')->get();

        $sectionsJson = Section::query()->orderBy('id')->get()->map(fn ($section) => [
            'id' => $section->id,
            'name' => $section->name,
        ])->values()->toArray();

        $classesJson = [];
        $classes = ClassGroup::where('academic_year_id', $activeYear->id)
            ->with(['level.section'])
            ->get();

        foreach ($classes as $c) {
            $classesJson[] = [
                'id'             => $c->id,
                'full_name'      => $c->full_name,
                'level_id'       => $c->level_id,
                'level_order'    => $c->level?->order_index,
                'section_id'     => $c->level?->section_id,
                'max_students'   => $c->max_students,
                'students_count' => $c->studentEnrollments()
                    ->where('status', StudentEnrollment::STATUS_ACTIVE)
                    ->count(),
                'is_repeat'      => $repeatClasses->contains('id', $c->id),
                'is_promotion'   => $promotionClasses->contains('id', $c->id),
            ];
        }

        $school = \App\Models\SchoolSetting::instance();

        return view('students.enroll', compact(
            'student', 'activeYear', 'existingEnrollment', 'previousEnrollment', 'school',
            'allClasses', 'sectionsJson', 'classesJson',
            'repeatClasses', 'promotionClasses'
        ));
    }

    // ── ENREGISTREMENT INSCRIPTION ────────────────────────────────────────
    public function storeEnrollment(StoreEnrollmentRequest $request,
                                    Student $student)
    {
        $activeYear = AcademicYear::active();

        if (! $activeYear || (int) $request->academic_year_id !== $activeYear->id) {
            return back()->with('error',
                'Le renouvellement ne peut se faire que pour l\'année scolaire active.');
        }

        $class = ClassGroup::findOrFail($request->class_group_id);

        if ($class->academic_year_id !== $activeYear->id) {
            return back()->with('error',
                'La classe sélectionnée n\'appartient pas à l\'année active.');
        }

        try {
            DB::transaction(function () use ($request, $student, $class, $activeYear) {
                $this->enrollments->assertNoDuplicateEnrollment(
                    $student,
                    $activeYear->id
                );
                // ATTENTION: Limite de capacité RETIRÉE par choix du client (point 2 du feedback)
                // $this->enrollments->assertClassHasCapacity($class);

                $previousEnrollment = $this->enrollments
                    ->previousEnrollmentForRenewal($student, $activeYear);
                $previousClass = $previousEnrollment?->classGroup;

                if (! $previousClass) {
                    throw new \InvalidArgumentException(
                        'Aucune inscription précédente n’a été trouvée pour cet élève.'
                    );
                }

                $class->loadMissing('level.section');
                $previousClass->loadMissing('level.section');

                $sameLevel = (int) $class->level_id === (int) $previousClass->level_id;
                $sameSection = (int) $class->level?->section_id === (int) $previousClass->level?->section_id;
                $isPromotion = $sameSection
                    && (int) $class->level?->order_index > (int) $previousClass->level?->order_index;

                if (! $sameLevel && ! $isPromotion) {
                    throw new \InvalidArgumentException(
                        'La classe choisie doit être le même niveau (redoublement) ou un niveau supérieur de la même section (promotion).'
                    );
                }

                $isRepeating = $sameLevel;
                $school = \App\Models\SchoolSetting::instance();

                $enrollment = StudentEnrollment::create([
                    'student_id'              => $student->id,
                    'academic_year_id'        => $activeYear->id,
                    'class_group_id'          => $request->class_group_id,
                    'enrollment_date'         => $request->enrollment_date,
                    'is_repeating'            => $isRepeating,
                    'previous_class_group_id' => $previousClass->id,
                    'previous_class_label'    => $previousClass->full_name,
                    'origin_school'           => $school->full_name,
                    'status'                  => StudentEnrollment::STATUS_ACTIVE,
                ]);

                AuditLog::log('enrolled', $enrollment);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('students.show', $student)
            ->with('success',
                "{$student->full_name} inscrit(e) en {$class->full_name} "
                . "pour {$activeYear->label}.");
    }

    public function updateEnrollmentDate(Request $request, StudentEnrollment $enrollment)
    {
        $data = $request->validate([
            'enrollment_date' => ['required', 'date'],
        ]);

        $old = $enrollment->toArray();
        $enrollment->update($data);
        AuditLog::log('updated', $enrollment, $old, $enrollment->fresh()->toArray());

        return back()->with('success', 'Date d\'inscription mise à jour.');
    }

    // ── TRANSFERT ─────────────────────────────────────────────────────────
    public function transfer(Request $request, StudentEnrollment $enrollment)
    {
        $request->validate([
            'new_class_id'    => ['required', 'exists:class_groups,id'],
            'transfer_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $newClass = ClassGroup::findOrFail($request->new_class_id);

            if ((int) $newClass->id === (int) $enrollment->class_group_id) {
                return back()->with('error', 'La classe de destination doit être différente de la classe actuelle.');
            }

            DB::transaction(function () use ($request, $enrollment, $newClass) {
                $this->enrollments->assertClassHasCapacity($newClass);

                $student = $enrollment->student;
                $oldStudentFullName = $student->full_name;
                $photoPath = null;

                // ─── ÉTAPE 1 : Sauvegarder toutes les données avant suppression ───────
                if ($student->photo) {
                    $photoPath = 'students/photos/' . Str::uuid() . '-' . basename($student->photo);
                    Storage::disk('public')->copy($student->photo, $photoPath);
                }

                $studentData = [
                    'matricule'             => $student->matricule,
                    'first_name'            => $student->first_name,
                    'last_name'             => $student->last_name,
                    'gender'                => $student->gender,
                    'date_of_birth'         => $student->date_of_birth,
                    'place_of_birth'        => $student->place_of_birth,
                    'birth_certificate_number' => $student->birth_certificate_number,
                    'nationality'           => $student->nationality,
                    'photo'                 => $photoPath,
                    'father_name'           => $student->father_name,
                    'father_phone'          => $student->father_phone,
                    'mother_name'           => $student->mother_name,
                    'mother_phone'          => $student->mother_phone,
                    'guardian_name'         => $student->guardian_name,
                    'guardian_phone'        => $student->guardian_phone,
                    'guardian_relationship' => $student->guardian_relationship,
                    'address'               => $student->address,
                ];

                $enrollmentData = [
                    'academic_year_id'        => $enrollment->academic_year_id,
                    'class_group_id'          => $newClass->id,
                    'enrollment_date'         => $enrollment->enrollment_date?->toDateString() ?? now()->toDateString(),
                    'is_repeating'            => $enrollment->is_repeating,
                    'previous_class_group_id' => $enrollment->class_group_id,
                    'previous_class_label'    => $enrollment->classGroup?->full_name,
                    'origin_school'           => $enrollment->origin_school,
                    'status'                  => StudentEnrollment::STATUS_ACTIVE,
                ];

                // ─── ÉTAPE 2 : Supprimer complètement l'ancien élève ──────────────────
                // (inscriptions, paiements, reçus de paiement, l'élève lui-même)
                $this->deleteStudentAndRelatedData($student);

                // ─── ÉTAPE 3 : Créer le nouvel élève avec les données sauvegardées ───
                $newStudent = Student::create($studentData);

                // ─── ÉTAPE 4 : Inscrire le nouvel élève dans la classe cible ────────
                StudentEnrollment::create([
                    'student_id' => $newStudent->id,
                    ...$enrollmentData,
                ]);

                AuditLog::log('transferred', $enrollment);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Impossible de transférer cet élève : ' . $e->getMessage());
        }

        return redirect()->route('students.index')->with('success',
            "L'élève a été transféré avec succès dans la nouvelle classe.");
    }

    // ── CHANGEMENT DE STATUT ──────────────────────────────────────────────
    public function updateStatus(Request $request,
                                  StudentEnrollment $enrollment)
    {
        $request->validate([
            'status' => ['required',
                         'in:active,transferred,withdrawn,excluded'],
        ]);

        $enrollment->update(['status' => $request->status]);
        AuditLog::log('status_changed', $enrollment);

        $labels = [
            'active'      => 'Actif(ve)',
            'transferred' => 'Transféré(e)',
            'withdrawn'   => 'Retiré(e)',
            'excluded'    => 'Exclu(e)',
        ];

        return back()->with('success',
            "Statut de {$enrollment->student->full_name} : "
            . $labels[$request->status]);
    }
}