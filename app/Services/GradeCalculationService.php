<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\AppreciationScale;
use App\Models\ClassGroup;
use App\Models\Grade;
use App\Models\Sequence;
use App\Models\StudentEnrollment;
use App\Models\Trimester;
use Illuminate\Support\Collection;

class GradeCalculationService
{
    /**
     * Calcule les moyennes par matière pour un élève sur une séquence donnée.
     * Retourne une collection [class_subject_id => ['grade'=>, 'coef'=>, 'subject'=>]]
     */
    public function sequenceGrades(StudentEnrollment $enrollment, Sequence $sequence): Collection
    {
        return Grade::where('student_enrollment_id', $enrollment->id)
            ->where('sequence_id', $sequence->id)
            ->with('classSubject.subject')
            ->get()
            ->keyBy('class_subject_id');
    }

    /**
     * Moyenne générale séquentielle d'un élève = somme(note×coef)/somme(coef)
     */
    public function sequenceAverage(StudentEnrollment $enrollment, Sequence $sequence): ?float
    {
        $grades = $this->sequenceGrades($enrollment, $sequence);
        $totalPoints = 0;
        $totalCoef   = 0;

        foreach ($grades as $g) {
            if ($g->is_absent) {
                $totalCoef += $g->classSubject->coefficient;
                continue;
            }
            if ($g->grade === null) continue;

            $totalPoints += (float)$g->grade * $g->classSubject->coefficient;
            $totalCoef   += $g->classSubject->coefficient;
        }

        return $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : null;
    }

    /**
     * Calcule la note d'une matière pour un trimestre donné (CC = 20%, DS = 40%)
     */
    public function calculateTrimesterSubjectGrade(int $enrollmentId, int $classSubjectId, Collection $sequences): ?float
    {
        $grades = $sequences->map(function($seq) use ($enrollmentId, $classSubjectId) {
            return Grade::where([
                'student_enrollment_id' => $enrollmentId,
                'class_subject_id'      => $classSubjectId,
                'sequence_id'           => $seq->id,
            ])->first();
        });

        $validGrades = $grades->filter(
            fn($g) => $g && $g->grade !== null && !$g->is_absent
        )->values();

        if ($validGrades->isEmpty()) {
            return null;
        }

        // Every trimester contains exactly two sequences. Their available grades have equal weight.
        $vals = $validGrades->pluck('grade')->map(fn($g) => (float)$g);
        return round($vals->avg(), 2);
    }

    /**
     * Moyenne trimestrielle générale d'un élève = sum(Moyenne_Matiere × Coef) / sum(Coef)
     */
    public function trimesterAverage(StudentEnrollment $enrollment, Trimester $trimester): ?float
    {
        $classGroup = $enrollment->classGroup;
        $sequences = $trimester->sequences;
        $classSubjects = $classGroup->classSubjects()->where('is_active', true)->get();

        $totalPoints = 0;
        $totalCoef   = 0;

        foreach ($classSubjects as $cs) {
            $avg = $this->calculateTrimesterSubjectGrade($enrollment->id, $cs->id, $sequences);
            if ($avg !== null) {
                $totalPoints += $avg * $cs->coefficient;
                $totalCoef   += $cs->coefficient;
            }
        }

        return $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : null;
    }

    /**
     * Moyenne annuelle = sum(Moyenne_Matiere_Annuelle × Coef) / sum(Coef)
     */
    public function yearAverage(StudentEnrollment $enrollment): ?float
    {
        $trimesters = Trimester::where('academic_year_id', $enrollment->academic_year_id)
            ->orderBy('number')->get();

        $classGroup = $enrollment->classGroup;
        $classSubjects = $classGroup->classSubjects()->where('is_active', true)->get();

        $totalPoints = 0;
        $totalCoef   = 0;

        foreach ($classSubjects as $cs) {
            $trimesterAverages = [];
            foreach ($trimesters as $tri) {
                $triAvg = $this->calculateTrimesterSubjectGrade($enrollment->id, $cs->id, $tri->sequences);
                if ($triAvg !== null) {
                    $trimesterAverages[] = $triAvg;
                }
            }
            if (count($trimesterAverages) > 0) {
                $subjectYearAvg = round(array_sum($trimesterAverages) / count($trimesterAverages), 2);
                $totalPoints += $subjectYearAvg * $cs->coefficient;
                $totalCoef   += $cs->coefficient;
            }
        }

        return $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : null;
    }

    /**
     * Calcule le détail complet par matière pour le bulletin
     * (note séquence, moyenne, coef, rang dans la matière, appréciation)
     */
    public function buildSubjectDetails(
        ClassGroup $classGroup,
        StudentEnrollment $enrollment,
        Sequence $sequence
    ): Collection {
        $classSubjects = $classGroup->classSubjects()
            ->where('is_active', true)
            ->with(['subject', 'teacherAssignments.staff'])
            ->orderBy('subject_id')
            ->get();

        $allEnrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        $details = collect();

        foreach ($classSubjects as $cs) {
            $grade = Grade::where([
                'student_enrollment_id' => $enrollment->id,
                'class_subject_id'      => $cs->id,
                'sequence_id'           => $sequence->id,
            ])->first();

            // Rang dans la matière (parmi toute la classe)
            $classGrades = Grade::where('class_subject_id', $cs->id)
                ->where('sequence_id', $sequence->id)
                ->whereIn('student_enrollment_id', $allEnrollments->pluck('id'))
                ->whereNotNull('grade')
                ->orderByDesc('grade')
                ->get();

            $rank = null;
            if ($grade && $grade->grade !== null) {
                $rank = $classGrades->search(
                    fn($g) => $g->student_enrollment_id === $enrollment->id
                );
                $rank = $rank !== false ? $rank + 1 : null;
            }

            $gradesValues = $classGrades->pluck('grade')->map(fn($g) => (float)$g);
            $max = $gradesValues->max();
            $min = $gradesValues->min();
            $successCount = $gradesValues->filter(fn($g) => $g >= 10)->count();
            $successRate = $gradesValues->count() > 0 ? round(($successCount / $gradesValues->count()) * 100, 2) : 0;

            $teacherModel = $cs->teacherAssignments->first()?->staff;
            $teacherFormatted = null;
            if ($teacherModel) {
                $genderPrefix = '';
                if ($teacherModel->gender) {
                    $gender = strtoupper(trim($teacherModel->gender));
                    if ($gender === 'F' || $gender === 'FEMALE' || $gender === 'FEMME') {
                        $genderPrefix = 'Mme ';
                    } else {
                        $genderPrefix = 'M. ';
                    }
                }
                $teacherFormatted = strtoupper($genderPrefix . trim($teacherModel->last_name . ' ' . $teacherModel->first_name));
            }

            $appreciation = $grade && $grade->grade !== null
                ? AppreciationScale::forGrade((float)$grade->grade)
                : null;

            $details->push([
                'subject'      => $cs->subject,
                'coefficient'  => $cs->coefficient,
                'teacher'      => $teacherFormatted,
                'grade'        => $grade?->grade,
                'raw_grade'    => $grade?->raw_grade ?? (($cs->grading_scale ?: 20) == 20 ? $grade?->grade : null),
                'grading_scale'=> (float) ($cs->grading_scale ?: 20),
                'is_absent'    => $grade?->is_absent ?? false,
                'rank'         => $rank,
                'class_size'   => $classGrades->count(),
                'min'          => $min,
                'max'          => $max,
                'success_rate' => $successRate,
                'appreciation' => $appreciation,
            ]);
        }

        return $details;
    }

    /**
     * Rang général de l'élève dans la classe pour une séquence
     */
    public function classRank(ClassGroup $classGroup, StudentEnrollment $enrollment, Sequence $sequence): array
    {
        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        $averages = $enrollments->map(fn($e) => [
            'enrollment_id' => $e->id,
            'average'       => $this->sequenceAverage($e, $sequence),
        ])->filter(fn($a) => $a['average'] !== null)
          ->sortByDesc('average')
          ->values();

        $rank = $averages->search(fn($a) => $a['enrollment_id'] === $enrollment->id);
        $rank = $rank !== false ? $rank + 1 : null;

        $successCount = $averages->filter(fn($a) => $a['average'] >= 10)->count();
        $successRate = $averages->count() > 0 ? round(($successCount / $averages->count()) * 100, 2) : 0;

        return [
            'rank'           => $rank,
            'class_size'     => $enrollments->count(),
            'class_average'  => $averages->avg('average') !== null
                ? round($averages->avg('average'), 2) : null,
            'highest'        => $averages->max('average'),
            'lowest'         => $averages->min('average'),
            'averages_count' => $averages->count(),
            'success_rate'   => $successRate,
        ];
    }

    public function trimesterRank(ClassGroup $classGroup, StudentEnrollment $enrollment, Trimester $trimester): array
    {
        $enrollments = StudentEnrollment::where([
            'class_group_id'   => $classGroup->id,
            'academic_year_id' => $classGroup->academic_year_id,
            'status'           => 'active',
        ])->get();

        $averages = $enrollments->map(fn($e) => [
            'enrollment_id' => $e->id,
            'average'       => $this->trimesterAverage($e, $trimester),
        ])->filter(fn($a) => $a['average'] !== null)
          ->sortByDesc('average')
          ->values();

        $rank = $averages->search(fn($a) => $a['enrollment_id'] === $enrollment->id);
        $rank = $rank !== false ? $rank + 1 : null;

        $successCount = $averages->filter(fn($a) => $a['average'] >= 10)->count();
        $successRate = $averages->count() > 0 ? round(($successCount / $averages->count()) * 100, 2) : 0;

        return [
            'rank'           => $rank,
            'class_size'     => $enrollments->count(),
            'class_average'  => $averages->avg('average') !== null
                ? round($averages->avg('average'), 2) : null,
            'highest'        => $averages->max('average'),
            'lowest'         => $averages->min('average'),
            'averages_count' => $averages->count(),
            'success_rate'   => $successRate,
            'average'        => $averages->avg('average') !== null ? round($averages->avg('average'), 2) : null,
        ];
    }

    /**
     * Total des heures d'absence (justifiées/injustifiées) sur une période
     */
    public function absenceTotals(StudentEnrollment $enrollment, ?Sequence $sequence = null): array
    {
        $query = Absence::where('student_enrollment_id', $enrollment->id);

        if ($sequence) {
            $query->whereBetween('absence_date', [
                $sequence->start_date, $sequence->end_date,
            ]);
        }

        $absences = $query->get();

        return [
            'justified'   => (float)$absences->where('is_justified', true)->sum('hours'),
            'unjustified' => (float)$absences->where('is_justified', false)->sum('hours'),
            'total'       => (float)$absences->sum('hours'),
        ];
    }
}
