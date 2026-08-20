<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateBulletinRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BulletinReport;
use App\Models\BulletinSubjectDetail;
use App\Models\ClassGroup;
use App\Models\CouncilDecision;
use App\Models\Distinction;
use App\Models\Grade;
use App\Models\Sequence;
use App\Models\StudentEnrollment;
use App\Models\Trimester;
use App\Models\Staff;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;

class BulletinController extends Controller
{
    public function __construct(
        private GradeCalculationService $calc
    ) {}

    // ── PAGE DE SÉLECTION ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $activeYear = AcademicYear::active();

        $sections = \App\Models\Section::orderBy('id')->get();

        $classes = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->with('level.section')
                ->withCount(['studentEnrollments as enrolled' => fn($q) =>
                    $q->where('status', 'active')
                ])
                ->orderBy('name')->get()
            : collect();

        $sequences  = $activeYear
            ? Sequence::where('academic_year_id', $activeYear->id)
                ->with('trimester')->orderBy('number')->get()
            : collect();

        $trimesters = $activeYear
            ? Trimester::where('academic_year_id', $activeYear->id)
                ->orderBy('number')->get()
            : collect();

        return view('bulletins.index', compact(
            'activeYear', 'sections', 'classes', 'sequences', 'trimesters'
        ));
    }

    // ── APERÇU D'UN BULLETIN (HTML) ────────────────────────────────────────
    public function show(Request $request, StudentEnrollment $enrollment)
    {
        $type        = $request->input('type', 'sequentiel');
        $sequenceId  = $request->input('sequence_id');
        $trimesterId = $request->input('trimester_id');

        $bulletinData = $this->buildBulletinData(
            $enrollment, $type, $sequenceId, $trimesterId, false
        );

        return view('bulletins.show', $bulletinData);
    }

    // ── GÉNÉRATION PDF INDIVIDUELLE ─────────────────────────────────────────
    public function pdf(Request $request, StudentEnrollment $enrollment)
    {
        $type        = $request->input('type', 'sequentiel');
        $sequenceId  = $request->input('sequence_id');
        $trimesterId = $request->input('trimester_id');

        $data = $this->buildBulletinData(
            $enrollment, $type, $sequenceId, $trimesterId, true
        );

        $pdf = Pdf::loadView('bulletins.pdf', $data)
                  ->setPaper('a4', 'portrait');

        $filename = 'bulletin-'
            . str_replace(' ', '-', $enrollment->student->full_name)
            . '-' . $data['periodLabel'] . '.pdf';

        return $pdf->download($filename);
    }

    public function signedPdf(Request $request, StudentEnrollment $enrollment)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        return $this->pdf($request, $enrollment);
    }

    // ── GÉNÉRATION EN MASSE (CLASSE ENTIÈRE) ───────────────────────────────
    public function bulkPdf(GenerateBulletinRequest $request)
    {
        $classGroup = ClassGroup::with('level.section')
            ->find($request->class_group_id);

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->with('student')
          ->get()
          ->sortBy('student.last_name');

        // Filtrer si sélection partielle
        if ($request->filled('student_ids')) {
            $enrollments = $enrollments->whereIn('id', $request->student_ids);
        }

        if ($enrollments->isEmpty()) {
            return back()->with('error', 'Aucun élève sélectionné.');
        }

        $allBulletins = [];
        foreach ($enrollments as $enr) {
            $allBulletins[] = $this->buildBulletinData(
                $enr,
                $request->type,
                $request->sequence_id,
                $request->trimester_id,
                false
            );
        }

        AuditLog::log('bulletins_generated', $classGroup);

        return view('bulletins.bulk-print', [
            'bulletins'  => $allBulletins,
            'classGroup' => $classGroup,
        ]);
    }

    private function getSequenceOfficialTitle(string $label): string
    {
        $labelUpper = strtoupper(trim($label));
        if (preg_match('/CC\s*([1-3])/', $labelUpper, $matches)) {
            return "CONTROLE CONTINU " . $matches[1];
        }
        if (preg_match('/DS\s*([1-5])/', $labelUpper, $matches)) {
            return "DEVOIR SURVEILLE " . $matches[1];
        }
        if (preg_match('/SEQ(?:UENCE)?\s*([1-6])/', $labelUpper, $matches)) {
            return "SEQUENCE " . $matches[1];
        }
        return $labelUpper;
    }

    // ── CONSTRUCTION DES DONNÉES DU BULLETIN ────────────────────────────────
    private function buildBulletinData(
        StudentEnrollment $enrollment,
        string $type,
        ?int $sequenceId,
        ?int $trimesterId,
        bool $forPdf = false
    ): array {
        $enrollment->load([
            'student',
            'classGroup.level.section',
            'classGroup.academicYear',
            'classGroup.titularStaff',
            'academicYear',
        ]);

        $classGroup = $enrollment->classGroup;
        $school     = \App\Models\SchoolSetting::instance();
        $phones     = \App\Models\SchoolPhone::orderByDesc('is_primary')->get();
        $agreements = \App\Models\SchoolAgreement::orderBy('cycle')->get();

        $principalName = \App\Models\Staff::whereHas('positions', function ($q) {
            $q->where('position', 'directeur');
        })->first()?->full_name;

        if (!$principalName) {
            $principalName = \App\Models\Staff::whereHas('positions', function ($q) {
                $q->where('position', 'prefet_des_etudes');
            })->first()?->full_name ?? '—';
        }

        if ($type === 'sequentiel') {
            $sequence  = Sequence::with('trimester')->find($sequenceId);
            $details   = $this->buildSequenceSubjectDetails($classGroup, $enrollment, $sequence);
            $average   = $this->calc->sequenceAverage($enrollment, $sequence);
            $rankInfo  = $this->calc->classRank($classGroup, $enrollment, $sequence);
            $absences  = $this->calc->absenceTotals($enrollment, $sequence);
            $periodLabel = $sequence->label;
            
            $seqOfficialTitle = $this->getSequenceOfficialTitle($sequence->label);
            if (str_starts_with($seqOfficialTitle, 'SEQUENCE')) {
                $documentTitle = 'BULLETIN DE NOTES DE LA ' . $seqOfficialTitle;
            } else {
                $documentTitle = 'BULLETIN DE NOTES DU ' . $seqOfficialTitle;
            }

            $seqOfficialTitleEn = $this->getSequenceOfficialTitleEn($sequence->label);
            $documentTitleEn = $seqOfficialTitleEn . ' REPORT CARD';

            $periodHeaders = [];
            $periodAverages = [$average];
            $previousAverages = [];
            $council     = null;
            $distinction = $this->getDistinction($average);

        } elseif ($type === 'trimestriel') {
            $trimester  = Trimester::with('sequences')->find($trimesterId);
            $sequences  = $trimester->sequences;

            $details = $this->buildTrimesterSubjectDetails(
                $classGroup, $enrollment, $sequences
            );
            $average  = $this->calc->trimesterAverage($enrollment, $trimester);
            $rankInfo = $this->trimesterRank($classGroup, $enrollment, $trimester);
            $absences = $this->absenceTotalsForPeriod($enrollment,
                $sequences->first()?->start_date, $sequences->last()?->end_date);
            $periodLabel = $trimester->label;
            $documentTitle = 'BULLETIN DE NOTES DU TRIMESTRE ' . $trimester->number;
            $documentTitleEn = 'REPORT CARD OF TRIMESTER ' . $trimester->number;
            $periodHeaders = $sequences->pluck('label')->toArray();

            $periodAverages = [];
            foreach ($sequences as $seq) {
                $periodAverages[] = $this->calc->sequenceAverage($enrollment, $seq);
            }

            $previousAverages = [];
            if ($trimester->number >= 2) {
                $tri1 = Trimester::where('academic_year_id', $classGroup->academic_year_id)->where('number', 1)->first();
                if ($tri1) {
                    $previousAverages['TRIM 1'] = $this->calc->trimesterAverage($enrollment, $tri1);
                }
            }
            if ($trimester->number >= 3) {
                $tri2 = Trimester::where('academic_year_id', $classGroup->academic_year_id)->where('number', 2)->first();
                if ($tri2) {
                    $previousAverages['TRIM 2'] = $this->calc->trimesterAverage($enrollment, $tri2);
                }
            }

            $council     = CouncilDecision::active()
                ->forLevel($classGroup->level)->first();
            $distinction = $this->getDistinction($average);

        } else { // annuel
            $trimesters = Trimester::where('academic_year_id', $classGroup->academic_year_id)
                ->orderBy('number')->get();

            $details = $this->buildYearSubjectDetails($classGroup, $enrollment, $trimesters);
            $average  = $this->calc->yearAverage($enrollment);
            $rankInfo = $this->yearRank($classGroup, $enrollment);
            $absences = $this->absenceTotalsForPeriod($enrollment, null, null);
            $periodLabel = 'Année ' . $classGroup->academicYear->label;
            $documentTitle = 'BULLETIN DE NOTES ANNUEL';
            $documentTitleEn = 'ANNUAL REPORT CARD';
            $periodHeaders = $trimesters->map(fn($t) => 'TRIM ' . $t->number)->toArray();

            $periodAverages = [];
            foreach ($trimesters as $tri) {
                $periodAverages[] = $this->calc->trimesterAverage($enrollment, $tri);
            }

            $previousAverages = [];
            foreach ($trimesters as $tri) {
                $previousAverages['TRIM ' . $tri->number] = $this->calc->trimesterAverage($enrollment, $tri);
            }

            $council     = CouncilDecision::active()
                ->forLevel($classGroup->level)->first();
            $distinction = $this->getDistinction($average);
        }

        $classSize = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->count();

        return [
            'enrollment'   => $enrollment,
            'classGroup'   => $classGroup,
            'school'       => $school,
            'phones'       => $phones,
            'agreements'   => $agreements,
            'principalName'=> $principalName,
            'type'         => $type,
            'periodLabel'  => $periodLabel,
            'periodHeaders'=> $periodHeaders,
            'details'      => $details,
            'average'      => $average,
            'rankInfo'     => $rankInfo,
            'absences'     => $absences,
            'council'      => $council,
            'distinction'  => $distinction,
            'appreciation' => $average !== null
                ? \App\Models\AppreciationScale::forGrade($average) : null,
            'studentPhoto'  => $this->studentPhotoPath($enrollment->student, $forPdf),
            'parentContacts'=> $this->buildParentContactLines($enrollment->student),
            'documentTitle' => $documentTitle,
            'documentTitleEn' => $documentTitleEn,
            'classSize'     => $classSize,
            'isRepeating'   => $enrollment->is_repeating ?? false,
            'academicYear'  => $classGroup->academicYear ?? $enrollment->academicYear,
            'previousAverages' => $previousAverages,
            'periodAverages' => $periodAverages,
        ];
    }

    private function buildSequenceSubjectDetails($classGroup, $enrollment, Sequence $sequence)
    {
        return $this->calc->buildSubjectDetails($classGroup, $enrollment, $sequence)
            ->map(fn ($detail) => array_merge($detail, [
                'total' => isset($detail['grade']) && $detail['grade'] !== null && !$detail['is_absent']
                    ? round($detail['grade'] * $detail['coefficient'], 2)
                    : null,
            ]));
    }

    private function buildTrimesterSubjectDetails($classGroup, $enrollment, $sequences)
    {
        $classSubjects = $classGroup->classSubjects()
            ->where('is_active', true)
            ->with(['subject', 'teacherAssignments.staff'])
            ->get();

        $allEnrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        $teacherMap = $classSubjects->mapWithKeys(fn($cs) => [
            $cs->id => $this->formatTeacherName($cs->teacherAssignments->first()?->staff),
        ])->toArray();

        return $classSubjects->map(function($cs) use ($enrollment, $sequences, $teacherMap, $allEnrollments) {
            $seqGrades = $sequences->map(fn($seq) => Grade::where([
                'student_enrollment_id' => $enrollment->id,
                'class_subject_id'      => $cs->id,
                'sequence_id'           => $seq->id,
            ])->first());

            $avg = $this->calc->calculateTrimesterSubjectGrade($enrollment->id, $cs->id, $sequences);

            // Calculer les statistiques de la classe pour ce trimestre
            $classGrades = $allEnrollments->map(function($enr) use ($cs, $sequences) {
                return $this->calc->calculateTrimesterSubjectGrade($enr->id, $cs->id, $sequences);
            })->filter(fn($g) => $g !== null)->values();

            $min = $classGrades->min();
            $max = $classGrades->max();
            $successCount = $classGrades->filter(fn($g) => $g >= 10)->count();
            $successRate = $classGrades->count() > 0 ? round(($successCount / $classGrades->count()) * 100, 2) : 0;

            $rank = null;
            if ($avg !== null) {
                $sorted = $classGrades->sortDesc()->values();
                $rankIdx = $sorted->search($avg);
                $rank = $rankIdx !== false ? $rankIdx + 1 : null;
            }

            return [
                'subject'      => $cs->subject,
                'coefficient'  => $cs->coefficient,
                'teacher'      => $teacherMap[$cs->id] ?? null,
                'seq_grades'   => $seqGrades->map(fn($g) => $g && !$g->is_absent ? $g->grade : ($g && $g->is_absent ? 'ABS' : null))->values()->toArray(),
                'grade'        => $avg,
                'total'        => $avg !== null ? round($avg * $cs->coefficient, 2) : null,
                'is_absent'    => false,
                'rank'         => $rank,
                'class_size'   => $classGrades->count(),
                'min'          => $min,
                'max'          => $max,
                'success_rate' => $successRate,
                'appreciation' => $avg !== null
                    ? \App\Models\AppreciationScale::forGrade($avg) : null,
            ];
        });
    }

    private function buildYearSubjectDetails($classGroup, $enrollment, $trimesters)
    {
        $classSubjects = $classGroup->classSubjects()
            ->where('is_active', true)
            ->with(['subject', 'teacherAssignments.staff'])
            ->get();

        $allEnrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        $teacherMap = $classSubjects->mapWithKeys(fn($cs) => [
            $cs->id => $this->formatTeacherName($cs->teacherAssignments->first()?->staff),
        ])->toArray();

        return $classSubjects->map(function($cs) use ($enrollment, $trimesters, $teacherMap, $allEnrollments) {
            $trimesterAverages = $trimesters->map(function($tri) use ($enrollment, $cs) {
                return $this->calc->calculateTrimesterSubjectGrade($enrollment->id, $cs->id, $tri->sequences);
            });

            $validTrimesterAverages = $trimesterAverages->filter(fn($v) => $v !== null);
            $avg = $validTrimesterAverages->count() > 0
                ? round($validTrimesterAverages->avg(), 2)
                : null;

            // Calculer les statistiques de la classe pour l'année
            $classGrades = $allEnrollments->map(function($enr) use ($cs, $trimesters) {
                $triAvgs = [];
                foreach ($trimesters as $tri) {
                    $triAvg = $this->calc->calculateTrimesterSubjectGrade($enr->id, $cs->id, $tri->sequences);
                    if ($triAvg !== null) {
                        $triAvgs[] = $triAvg;
                    }
                }
                return count($triAvgs) > 0 ? round(array_sum($triAvgs) / count($triAvgs), 2) : null;
            })->filter(fn($g) => $g !== null)->values();

            $min = $classGrades->min();
            $max = $classGrades->max();
            $successCount = $classGrades->filter(fn($g) => $g >= 10)->count();
            $successRate = $classGrades->count() > 0 ? round(($successCount / $classGrades->count()) * 100, 2) : 0;

            $rank = null;
            if ($avg !== null) {
                $sorted = $classGrades->sortDesc()->values();
                $rankIdx = $sorted->search($avg);
                $rank = $rankIdx !== false ? $rankIdx + 1 : null;
            }

            return [
                'subject'             => $cs->subject,
                'coefficient'         => $cs->coefficient,
                'teacher'             => $teacherMap[$cs->id] ?? null,
                'trimester_averages'  => $trimesterAverages->values()->toArray(),
                'grade'               => $avg,
                'total'               => $avg !== null ? round($avg * $cs->coefficient, 2) : null,
                'is_absent'           => false,
                'rank'                => $rank,
                'class_size'          => $classGrades->count(),
                'min'                 => $min,
                'max'                 => $max,
                'success_rate'        => $successRate,
                'appreciation'        => $avg !== null
                    ? \App\Models\AppreciationScale::forGrade($avg) : null,
            ];
        });
    }

    private function studentPhotoPath($student, bool $forPdf = false): ?string
    {
        if ($student->photo) {
            $storagePath = public_path('storage/' . ltrim($student->photo, '/'));
            if (! file_exists($storagePath)) {
                return null;
            }

            if ($forPdf) {
                return 'file://' . str_replace('\\', '/', $storagePath);
            }

            return asset('storage/' . ltrim($student->photo, '/'));
        }

        return null;
    }

    private function buildParentContactLines($student): string
    {
        $lines = [];

        if ($student->father_name || $student->father_phone) {
            $lines[] = 'Père : ' . trim($student->father_name . ' ' . ($student->father_phone ? '(' . $student->father_phone . ')' : ''));
        }

        if ($student->mother_name || $student->mother_phone) {
            $lines[] = 'Mère : ' . trim($student->mother_name . ' ' . ($student->mother_phone ? '(' . $student->mother_phone . ')' : ''));
        }

        if ($student->guardian_name || $student->guardian_phone) {
            $lines[] = 'Tuteur : ' . trim($student->guardian_name . ' ' . ($student->guardian_phone ? '(' . $student->guardian_phone . ')' : ''));
        }

        return count($lines) > 0 ? implode(' · ', $lines) : '—';
    }

    private function getDistinction(?float $average): ?Distinction
    {
        if ($average === null) return null;

        if ($average >= 16) return Distinction::positive()->first();
        return null;
    }

    private function trimesterRank($classGroup, $enrollment, $trimester): array
    {
        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        $averages = $enrollments->map(fn($e) => [
            'id'  => $e->id,
            'avg' => $this->calc->trimesterAverage($e, $trimester),
        ])->filter(fn($a) => $a['avg'] !== null)->sortByDesc('avg')->values();

        $rank = $averages->search(fn($a) => $a['id'] === $enrollment->id);

        $successCount = $averages->filter(fn($a) => $a['avg'] >= 10)->count();
        $successRate = $averages->count() > 0 ? round(($successCount / $averages->count()) * 100, 2) : 0;

        return [
            'rank'          => $rank !== false ? $rank + 1 : null,
            'class_size'    => $enrollments->count(),
            'class_average' => $averages->avg('avg') ? round($averages->avg('avg'), 2) : null,
            'highest'       => $averages->max('avg'),
            'lowest'        => $averages->min('avg'),
            'averages_count'=> $averages->count(),
            'success_rate'  => $successRate,
        ];
    }

    private function yearRank($classGroup, $enrollment): array
    {
        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        $averages = $enrollments->map(fn($e) => [
            'id'  => $e->id,
            'avg' => $this->calc->yearAverage($e),
        ])->filter(fn($a) => $a['avg'] !== null)->sortByDesc('avg')->values();

        $rank = $averages->search(fn($a) => $a['id'] === $enrollment->id);

        $successCount = $averages->filter(fn($a) => $a['avg'] >= 10)->count();
        $successRate = $averages->count() > 0 ? round(($successCount / $averages->count()) * 100, 2) : 0;

        return [
            'rank'          => $rank !== false ? $rank + 1 : null,
            'class_size'    => $enrollments->count(),
            'class_average' => $averages->avg('avg') ? round($averages->avg('avg'), 2) : null,
            'highest'       => $averages->max('avg'),
            'lowest'        => $averages->min('avg'),
            'averages_count'=> $averages->count(),
            'success_rate'  => $successRate,
        ];
    }

    private function getSequenceOfficialTitleEn(string $label): string
    {
        $labelUpper = strtoupper(trim($label));
        if (preg_match('/CC\s*([1-3])/', $labelUpper, $matches)) {
            return "CONTINUOUS ASSESSMENT " . $matches[1];
        }
        if (preg_match('/DS\s*([1-5])/', $labelUpper, $matches)) {
            return "SUPERVISED TEST " . $matches[1];
        }
        if (preg_match('/SEQ(?:UENCE)?\s*([1-6])/', $labelUpper, $matches)) {
            return "SEQUENCE " . $matches[1];
        }
        return $labelUpper;
    }

    private function formatTeacherName(?Staff $staff): ?string
    {
        if (!$staff) {
            return null;
        }

        $genderPrefix = '';
        if ($staff->gender) {
            $gender = strtoupper(trim($staff->gender));
            if ($gender === 'F' || $gender === 'FEMALE' || $gender === 'FEMME') {
                $genderPrefix = 'Mme ';
            } else {
                $genderPrefix = 'M. ';
            }
        }

        $fullName = trim($staff->last_name . ' ' . $staff->first_name);
        return strtoupper($genderPrefix . $fullName);
    }

    private function absenceTotalsForPeriod($enrollment, $start, $end): array
    {
        $query = \App\Models\Absence::where('student_enrollment_id', $enrollment->id);
        if ($start && $end) $query->whereBetween('absence_date', [$start, $end]);
        $absences = $query->get();

        return [
            'justified'   => (float)$absences->where('is_justified', true)->sum('hours'),
            'unjustified' => (float)$absences->where('is_justified', false)->sum('hours'),
            'total'       => (float)$absences->sum('hours'),
        ];
    }

    // ── API : Élèves d'une classe (utilisée par la page de génération) ───────
    public function apiStudents(Request $request)
    {
        $classId    = (int)$request->input('class_id');
        $activeYear = AcademicYear::active();

        if (!$classId || !$activeYear) {
            return response()->json(['students' => []]);
        }

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classId,
            'academic_year_id' => $activeYear->id,
            'status'           => 'active',
        ])->with('student')
        ->get()
        ->sortBy('student.last_name')
        ->map(fn($e) => [
            'id'        => $e->id,
            'full_name' => $e->student->full_name,
            'matricule' => $e->student->matricule,
            'gender'    => $e->student->gender,
        ])->values();

        return response()->json(['students' => $enrollments]);
    }
}
