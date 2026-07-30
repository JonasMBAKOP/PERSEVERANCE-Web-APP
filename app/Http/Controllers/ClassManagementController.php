<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassGroupRequest;
use App\Http\Requests\UpdateClassGroupRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\Level;
use App\Models\Section;
use App\Models\Staff;
use App\Models\TeacherAssignment;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use App\Services\GradeCalculationService;
use App\Services\TimetableGridService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassManagementController extends Controller
{
    private function isClassAdmin(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user->hasAnyRole(['super-admin', 'directeur', 'censeur', 'fondateur']);
    }

    private function isTeacherView(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return ! $this->isClassAdmin() && $user->hasRole('enseignant');
    }

    /** IDs des classes où l'enseignant est affecté pour l'année donnée */
    private function teacherClassIds(?AcademicYear $year): array
    {
        if (! $year) {
            return [];
        }

        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $staff = $user->staff;

        if (! $staff) {
            return [];
        }

        return TeacherAssignment::where('staff_id', $staff->id)
            ->where('academic_year_id', $year->id)
            ->whereHas('classSubject')
            ->with('classSubject:id,class_group_id')
            ->get()
            ->pluck('classSubject.class_group_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    // ── LISTE ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $activeYear   = AcademicYear::active();
        $isTeacher    = $this->isTeacherView();
        $teacherIds   = $isTeacher ? $this->teacherClassIds($activeYear) : [];

        if ($isTeacher) {
            $selectedYear = $activeYear;
        } else {
            $selectedYearId = $request->input('year_id');
            $selectedYear   = $selectedYearId
                ? AcademicYear::find($selectedYearId)
                : $activeYear;
        }

        $years = AcademicYear::orderByDesc('start_date')->get();

        $sectionsQuery = Section::with([
            'levels' => fn ($q) => $q->orderBy('order_index'),
        ]);

        $classGroups = collect();

        if ($selectedYear) {
            $classesQuery = ClassGroup::where('academic_year_id', $selectedYear->id)
                ->with(['level.section', 'titularStaff', 'studentEnrollments'])
                ->withCount([
                    'studentEnrollments' => fn ($q) =>
                        $q->where('status', 'active'),
                    'classSubjects',
                ]);

            if ($isTeacher) {
                if (empty($teacherIds)) {
                    $sections = collect();
                } else {
                    $classesQuery->whereIn('id', $teacherIds);
                    $classGroups = $classesQuery->get()->groupBy('level.section.id');

                    $sectionIds = $classGroups->keys()->filter()->values();
                    $sections   = $sectionsQuery->whereIn('id', $sectionIds)->get();
                }
            } else {
                $classGroups = $classesQuery->get()->groupBy('level.section.id');
                $sections    = $sectionsQuery->get();
            }
        } else {
            $sections = $isTeacher ? collect() : $sectionsQuery->get();
        }

        if (! isset($sections)) {
            $sections = collect();
        }

        $stats = [
            'total_classes'  => $classGroups->flatten()->count(),
            'total_students' => $classGroups->flatten()->sum('student_enrollments_count'),
            'sections_used'  => $classGroups->keys()->count(),
        ];

        return view('classes.index', compact(
            'sections', 'classGroups', 'selectedYear',
            'years', 'stats', 'activeYear', 'isTeacher'
        ));
    }

    // ── FORMULAIRE CRÉATION ───────────────────────────────────────────────
    public function create(Request $request)
    {
        $activeYear = AcademicYear::active();

        if (!$activeYear) {
            return redirect()->route('classes.index')
                ->with('error',
                    'Aucune année scolaire active. '
                    . 'Veuillez en activer une avant de créer des classes.');
        }

        $sections  = Section::with([
            'levels' => fn($q) => $q->orderBy('order_index'),
        ])->get();

        $staffList = Staff::where('is_active', true)
                          ->orderBy('last_name')
                          ->get();

        $selectedSectionId = $request->input('section_id');
        $selectedLevelId   = $request->input('level_id');

        return view('classes.create', compact(
            'activeYear', 'sections', 'staffList',
            'selectedSectionId', 'selectedLevelId'
        ));
    }

    // ── ENREGISTREMENT ────────────────────────────────────────────────────
    public function store(StoreClassGroupRequest $request)
    {
        // Verifier l'unicite annee + niveau + sous-groupe.
        $data = $request->validated();
        $level = Level::findOrFail($data['level_id']);
        $data['series'] = '';
        $data['sub_group'] = $this->normalizeOptionalClassPart($data['sub_group'] ?? null);
        $data['name'] = ClassGroup::composeName($level->name, null, $data['sub_group']);

        $exists = $this->classCombinationExists(
            (int) $data['academic_year_id'],
            (int) $data['level_id'],
            $data['sub_group']
        );

        if ($exists) {
            return back()
                ->withInput()
                ->with('error',
                    'Une classe avec ce nom existe déjà dans ce niveau '
                    . 'pour cette année scolaire.');
        }

        $classGroup = ClassGroup::create($data);

        AuditLog::log('created', $classGroup, [], $classGroup->toArray());

        return redirect()
            ->route('classes.show', $classGroup)
            ->with('success',
                "Classe « {$classGroup->full_name} » créée avec succès.");
    }

    private function buildPreviousEvaluationSummary(ClassGroup $classGroup, GradeCalculationService $gradeService): array
    {
        $sequences = $classGroup->academicYear?->sequences()->orderBy('number')->get() ?? collect();
        $orderedSequences = $sequences->sortBy('number')->values();

        $currentSequence = $orderedSequences->last();
        $previousSequence = null;

        if ($currentSequence && $orderedSequences->count() >= 2) {
            $candidateSequences = $orderedSequences->filter(fn ($sequence) => $sequence->id !== $currentSequence->id);
            $previousSequence = $candidateSequences->reverse()->first(fn ($sequence) =>
                $sequence->is_grades_locked || $sequence->isLockedFor($classGroup->id)
            );
        }

        if (! $previousSequence && $orderedSequences->count() >= 2) {
            $previousSequence = $orderedSequences->get($orderedSequences->count() - 2);
        }

        if (! $previousSequence) {
            return [
                'available' => false,
                'sequence' => null,
                'average' => null,
                'success_rate' => null,
                'best_student' => null,
                'weakest_student' => null,
            ];
        }

        $enrollments = $classGroup->studentEnrollments()
            ->where('status', 'active')
            ->with('student')
            ->get();

        $averages = $enrollments->map(function ($enrollment) use ($previousSequence, $gradeService) {
            $average = $gradeService->sequenceAverage($enrollment, $previousSequence);

            return [
                'enrollment' => $enrollment,
                'average' => $average,
            ];
        })->filter(fn ($item) => $item['average'] !== null);

        if ($averages->isEmpty()) {
            return [
                'available' => true,
                'sequence' => $previousSequence,
                'average' => null,
                'success_rate' => null,
                'best_student' => null,
                'weakest_student' => null,
            ];
        }

        $best = $averages->sortByDesc(fn ($item) => $item['average'])->first();
        $weakest = $averages->sortBy(fn ($item) => $item['average'])->first();
        $successRate = round(($averages->filter(fn ($item) => (float) $item['average'] >= 10)->count() / $averages->count()) * 100);

        return [
            'available' => true,
            'sequence' => $previousSequence,
            'average' => round($averages->avg('average'), 2),
            'success_rate' => $successRate,
            'best_student' => $best['enrollment']->student
                ? [
                    'name' => $best['enrollment']->student->full_name,
                    'average' => round((float) $best['average'], 2),
                ]
                : null,
            'weakest_student' => $weakest['enrollment']->student
                ? [
                    'name' => $weakest['enrollment']->student->full_name,
                    'average' => round((float) $weakest['average'], 2),
                ]
                : null,
        ];
    }

    private function buildAnnualEvaluationSummary(ClassGroup $classGroup, GradeCalculationService $gradeService): array
    {
        $enrollments = $classGroup->studentEnrollments()
            ->where('status', 'active')
            ->with('student')
            ->get();

        $averages = $enrollments->map(function ($enrollment) use ($gradeService) {
            $average = $gradeService->yearAverage($enrollment);

            return [
                'enrollment' => $enrollment,
                'average' => $average,
            ];
        })->filter(fn ($item) => $item['average'] !== null);

        if ($averages->isEmpty()) {
            return [
                'average' => null,
                'success_rate' => null,
                'best_student' => null,
                'weakest_student' => null,
            ];
        }

        $best = $averages->sortByDesc(fn ($item) => $item['average'])->first();
        $weakest = $averages->sortBy(fn ($item) => $item['average'])->first();
        $successRate = round(($averages->filter(fn ($item) => (float) $item['average'] >= 10)->count() / $averages->count()) * 100);

        return [
            'average' => round($averages->avg('average'), 2),
            'success_rate' => $successRate,
            'best_student' => $best['enrollment']->student
                ? [
                    'name' => $best['enrollment']->student->full_name,
                    'average' => round((float) $best['average'], 2),
                ]
                : null,
            'weakest_student' => $weakest['enrollment']->student
                ? [
                    'name' => $weakest['enrollment']->student->full_name,
                    'average' => round((float) $weakest['average'], 2),
                ]
                : null,
        ];
    }

    private function buildTimetableData(ClassGroup $classGroup): array
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
        ];

        $setting = TimetableSetting::current();
        $gridService = app(TimetableGridService::class);
        $grid = $gridService->buildGrid($setting, $days);

        $slots = TimetableSlot::where('class_group_id', $classGroup->id)
            ->where('academic_year_id', $classGroup->academic_year_id)
            ->with([
                'classSubject.subject',
                'classSubject.teacherAssignments.staff',
            ])
            ->orderBy('day_of_week')
            ->orderBy('period_index')
            ->orderBy('start_time')
            ->get();

        return [
            'timetableDays' => $days,
            'timetableGridRows' => $grid['rows'],
            'timetableSlots' => $slots,
            'timetableConflicts' => collect(),
            'timetableSetting' => $setting,
        ];
    }

    // ── DÉTAIL ────────────────────────────────────────────────────────────
    public function show(ClassGroup $classGroup)
    {
        $classGroup->load([
            'level.section',
            'academicYear',
            'titularStaff',
            'classSubjects.subject.category',
            'classSubjects.teacherAssignments.staff',
            'studentEnrollments' => fn($q) =>
                $q->where('status', 'active')->with('student'),
        ]);

        $classGroup->studentEnrollments = $classGroup->studentEnrollments
            ->sortBy(fn ($enrollment) => strtolower(
                trim(($enrollment->student?->last_name ?? '') . ' ' . ($enrollment->student?->first_name ?? ''))
            ))
            ->values();

        $stats = [
            'students'     => $classGroup->studentEnrollments->count(),
            'subjects'     => $classGroup->classSubjects->count(),
            'boys'         => $classGroup->studentEnrollments
                                ->filter(fn($e) =>
                                    $e->student?->gender === 'M')->count(),
            'girls'        => $classGroup->studentEnrollments
                                ->filter(fn($e) =>
                                    $e->student?->gender === 'F')->count(),
        ];

        $previousEvaluation = $this->buildPreviousEvaluationSummary(
            $classGroup,
            app(GradeCalculationService::class)
        );
        $annualEvaluation = $this->buildAnnualEvaluationSummary(
            $classGroup,
            app(GradeCalculationService::class)
        );

        return view('classes.show', array_merge(
            compact('classGroup', 'stats', 'previousEvaluation', 'annualEvaluation'),
            $this->buildTimetableData($classGroup)
        ));
    }

    // ── FORMULAIRE MODIFICATION ───────────────────────────────────────────
    public function edit(ClassGroup $classGroup)
    {
        // Vérifier si l'année est clôturée
        if ($classGroup->academicYear->isClosed()) {
            return redirect()
                ->route('classes.show', $classGroup)
                ->with('error',
                    'Cette classe appartient à une année clôturée '
                    . 'et ne peut plus être modifiée.');
        }

        $classGroup->load(['level.section', 'academicYear']);

        $sections  = Section::with([
            'levels' => fn($q) => $q->orderBy('order_index'),
        ])->get();

        $staffList = Staff::where('is_active', true)
                          ->orderBy('last_name')
                          ->get();

        return view('classes.edit',
            compact('classGroup', 'sections', 'staffList'));
    }

    // ── MISE À JOUR ───────────────────────────────────────────────────────
    public function update(UpdateClassGroupRequest $request,
                           ClassGroup $classGroup)
    {
        if ($classGroup->academicYear->isClosed()) {
            return back()->with('error',
                'Année clôturée — modification impossible.');
        }

        // Verifier l'unicite annee + niveau + sous-groupe.
        $data = $request->validated();
        $level = Level::findOrFail($data['level_id']);
        $data['series'] = '';
        $data['sub_group'] = $this->normalizeOptionalClassPart($data['sub_group'] ?? null);
        $data['name'] = ClassGroup::composeName($level->name, null, $data['sub_group']);

        $exists = $this->classCombinationExists(
            (int) $classGroup->academic_year_id,
            (int) $data['level_id'],
            $data['sub_group'],
            $classGroup->id
        );

        if ($exists) {
            return back()->withInput()
                ->with('error',
                    'Une classe avec ce nom existe déjà dans ce niveau.');
        }

        $old = $classGroup->toArray();
        $classGroup->update($data);
        AuditLog::log('updated', $classGroup, $old, $classGroup->toArray());

        return redirect()
            ->route('classes.show', $classGroup)
            ->with('success',
                "Classe « {$classGroup->full_name} » mise à jour.");
    }

    // ── SUPPRESSION ───────────────────────────────────────────────────────
    public function destroy(ClassGroup $classGroup)
    {
        if ($classGroup->academicYear->isClosed()) {
            return back()->with('error',
                'Impossible de supprimer une classe d\'une année clôturée.');
        }

        if ($classGroup->studentEnrollments()->count() > 0) {
            return back()->with('error',
                'Impossible de supprimer cette classe : '
                . 'elle contient des élèves inscrits.');
        }

        $name = $classGroup->full_name;

        // Supprimer les matières et verrous liés
        $classGroup->classSubjects()->delete();
        $classGroup->gradeLocks()->delete();
        $classGroup->delete();

        AuditLog::log('deleted', null, ['name' => $name], []);

        return redirect()
            ->route('classes.index')
            ->with('success', "Classe « {$name} » supprimée.");
    }

    // ── GESTION DES NIVEAUX ───────────────────────────────────────────────
    public function storeLevel(Request $request, Section $section)
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'order_index'   => ['required', 'integer', 'min:1'],
            'is_exam_class' => ['required', 'boolean'],
        ]);

        $exists = Level::where('section_id', $section->id)
                       ->where('name', $request->name)->exists();

        if ($exists) {
            return back()->with('error',
                'Ce niveau existe déjà dans cette section.');
        }

        $level = Level::create([
            'section_id'    => $section->id,
            'name'          => $request->name,
            'order_index'   => $request->order_index,
            'is_exam_class' => $request->boolean('is_exam_class'),
        ]);

        AuditLog::log('created', $level, [], $level->toArray());

        return back()->with('success',
            "Niveau « {$request->name} » ajouté.");
    }

    public function updateLevel(Request $request, Level $level)
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'order_index'   => ['required', 'integer', 'min:1'],
            'is_exam_class' => ['required', 'boolean'],
        ]);

        $old = $level->toArray();
        $level->update([
            'name'          => $request->name,
            'order_index'   => $request->order_index,
            'is_exam_class' => $request->boolean('is_exam_class'),
        ]);

        AuditLog::log('updated', $level, $old, $level->toArray());

        return back()->with('success',
            "Niveau « {$level->name} » mis à jour.");
    }

    public function destroyLevel(Level $level)
    {
        if ($level->classGroups()->count() > 0) {
            return back()->with('error',
                'Impossible de supprimer ce niveau : '
                . 'il contient des classes.');
        }

        $name = $level->name;
        $level->delete();

        return back()->with('success',
            "Niveau « {$name} » supprimé.");
    }

    private function normalizeOptionalClassPart(?string $value): string
    {
        return trim((string) $value);
    }

    private function classCombinationExists(
        int $academicYearId,
        int $levelId,
        string $subGroup,
        ?int $exceptId = null
    ): bool {
        return ClassGroup::where('academic_year_id', $academicYearId)
            ->where('level_id', $levelId)
            ->where('sub_group', $subGroup)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    // ── MISE À JOUR D'UNE SECTION ─────────────────────────────────────────
    public function updateSection(Request $request, Section $section)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'language' => ['required', 'in:fr,en'],
        ]);

        $section->update([
            'name'     => $request->name,
            'language' => $request->language,
        ]);

        return back()->with('success',
            "Section « {$section->name} » mise à jour.");
    }
}
