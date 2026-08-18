<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AppreciationScale;
use App\Models\ClassGroup;
use App\Models\Grade;
use App\Models\Sequence;
use App\Models\Staff;
use App\Models\StudentEnrollment;
use App\Models\Trimester;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LivretController extends Controller
{
    public function __construct(
        private GradeCalculationService $calc
    ) {}

    // ── APERÇU (HTML) ─────────────────────────────────────────────────────
    public function show(Request $request, StudentEnrollment $enrollment)
    {
        $data = $this->buildLivretData($enrollment);
        return view('livrets.show', $data);
    }

    // ── GÉNÉRATION EN MASSE ────────────────────────────────────────────────
    public function bulk(Request $request)
    {
        $classGroupId = $request->integer('class_group_id');
        abort_if(! $classGroupId, 422, 'Aucune classe sélectionnée.');

        $classGroup = ClassGroup::with(['level.section', 'academicYear'])
            ->findOrFail($classGroupId);

        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->with('student')
          ->get()
          ->sortBy('student.last_name');

        if ($request->filled('student_ids')) {
            $enrollments = $enrollments->whereIn('id', $request->student_ids);
        }

        abort_if($enrollments->isEmpty(), 404, 'Aucun élève trouvé.');

        $allLivrets = $enrollments->map(fn($enr) => $this->buildLivretData($enr))->values();

        return view('livrets.bulk', [
            'livrets'    => $allLivrets,
            'classGroup' => $classGroup,
        ]);
    }

    // ── CONSTRUCTION DES DONNÉES ──────────────────────────────────────────
    public function buildLivretData(StudentEnrollment $enrollment): array
    {
        $enrollment->load([
            'student',
            'classGroup.level.section',
            'classGroup.academicYear',
            'classGroup.titularStaff',
            'academicYear',
        ]);

        $classGroup   = $enrollment->classGroup;
        $academicYear = $classGroup->academicYear ?? $enrollment->academicYear;
        $school       = \App\Models\SchoolSetting::instance();
        $phones       = \App\Models\SchoolPhone::orderByDesc('is_primary')->get();
        $agreements   = \App\Models\SchoolAgreement::orderBy('cycle')->get();

        $principalName = \App\Models\Staff::whereHas('positions', function ($q) {
            $q->where('position', 'directeur');
        })->first()?->full_name;
        if (!$principalName) {
            $principalName = \App\Models\Staff::whereHas('positions', function ($q) {
                $q->where('position', 'prefet_des_etudes');
            })->first()?->full_name ?? '—';
        }

        // Trimestres + séquences pour l'année
        $trimesters = Trimester::where('academic_year_id', $academicYear->id)
            ->with(['sequences' => fn($q) => $q->orderBy('number')])
            ->orderBy('number')
            ->get();

        // Colonnes de l'en-tête du tableau
        // Structure: [Seq1, Seq2, ..., TRIM1, Seq3, ..., TRIM2, ..., ANNUELLE]
        $headerCols = []; // Chaque colonne: ['label' => ..., 'type' => 'seq'|'trim'|'annual', 'seq' => ..., 'trim' => ...]
        foreach ($trimesters as $trimester) {
            foreach ($trimester->sequences as $seq) {
                $headerCols[] = [
                    'label'   => strtoupper($seq->label),
                    'type'    => 'seq',
                    'seq_id'  => $seq->id,
                    'trim_id' => $trimester->id,
                ];
            }
            $headerCols[] = [
                'label'   => 'TRIM ' . $trimester->number,
                'type'    => 'trim',
                'seq_id'  => null,
                'trim_id' => $trimester->id,
            ];
        }
        $headerCols[] = ['label' => 'ANNUELLE', 'type' => 'annual', 'seq_id' => null, 'trim_id' => null];

        // Matières de la classe
        $classSubjects = $classGroup->classSubjects()
            ->where('is_active', true)
            ->with(['subject.category'])
            ->get();

        // Toutes les séquences de l'année (flat)
        $allSequences = $trimesters->flatMap->sequences;

        // Tous les élèves de la classe (pour rang)
        $allEnrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        // Construction des lignes par matière
        $groupedDetails = $classSubjects->groupBy(fn($cs) => $cs->subject?->category?->name_fr ?? 'AUTRES MATIÈRES');

        $subjectRows = $groupedDetails->map(function ($catSubjects, $catName) use (
            $enrollment, $trimesters, $allEnrollments, $headerCols
        ) {
            return $catSubjects->map(function ($cs) use (
                $enrollment, $trimesters, $allEnrollments, $headerCols, $catName
            ) {
                // Calculer la valeur pour chaque colonne
                $colValues = [];
                $trimAvgs  = [];

                foreach ($trimesters as $trimester) {
                    $sequences = $trimester->sequences;

                    // Notes séquentielles
                    foreach ($sequences as $seq) {
                        $grade = Grade::where([
                            'student_enrollment_id' => $enrollment->id,
                            'class_subject_id'      => $cs->id,
                            'sequence_id'           => $seq->id,
                        ])->first();

                        if ($grade && $grade->is_absent) {
                            $colValues[$seq->id] = 'ABS';
                        } elseif ($grade && $grade->grade !== null) {
                            $colValues[$seq->id] = round((float)$grade->grade, 2);
                        } else {
                            $colValues[$seq->id] = null;
                        }
                    }

                    // Moyenne trimestrielle de la matière
                    $trimAvg = $this->calc->calculateTrimesterSubjectGrade(
                        $enrollment->id, $cs->id, $sequences
                    );
                    $colValues['trim_' . $trimester->id] = $trimAvg;
                    $trimAvgs[]                          = $trimAvg;
                }

                // Moyenne annuelle de la matière
                $validTrimAvgs = array_filter($trimAvgs, fn($v) => $v !== null);
                $annualAvg = count($validTrimAvgs) > 0
                    ? round(array_sum($validTrimAvgs) / count($validTrimAvgs), 2)
                    : null;
                $colValues['annual'] = $annualAvg;

                // Statistiques classe pour la moyenne annuelle
                $classAnnualGrades = $allEnrollments->map(function ($enr) use ($cs, $trimesters) {
                    $triAvgs = [];
                    foreach ($trimesters as $tri) {
                        $avg = $this->calc->calculateTrimesterSubjectGrade($enr->id, $cs->id, $tri->sequences);
                        if ($avg !== null) $triAvgs[] = $avg;
                    }
                    return count($triAvgs) > 0 ? round(array_sum($triAvgs) / count($triAvgs), 2) : null;
                })->filter(fn($g) => $g !== null)->values();

                return [
                    'subject'      => $cs->subject,
                    'coefficient'  => $cs->coefficient,
                    'col_values'   => $colValues,
                    'annual_avg'   => $annualAvg,
                    'min'          => $classAnnualGrades->min(),
                    'max'          => $classAnnualGrades->max(),
                    'appreciation' => $annualAvg !== null
                        ? \App\Models\AppreciationScale::forGrade($annualAvg)
                        : null,
                ];
            })->values();
        });

        // Totaux par trimestre et annuel (pour la ligne TOTAL A+B+C)
        $footerValues = [];
        $footerRanks = [];
        $footerMentions = [];

        foreach ($trimesters as $trimester) {
            foreach ($trimester->sequences as $seq) {
                $seqAvg = $this->calc->sequenceAverage($enrollment, $seq);
                $footerValues[$seq->id] = $seqAvg;
                $footerRanks[$seq->id] = $this->calc->classRank($classGroup, $enrollment, $seq)['rank'];
                $footerMentions[$seq->id] = $seqAvg !== null ? AppreciationScale::forGrade($seqAvg) : null;
            }
            $trimAvg = $this->calc->trimesterAverage($enrollment, $trimester);
            $footerValues['trim_' . $trimester->id] = $trimAvg;
            $footerRanks['trim_' . $trimester->id] = $this->calc->trimesterRank($classGroup, $enrollment, $trimester)['rank'];
            $footerMentions['trim_' . $trimester->id] = $trimAvg !== null ? AppreciationScale::forGrade($trimAvg) : null;
        }

        $footerValues['annual'] = $this->calc->yearAverage($enrollment);
        $footerMentions['annual'] = $footerValues['annual'] !== null ? AppreciationScale::forGrade($footerValues['annual']) : null;

        // Rang annuel
        $yearAvgMap = $allEnrollments->map(fn($e) => [
            'id'  => $e->id,
            'avg' => $this->calc->yearAverage($e),
        ])->filter(fn($a) => $a['avg'] !== null)->sortByDesc('avg')->values();
        $yearRankIdx = $yearAvgMap->search(fn($a) => $a['id'] === $enrollment->id);
        $yearRank    = $yearRankIdx !== false ? $yearRankIdx + 1 : null;

        $classSize = $allEnrollments->count();

        $classYearAvg = $yearAvgMap->avg('avg');

        // Absences sur toute l'année
        $absences = $this->absenceTotalsForYear($enrollment);

        // Photo élève
        $studentPhoto = $this->studentPhotoPath($enrollment->student, false);

        // Contact parent
        $parentContacts = $this->buildParentContactLines($enrollment->student);

        return [
            'enrollment'     => $enrollment,
            'classGroup'     => $classGroup,
            'school'         => $school,
            'phones'         => $phones,
            'agreements'     => $agreements,
            'principalName'  => $principalName,
            'academicYear'   => $academicYear,
            'trimesters'     => $trimesters,
            'headerCols'     => $headerCols,
            'groupedDetails' => $subjectRows,
            'footerValues'   => $footerValues,
            'footerRanks'    => $footerRanks,
            'footerMentions' => $footerMentions,
            'yearAverage'    => $footerValues['annual'],
            'yearRank'       => $yearRank,
            'yearMention'    => $footerMentions['annual'],
            'classSize'      => $classSize,
            'classYearAvg'   => $classYearAvg ? round($classYearAvg, 2) : null,
            'isRepeating'    => $enrollment->is_repeating ?? false,
            'absences'       => $absences,
            'studentPhoto'   => $studentPhoto,
            'parentContacts' => $parentContacts,
        ];
    }

    private function absenceTotalsForYear(StudentEnrollment $enrollment): array
    {
        $absences = \App\Models\Absence::where('student_enrollment_id', $enrollment->id)->get();
        return [
            'justified'   => (float)$absences->where('is_justified', true)->sum('hours'),
            'unjustified' => (float)$absences->where('is_justified', false)->sum('hours'),
            'total'       => (float)$absences->sum('hours'),
        ];
    }

    private function studentPhotoPath($student, bool $forPdf = false): ?string
    {
        if ($student->photo) {
            if ($forPdf) {
                $storagePath = public_path('storage/' . ltrim($student->photo, '/'));
                if (file_exists($storagePath)) {
                    return 'file://' . str_replace('\\', '/', $storagePath);
                }
            }

            return asset('storage/' . ltrim($student->photo, '/'));
        }

        if ($forPdf) {
            $default = public_path('images/default-avatar.png');
            if (file_exists($default)) {
                return 'file://' . str_replace('\\', '/', $default);
            }
        }

        return asset('images/default-avatar.png');
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
}
