<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradeRequest;
use App\Models\AppreciationScale;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\GradeLock;
use App\Models\Section;
use App\Models\Sequence;
use App\Models\SchoolAgreement;
use App\Models\SchoolPhone;
use App\Models\SchoolSetting;
use App\Models\StudentEnrollment;
use App\Models\TeacherAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class GradeController extends Controller
{
    // ── HELPERS ───────────────────────────────────────────────────────────

    private function activeYear(): ?AcademicYear
    {
        return AcademicYear::active();
    }

    private function currentUser()
    {
        /** @var \App\Models\User $authUser */
        $user = Auth::user();

        return Auth::user();
    }

    private function isAdmin(): bool
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        return $authUser->hasAnyRole([
            'super-admin', 'directeur', 'censeur', 'secretaire'
        ]);
    }

    /** Sections où l'user enseigne (ou toutes si admin) */
    private function teacherSections(?AcademicYear $year): \Illuminate\Support\Collection
    {
        if ($this->isAdmin() || !$year) {
            return Section::orderBy('id')->get();
        }

        $user  = $this->currentUser();
        $staff = $user->staff;
        if (!$staff) return collect();

        $sectionIds = TeacherAssignment::where('staff_id', $staff->id)
            ->where('academic_year_id', $year->id)
            ->with('classSubject.classGroup.level.section')
            ->get()
            ->pluck('classSubject.classGroup.level.section_id')
            ->unique();

        return Section::whereIn('id', $sectionIds)
            ->orderBy('id')->get();
    }

    /** Matières enseignées dans une section par ce user */
    private function teacherSubjectsInSection(
        int $sectionId, ?AcademicYear $year
    ): \Illuminate\Support\Collection {
        if (!$year) return collect();

        if ($this->isAdmin()) {
            // FIX : whereHas au lieu d'un sous-requête = qui retournait plusieurs lignes
            return ClassSubject::where('is_active', true)
                ->whereHas('classGroup', fn($q) =>
                    $q->where('academic_year_id', $year->id)
                    ->whereHas('level', fn($q2) =>
                        $q2->where('section_id', $sectionId)
                    )
                )
                ->with('subject')
                ->get()
                ->pluck('subject')
                ->filter()
                ->unique('id')
                ->sortBy('name_fr')
                ->values();
        }

        $staff = $this->currentUser()->staff;
        if (!$staff) return collect();

        return TeacherAssignment::where('staff_id', $staff->id)
            ->where('academic_year_id', $year->id)
            ->whereHas('classSubject.classGroup', fn($q) =>
                $q->where('academic_year_id', $year->id)
                ->whereHas('level', fn($q2) =>
                    $q2->where('section_id', $sectionId)
                )
            )
            ->with('classSubject.subject')
            ->get()
            ->pluck('classSubject.subject')
            ->filter()
            ->unique('id')
            ->sortBy('name_fr')
            ->values();
    }

    /** Classes où ce user enseigne un sujet donné dans une section */
    private function teacherClassesForSubject(
        int $sectionId, int $subjectId, ?AcademicYear $year
    ): \Illuminate\Support\Collection {
        if (!$year) return collect();

        $query = ClassGroup::where('academic_year_id', $year->id)
            ->whereHas('level', fn($q) => $q->where('section_id', $sectionId))
            ->whereHas('classSubjects', fn($q) =>
                $q->where('subject_id', $subjectId)->where('is_active', true)
            )
            ->with('level.section')
            ->orderBy('name');

        if (!$this->isAdmin()) {
            $staff = $this->currentUser()->staff;
            if (!$staff) return collect();

            $query->whereHas('classSubjects', fn($q) =>
                $q->where('subject_id', $subjectId)
                  ->where('is_active', true)
                  ->whereHas('teacherAssignments', fn($q2) =>
                      $q2->where('staff_id', $staff->id)
                         ->where('academic_year_id', $year->id)
                  )
            );
        }

        return $query->get();
    }

    // // ── LISTE DES CLASSES PAR SÉQUENCE ───────────────────────────────────
    public function index(Request $request)
    {
        $activeYear = $this->activeYear();
        $sections   = $activeYear
            ? Section::with([
                'levels.classGroups' => fn($q) =>
                    $q->where('academic_year_id', $activeYear->id)
                      ->withCount([
                          'studentEnrollments as enrolled' => fn($q2) =>
                              $q2->where('status', 'active'),
                          'classSubjects as subjects_count' => fn($q2) =>
                              $q2->where('is_active', true),
                      ])
                      ->orderBy('name'),
            ])->orderBy('id')->get()
            : collect();

        $sequences = $activeYear
            ? Sequence::where('academic_year_id', $activeYear->id)
                ->with('trimester')->orderBy('number')->get()
            : collect();

        // Sélection de section (filtre)
        $selectedSectionId = $request->input('section_id',
            optional($sections->first())->id);
        $selectedSection   = $sections->firstWhere('id', $selectedSectionId);

        // Verrous
        $locks = GradeLock::where('is_locked', true)
            ->get()->groupBy('class_group_id');

        // Nombres de notes saisies
        $gradeCounts = $activeYear
            ? Grade::selectRaw(
                'class_subjects.class_group_id,
                 grades.sequence_id,
                 COUNT(*) as cnt'
            )->join('class_subjects',
                'class_subjects.id', '=', 'grades.class_subject_id')
             ->join('class_groups',
                'class_groups.id', '=', 'class_subjects.class_group_id')
             ->join('student_enrollments',
                'student_enrollments.id', '=', 'grades.student_enrollment_id')
             ->where('class_groups.academic_year_id', $activeYear->id)
             ->where('class_subjects.is_active', true)
             ->where('student_enrollments.status', 'active')
             ->where('student_enrollments.academic_year_id', $activeYear->id)
             ->where(fn($q) =>
                 $q->whereNotNull('grades.grade')
                   ->orWhere('grades.is_absent', true)
             )
             ->groupBy('class_subjects.class_group_id', 'grades.sequence_id')
             ->get()->groupBy('class_group_id')
            : collect();

        return view('grades.index', compact(
            'activeYear', 'sections', 'sequences',
            'selectedSection', 'selectedSectionId',
            'locks', 'gradeCounts'
        ));
    }

    // ══ PAGE NOTES (consultation enseignant) ══════════════════════════════

    public function notes(Request $request)
    {
        $activeYear = $this->activeYear();
        $sections   = $this->teacherSections($activeYear);

        $selectedSectionId  = $request->input('section_id');
        $selectedSubjectId  = $request->input('subject_id');
        $selectedClassId    = $request->input('class_id');
        $selectedSequenceId = $request->input('sequence_id');

        $sequences = $activeYear
            ? Sequence::where('academic_year_id', $activeYear->id)
                ->with('trimester')->orderBy('number')->get()
            : collect();

        // Matières disponibles dans la section
        $subjects = $selectedSectionId
            ? $this->teacherSubjectsInSection((int)$selectedSectionId, $activeYear)
            : collect();

        // Classes disponibles
        $classes = ($selectedSectionId && $selectedSubjectId)
            ? $this->teacherClassesForSubject(
                (int)$selectedSectionId, (int)$selectedSubjectId, $activeYear)
            : collect();

        // Notes si tout est sélectionné
        $enrollments   = collect();
        $grades        = collect();
        $classSubject  = null;
        $selectedClass = null;
        $sequence      = null;

        if ($selectedClassId && $selectedSubjectId && $selectedSequenceId) {
            $selectedClass = ClassGroup::where('academic_year_id', $activeYear?->id)
                ->find($selectedClassId);
            $sequence = Sequence::where('academic_year_id', $activeYear?->id)
                ->find($selectedSequenceId);

            $classSubject = ClassSubject::where([
                'class_group_id' => $selectedClassId,
                'subject_id'     => $selectedSubjectId,
                'is_active'      => true,
            ])->with('subject')->first();

            if ($selectedClass && $sequence && $classSubject) {
                $enrollments = StudentEnrollment::where([
                    'class_group_id'   => $selectedClassId,
                    'academic_year_id' => $activeYear?->id,
                    'status'           => 'active',
                ])->with('student')
                  ->get()
                  ->sortBy('student.last_name');

                $grades = Grade::whereIn(
                    'student_enrollment_id', $enrollments->pluck('id')
                )->where([
                    'class_subject_id' => $classSubject->id,
                    'sequence_id'      => $selectedSequenceId,
                ])->get()->keyBy('student_enrollment_id');
            }
        }

        $appreciationJs = AppreciationScale::toJsArray();

        return view('grades.notes', compact(
            'activeYear', 'sections', 'sequences', 'subjects', 'classes',
            'selectedSectionId', 'selectedSubjectId',
            'selectedClassId', 'selectedSequenceId',
            'enrollments', 'grades', 'classSubject',
            'selectedClass', 'sequence', 'appreciationJs'
        ));
    }

    // // ── FORMULAIRE DE SAISIE ─────────────────────────────────────────────
    
    // ══ SAISIE DES NOTES ═══════════════════════════════════════════════════

    public function entry(Request $request)
    {
        $activeYear = $this->activeYear();
        $sections   = $this->teacherSections($activeYear);

        $selectedSectionId  = $request->input('section_id');
        $selectedSubjectId  = $request->input('subject_id');
        $selectedClassId    = $request->input('class_id');
        $selectedSequenceId = $request->input('sequence_id');

        $sequences = $activeYear
            ? Sequence::where('academic_year_id', $activeYear->id)
                ->with('trimester')->orderBy('number')->get()
            : collect();

        $subjects = $selectedSectionId
            ? $this->teacherSubjectsInSection((int)$selectedSectionId, $activeYear)
            : collect();

        $classes = ($selectedSectionId && $selectedSubjectId)
            ? $this->teacherClassesForSubject(
                (int)$selectedSectionId, (int)$selectedSubjectId, $activeYear)
            : collect();

        // Si tout sélectionné → charger les données
        $enrollments   = collect();
        $grades        = collect();
        $classSubject  = null;
        $selectedClass = null;
        $sequence      = null;
        $isLocked      = false;
        $lock          = null;

        $readyToShow = $selectedClassId && $selectedSubjectId
                    && $selectedSequenceId && $activeYear;

        if ($readyToShow) {
            $selectedClass = ClassGroup::with('level.section', 'academicYear')
                ->find($selectedClassId);
            $sequence      = Sequence::with('trimester')
                ->find($selectedSequenceId);

            $classSubject = ClassSubject::where([
                'class_group_id' => $selectedClassId,
                'subject_id'     => $selectedSubjectId,
                'is_active'      => true,
            ])->with('subject')->first();

            if ($classSubject && $selectedClass && $sequence) {
                $lock = GradeLock::where([
                    'class_group_id' => $selectedClassId,
                    'sequence_id'    => $selectedSequenceId,
                ])->first();
                $isLocked = $lock?->is_locked ?? false;

                $enrollments = StudentEnrollment::where([
                    'class_group_id'   => $selectedClassId,
                    'academic_year_id' => $activeYear->id,
                    'status'           => 'active',
                ])->with('student')
                  ->get()
                  ->sortBy('student.last_name');

                $grades = Grade::whereIn(
                    'student_enrollment_id', $enrollments->pluck('id')
                )->where([
                    'class_subject_id' => $classSubject->id,
                    'sequence_id'      => $selectedSequenceId,
                ])->get()->keyBy('student_enrollment_id');
            }
        }

        $appreciationJs = AppreciationScale::toJsArray();

        return view('grades.entry', compact(
            'activeYear', 'sections', 'sequences', 'subjects', 'classes',
            'selectedSectionId', 'selectedSubjectId',
            'selectedClassId', 'selectedSequenceId',
            'enrollments', 'grades', 'classSubject',
            'selectedClass', 'sequence',
            'readyToShow', 'isLocked', 'lock', 'appreciationJs'
        ));
    }

    // // ── ENREGISTREMENT DES NOTES ─────────────────────────────────────────
    
    // ══ ENREGISTREMENT (FIX BD) ════════════════════════════════════════════

    public function save(Request $request)
    {
        $request->validate([
            'sequence_id'      => ['required', 'exists:sequences,id'],
            'class_subject_id' => ['required', 'exists:class_subjects,id'],
            'class_group_id'   => ['required', 'exists:class_groups,id'],
        ]);

        // Vérifier verrou
        $isLocked = GradeLock::where([
            'class_group_id' => $request->class_group_id,
            'sequence_id'    => $request->sequence_id,
            'is_locked'      => true,
        ])->exists();

        if ($isLocked) {
            return back()->with('error',
                'Ces notes sont verrouillées. Modification impossible.');
        }

        $classSubject = ClassSubject::findOrFail($request->class_subject_id);
        if ((int) $classSubject->class_group_id !== (int) $request->class_group_id) {
            abort(422, 'La matière ne correspond pas à la classe sélectionnée.');
        }
        $gradingScale = (float) ($classSubject->grading_scale ?: 20);

        // Vérifier permission enseignant
        $user = $this->currentUser();
        if (!$this->isAdmin()) {
            $staff = $user->staff;
            if (!$staff) abort(403);

            $isAssigned = TeacherAssignment::where([
                'staff_id'         => $staff->id,
                'class_subject_id' => $request->class_subject_id,
            ])->whereHas('academicYear', fn($q) => $q->where('is_active', true))
              ->exists();

            if (!$isAssigned) abort(403, 'Non assigné à cette matière.');
        }

        $gradesInput = $request->input('grades', []);
        $absentInput = $request->input('absent', []);
        $saved       = 0;
        $errors      = 0;

        foreach ($gradesInput as $enrollmentId => $grade) {
            $enrollmentId = (int)$enrollmentId;
            if ($enrollmentId <= 0) continue;

            $isAbsent = array_key_exists($enrollmentId, $absentInput);
            $gradeVal = null;
            $rawGrade = null;

            if (!$isAbsent && $grade !== '' && $grade !== null) {
                $v = (float)$grade;
                if ($v < 0 || $v > $gradingScale) { $errors++; continue; }
                $rawGrade = round($v, 2);
                $gradeVal = round(($v * 20) / $gradingScale, 2);
            }

            Grade::updateOrCreate(
                [
                    'student_enrollment_id' => $enrollmentId,
                    'class_subject_id'      => (int)$request->class_subject_id,
                    'sequence_id'           => (int)$request->sequence_id,
                ],
                [
                    'grade'      => $gradeVal,
                    'raw_grade'  => $rawGrade,
                    'is_absent'  => $isAbsent,
                    'entered_by' => $user->id,
                    'entered_at' => now(),
                    'updated_by' => $user->id,
                ]
            );
            $saved++;
        }

        AuditLog::log('grades_saved', ClassGroup::find($request->class_group_id));

        $msg = "{$saved} note(s) enregistrée(s).";
        if ($errors > 0) $msg .= " {$errors} note(s) invalide(s) ignorée(s).";

        return redirect()->back()->with('success', $msg);
    }

    // ══ DÉTAIL (clic sur % dans Vue Globale) ══════════════════════════════

    public function detail(Request $request,
                           ClassGroup $classGroup,
                           Sequence $sequence)
    {
        $classGroup->load([
            'level.section', 'academicYear',
            'classSubjects' => fn($q) =>
                $q->where('is_active', true)->with('subject')->orderBy('subject_id'),
        ]);

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->with('student')
          ->get()
          ->sortBy('student.last_name');

        // Filtre matière optionnel
        $filterSubjectId = $request->input('subject_id');
        $subjects = $classGroup->classSubjects;
        $displaySubjects = $filterSubjectId
            ? $subjects->where('subject_id', $filterSubjectId)
            : $subjects;

        // Charger toutes les notes de cette séquence pour cette classe
        $allGrades = Grade::whereIn(
            'student_enrollment_id', $enrollments->pluck('id')
        )->where('sequence_id', $sequence->id)
         ->whereIn('class_subject_id', $subjects->pluck('id'))
         ->get()
         ->groupBy('student_enrollment_id')
         ->map(fn($g) => $g->keyBy('class_subject_id'));

        $lock = GradeLock::where([
            'class_group_id' => $classGroup->id,
            'sequence_id'    => $sequence->id,
        ])->first();

        return view('grades.detail', compact(
            'classGroup', 'sequence', 'enrollments',
            'subjects', 'displaySubjects', 'filterSubjectId',
            'allGrades', 'lock'
        ));
    }

    public function bordereau(ClassGroup $classGroup, Sequence $sequence)
    {
        $data = $this->buildBordereauData($classGroup, $sequence);
        $data['school'] = SchoolSetting::instance();
        $data['phones'] = SchoolPhone::orderByDesc('is_primary')->get();
        $data['agreements'] = SchoolAgreement::orderBy('id')->get();
        $data['forPdf'] = false;
        $data['showCertificateTitle'] = false;

        return view('grades.bordereau', $data);
    }

    public function bordereauPdf(ClassGroup $classGroup, Sequence $sequence)
    {
        $data = $this->buildBordereauData($classGroup, $sequence);
        $data['school'] = SchoolSetting::instance();
        $data['phones'] = SchoolPhone::orderByDesc('is_primary')->get();
        $data['agreements'] = SchoolAgreement::orderBy('id')->get();
        $data['forPdf'] = true;
        $data['showCertificateTitle'] = false;

        $filename = 'bordereau-' . str_replace(' ', '-', $classGroup->full_name)
                  . '-' . str_replace(' ', '-', $sequence->label) . '.pdf';

        $pdf = Pdf::loadView('grades.bordereau-pdf', $data)
                  ->setPaper('a4', 'landscape');

        return $pdf->stream($filename);
    }

    private function buildBordereauData(ClassGroup $classGroup, Sequence $sequence): array
    {
        $classGroup->load([
            'level.section',
            'academicYear',
            'classSubjects' => fn($q) =>
                $q->where('is_active', true)
                  ->with('subject')
                  ->orderBy('subject_id'),
        ]);

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->with('student')
          ->get()
          ->sortBy(fn($enr) => $enr->student->last_name)
          ->values();

        $subjects = $classGroup->classSubjects;

        $allGrades = Grade::whereIn(
            'student_enrollment_id', $enrollments->pluck('id')
        )->where('sequence_id', $sequence->id)
         ->whereIn('class_subject_id', $subjects->pluck('id'))
         ->get()
         ->groupBy('student_enrollment_id')
         ->map(fn($g) => $g->keyBy('class_subject_id'));

        $studentAverages = $enrollments->mapWithKeys(function ($enr) use ($subjects, $allGrades) {
            $grades = $allGrades->get($enr->id);
            $totalPoints = 0;
            $totalCoef = 0;

            foreach ($subjects as $cs) {
                $grade = $grades?->get($cs->id)?->grade;
                if ($grade !== null) {
                    $totalPoints += $grade * $cs->coefficient;
                    $totalCoef += $cs->coefficient;
                }
            }

            return [
                $enr->id => [
                    'average' => $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : null,
                    'total_coef' => $totalCoef,
                ],
            ];
        });

        $rankOrder = $studentAverages
            ->sortByDesc(fn($row) => $row['average'] ?? -1)
            ->keys()
            ->values();

        $studentRanks = [];
        foreach ($rankOrder as $index => $enrollmentId) {
            $studentRanks[$enrollmentId] = $index + 1;
        }

        $enrollments = $enrollments->sortBy(fn($enr) => $studentRanks[$enr->id] ?? PHP_INT_MAX)
                                   ->values();

        $boys = $enrollments->filter(fn($enr) => strtoupper($enr->student->gender) === 'M')->count();
        $girls = $enrollments->filter(fn($enr) => strtoupper($enr->student->gender) === 'F')->count();

        $subjectSummaries = $subjects->mapWithKeys(function ($subject) use ($enrollments, $allGrades) {
            $sum = 0;
            $count = 0;
            $passed = 0;
            $passedBoys = 0;
            $passedGirls = 0;
            $boysGraded = 0;
            $girlsGraded = 0;
            $minGrade = null;
            $maxGrade = null;

            foreach ($enrollments as $enrollment) {
                $grade = $allGrades->get($enrollment->id)?->get($subject->id)?->grade;
                if ($grade === null) {
                    continue;
                }

                $count++;
                $sum += $grade;
                if ($grade >= 10) {
                    $passed++;
                }

                if (strtoupper($enrollment->student->gender) === 'M') {
                    $boysGraded++;
                    if ($grade >= 10) {
                        $passedBoys++;
                    }
                } else {
                    $girlsGraded++;
                    if ($grade >= 10) {
                        $passedGirls++;
                    }
                }

                $minGrade = $minGrade === null ? $grade : min($minGrade, $grade);
                $maxGrade = $maxGrade === null ? $grade : max($maxGrade, $grade);
            }

            return [
                $subject->id => [
                    'average' => $count > 0 ? round($sum / $count, 2) : null,
                    'success_rate' => $count > 0 ? round($passed * 100 / $count, 2) : null,
                    'success_rate_boys' => $boysGraded > 0 ? round($passedBoys * 100 / $boysGraded, 2) : null,
                    'success_rate_girls' => $girlsGraded > 0 ? round($passedGirls * 100 / $girlsGraded, 2) : null,
                    'min' => $minGrade,
                    'max' => $maxGrade,
                ],
            ];
        });

        $totalCoefficient = $subjects->sum('coefficient');

        return compact(
            'classGroup', 'sequence', 'enrollments',
            'subjects', 'allGrades', 'studentAverages',
            'studentRanks', 'boys', 'girls',
            'subjectSummaries', 'totalCoefficient'
        );
    }

    // // ── VERROUILLER / DÉVERROUILLER ──────────────────────────────────────
    
    // ══ VERROU ══════════════════════════════════════════════════════════════

    public function toggleLock(ClassGroup $classGroup, Sequence $sequence)
    {
        if (!$this->isAdmin()) abort(403);

        $lock = GradeLock::firstOrCreate([
            'class_group_id' => $classGroup->id,
            'sequence_id'    => $sequence->id,
        ], ['is_locked' => false]);

        $lock->update([
            'is_locked' => !$lock->is_locked,
            'locked_by' => Auth::id(),
            'locked_at' => $lock->is_locked ? null : now(),
        ]);

        AuditLog::log(
            'grades_' . ($lock->is_locked ? 'locked' : 'unlocked'),
            $classGroup
        );

        return back()->with('success',
            $lock->is_locked
                ? "Notes de {$classGroup->full_name} verrouillées."
                : "Notes de {$classGroup->full_name} déverrouillées."
        );
    }

    // // ══ API AJAX ═══════════════════════════════════════════════════════════

    // ── API AJAX (avec gestion d'erreur) ──────────────────────────────────────

    public function apiSubjects(Request $request)
    {
        try {
            $sectionId  = (int)$request->input('section_id', 0);
            $activeYear = $this->activeYear();

            if (!$sectionId || !$activeYear) {
                return response()->json(['subjects' => []]);
            }

            $subjects = $this->teacherSubjectsInSection($sectionId, $activeYear)
                ->map(fn($s) => [
                    'id'   => $s->id,
                    'name' => $s->name_fr,
                    'code' => $s->code ?? '',
                ]);

            return response()->json(['subjects' => $subjects->values()]);

        } catch (\Throwable $e) {
            \Log::error('apiSubjects error: ' . $e->getMessage());
            return response()->json(['subjects' => [], 'error' => $e->getMessage()], 500);
        }
    }

    public function apiClasses(Request $request)
    {
        try {
            $sectionId  = (int)$request->input('section_id', 0);
            $subjectId  = (int)$request->input('subject_id', 0);
            $activeYear = $this->activeYear();

            if (!$sectionId || !$subjectId || !$activeYear) {
                return response()->json(['classes' => []]);
            }

            $classes = $this->teacherClassesForSubject($sectionId, $subjectId, $activeYear)
                ->map(fn($c) => [
                    'id'        => $c->id,
                    'full_name' => $c->full_name,
                    'enrolled'  => $c->studentEnrollments()
                        ->where('status', 'active')->count(),
                ]);

            return response()->json(['classes' => $classes->values()]);

        } catch (\Throwable $e) {
            \Log::error('apiClasses error: ' . $e->getMessage());
            return response()->json(['classes' => [], 'error' => $e->getMessage()], 500);
        }
    }
}
