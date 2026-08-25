<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbsenceRequest;
use App\Models\Absence;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\ClassSubject;
use App\Models\Section;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbsenceController extends Controller
{
    // // ── VUE GLOBALE DES ABSENCES (CODEX) ─────────────────────────────────────────
    // public function index(Request $request)
    // {
    //     $activeYear = AcademicYear::active();

    //     $sections = collect();
    //     if ($activeYear) {
    //         $sections = Section::with([
    //             'levels.classGroups' => fn($q) =>
    //                 $q->where('academic_year_id', $activeYear->id)
    //                   ->withCount([
    //                       'studentEnrollments as enrolled' => fn($q2) =>
    //                           $q2->where('status', 'active'),
    //                   ])
    //                   ->orderBy('name'),
    //         ])->orderBy('id')->get();
    //     }

    //     // Stats globales des absences
    //     $totalAbsenceHours = Absence::when($activeYear, fn($q) =>
    //         $q->whereHas('studentEnrollment', fn($q2) =>
    //             $q2->where('academic_year_id', $activeYear->id)
    //         )
    //     )->sum('hours');

    //     $unjustifiedHours = Absence::when($activeYear, fn($q) =>
    //         $q->whereHas('studentEnrollment', fn($q2) =>
    //             $q2->where('academic_year_id', $activeYear->id)
    //         )
    //     )->where('is_justified', false)->sum('hours');

    //     $justifiedHours = Absence::when($activeYear, fn($q) =>
    //         $q->whereHas('studentEnrollment', fn($q2) =>
    //             $q2->where('academic_year_id', $activeYear->id)
    //         )
    //     )->where('is_justified', true)->sum('hours');

    //     // Absences récentes (toutes classes)
    //     $recentAbsences = Absence::with([
    //         'studentEnrollment.student',
    //         'studentEnrollment.classGroup.level.section',
    //         'classSubject.subject',
    //         'recordedBy',
    //     ])
    //     ->when($activeYear, fn($q) =>
    //         $q->whereHas('studentEnrollment', fn($q2) =>
    //             $q2->where('academic_year_id', $activeYear->id)
    //         )
    //     )
    //     ->orderByDesc('absence_date')
    //     ->orderByDesc('created_at')
    //     ->take(15)
    //     ->get();

    //     // Top absentéistes
    //     $topAbsentees = StudentEnrollment::where('status', 'active')
    //         ->when($activeYear, fn($q) =>
    //             $q->where('academic_year_id', $activeYear->id)
    //         )
    //         ->withSum('absences as total_hours', 'hours')
    //         ->withSum(['absences as unjustified_hours' => fn($q) =>
    //             $q->where('is_justified', false)
    //         ], 'hours')
    //         ->having('total_hours', '>', 0)
    //         ->orderByDesc('total_hours')
    //         ->with('student', 'classGroup.level.section')
    //         ->take(10)
    //         ->get();

    //     return view('absences.index', compact(
    //         'activeYear', 'sections',
    //         'totalAbsenceHours', 'unjustifiedHours', 'justifiedHours',
    //         'recentAbsences', 'topAbsentees'
    //     ));
    // }

    // ── LISTE GLOBALE ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $activeYear        = AcademicYear::active();
        $sections          = Section::orderBy('id')->get();
        $selectedSectionId = $request->input('section_id');
        $selectedClassId   = $request->input('class_id');

        $classes = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->when($selectedSectionId, fn ($q) =>
                    $q->whereHas('level', fn ($q2) =>
                        $q2->where('section_id', $selectedSectionId)
                    )
                )
                ->with('level.section')
                ->orderBy('name')->get()
            : collect();

        $selectedClass = $selectedClassId
            ? ClassGroup::with('level.section')->find($selectedClassId)
            : null;

        // Stats d'absences par élève pour la classe sélectionnée
        $enrollments = collect();
        if ($selectedClass) {
            $enrollments = StudentEnrollment::where([
                'class_group_id'   => $selectedClass->id,
                'academic_year_id' => $activeYear?->id,
                'status'           => 'active',
            ])->with(['student',
                'absences' => fn($q) => $q->with('classSubject.subject')
                    ->orderByDesc('absence_date'),
            ])->get()
              ->sortBy('student.last_name')
              ->map(function($enr) {
                  $enr->total_hours       = $enr->absences->sum('hours');
                  $enr->justified_hours   = $enr->absences->where('is_justified', true)->sum('hours');
                  $enr->unjustified_hours = $enr->absences->where('is_justified', false)->sum('hours');
                  return $enr;
              });
        }

        // Absences récentes (filtrées par classe ou section)
        $recentAbsences = Absence::whereHas('studentEnrollment', function ($q) use ($selectedClassId, $selectedSectionId, $activeYear) {
            $q->where('status', 'active')
              ->when($activeYear, fn($q2) => $q2->where('academic_year_id', $activeYear->id))
              ->when($selectedClassId, fn($q2) => $q2->where('class_group_id', $selectedClassId))
              ->when(!$selectedClassId && $selectedSectionId, fn($q2) => $q2->whereHas('classGroup.level', fn ($q3) =>
                    $q3->where('section_id', $selectedSectionId)
                ));
        })->with([
            'studentEnrollment.student',
            'studentEnrollment.classGroup',
            'classSubject.subject',
            'recordedBy',
        ])->orderByDesc('created_at')
          ->orderByDesc('absence_date')
          ->take(20)->get();

        return view('absences.index', compact(
            'activeYear', 'sections', 'classes',
            'selectedClass', 'enrollments', 'recentAbsences',
            'selectedSectionId'
        ));
    }

    // ── FORMULAIRE DE SAISIE ──────────────────────────────────────────────
    public function create(Request $request)
    {
        $activeYear = AcademicYear::active();
        $sections   = Section::orderBy('id')->get();
        $classes    = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->with(['level.section', 'classSubjects.subject'])
                ->orderBy('name')->get()
            : collect();

        $preClassId     = $request->input('class_id');
        $preClass       = $preClassId ? $classes->firstWhere('id', $preClassId) : null;
        $preSectionId   = $preClass?->level?->section_id;
        $classesJson    = $this->classesJsonForForms($classes);

        return view('absences.create', compact(
            'activeYear', 'sections', 'classes', 'preClass', 'preSectionId', 'classesJson'
        ));
    }

    private function classesJsonForForms($classes): array
    {
        return $classes->map(fn ($c) => [
            'id'         => $c->id,
            'name'       => $c->full_name,
            'section_id' => $c->level?->section_id,
        ])->values()->all();
    }

    // ── SAISIE DES ABSENCES D'UNE CLASSE ─────────────────────────────────
    public function classView(ClassGroup $classGroup)
    {
        $classGroup->load([
            'level.section',
            'academicYear',
            'classSubjects' => fn($q) =>
                $q->where('is_active', true)->with('subject')->orderBy('subject_id'),
        ]);

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->with([
            'student',
            'absences' => fn($q) => $q->orderByDesc('absence_date'),
        ])->get()->sortBy('student.last_name');

        // Absences récentes de la classe (30 derniers jours)
        $recentAbsences = Absence::whereHas('studentEnrollment', fn($q) =>
            $q->where('class_group_id', $classGroup->id)
        )
        ->with([
            'studentEnrollment.student',
            'classSubject.subject',
            'recordedBy',
        ])
        ->orderByDesc('absence_date')
        ->take(50)
        ->get();

        return view('absences.class', compact(
            'classGroup', 'enrollments', 'recentAbsences'
        ));
    }

    // // ── ENREGISTREMENT D'UNE ABSENCE ─────────────────────────────────────
    // public function store(Request $request, ClassGroup $classGroup)
    // {
    //     $validated = $request->validate([
    //         'student_enrollment_id' => 'required|exists:student_enrollments,id',
    //         'absence_date'          => 'required|date',
    //         'period'                => 'nullable|string|max:50',
    //         'class_subject_id'      => 'nullable|exists:class_subjects,id',
    //         'hours'                 => 'required|numeric|min:0.5|max:8',
    //         'justification'         => 'nullable|string|max:500',
    //         'is_justified'          => 'boolean',
    //     ]);

    //     $validated['recorded_by'] = Auth::id();
    //     $validated['is_justified'] = $request->boolean('is_justified');

    //     $absence = Absence::create($validated);
    //     AuditLog::log('absence_recorded', $absence);

    //     return back()->with('success', 'Absence enregistrée avec succès.');
    // }

    // ── ENREGISTREMENT ────────────────────────────────────────────────────
    public function store(StoreAbsenceRequest $request)
    {
        $classGroup  = ClassGroup::find($request->class_group_id);
        $activeYear  = AcademicYear::active();

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $activeYear?->id,
            'status'           => 'active',
        ])->pluck('id', 'student_id');

        $saved = 0;
        foreach ($request->input('absences', []) as $enrollmentId => $data) {
            if (empty($data['hours']) || (float)$data['hours'] <= 0) continue;

            Absence::updateOrCreate(
                [
                    'student_enrollment_id' => (int)$enrollmentId,
                    'absence_date'          => $request->absence_date,
                    'period'                => $request->period ?? 'journée',
                    'class_subject_id'      => $request->class_subject_id ?: null,
                ],
                [
                    'hours'        => (float)$data['hours'],
                    'is_justified' => false,
                    'recorded_by'  => Auth::id(),
                ]
            );
            $saved++;
        }

        return redirect()
            ->route('absences.index', ['class_id' => $classGroup->id])
            ->with('success', "{$saved} absence(s) enregistrée(s).");
    }

    // ── ABSENCES D'UN ÉLÈVE ───────────────────────────────────────────────
    public function student(StudentEnrollment $enrollment)
    {
        $filters = request()->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $enrollment->load([
            'student',
            'classGroup.level.section',
            'academicYear',
            'absences' => fn($q) =>
                $q->with(['classSubject.subject', 'recordedBy', 'timetableSlot'])
                  ->when($startDate, fn($query) => $query->whereDate('absence_date', '>=', $startDate))
                  ->when($endDate, fn($query) => $query->whereDate('absence_date', '<=', $endDate))
                  ->orderByDesc('absence_date'),
        ]);

        $totalH        = $enrollment->absences->sum('hours');
        $justifiedH    = $enrollment->absences->where('is_justified', true)->sum('hours');
        $unjustifiedH  = $enrollment->absences->where('is_justified', false)->sum('hours');

        return view('absences.student', compact(
            'enrollment', 'totalH', 'justifiedH', 'unjustifiedH', 'startDate', 'endDate'
        ));
    }

    // // ── JUSTIFIER / DÉ-JUSTIFIER UNE ABSENCE ─────────────────────────────
    // public function justify(Request $request, Absence $absence)
    // {
    //     $validated = $request->validate([
    //         'is_justified'  => 'required|boolean',
    //         'justification' => 'nullable|string|max:500',
    //     ]);

    //     $absence->update($validated);
    //     AuditLog::log('absence_updated', $absence);

    //     $msg = $validated['is_justified']
    //         ? 'Absence justifiée.'
    //         : 'Justification retirée.';

    //     return back()->with('success', $msg);
    // }

    // ── JUSTIFIER ─────────────────────────────────────────────────────────
    public function justify(Request $request, Absence $absence)
    {
        $request->validate([
            'justification' => ['nullable', 'string', 'max:255'],
        ]);

        $absence->update([
            'is_justified'  => !$absence->is_justified,
            'justification' => $request->justification,
        ]);

        return back()->with('success',
            $absence->is_justified
                ? 'Absence justifiée.'
                : 'Absence marquée comme injustifiée.');
    }

    // ── SUPPRIMER UNE ABSENCE ─────────────────────────────────────────────
    public function destroy(Absence $absence)
    {
        AuditLog::log('absence_deleted', $absence);
        $absence->delete();

        return back()->with('success', 'Absence supprimée.');
    }

    // ── API : Élèves d'une classe pour saisie ─────────────────────────────
    public function apiStudents(Request $request)
    {
        $classId    = (int)$request->input('class_id');
        $activeYear = AcademicYear::active();

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classId,
            'academic_year_id' => $activeYear?->id,
            'status'           => 'active',
        ])->with('student')
          ->get()
          ->sortBy('student.last_name')
          ->map(fn($e) => [
              'id'         => $e->id,
              'full_name'  => $e->student->full_name,
              'matricule'  => $e->student->matricule,
              'gender'     => $e->student->gender,
          ])->values();

        return response()->json(['enrollments' => $enrollments]);
    }
}
