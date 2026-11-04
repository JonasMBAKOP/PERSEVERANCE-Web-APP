<?php

use App\Models\ClassGroup;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Models\SubjectCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $category = SubjectCategory::query()->orderBy('id')->first();

        if (! $category) {
            $category = SubjectCategory::create([
                'name_fr' => 'Matières générales',
                'name_en' => 'General subjects',
                'order_index' => 1,
            ]);
        }

        $subjects = [
            ['MAT-DEMO', 'Mathématiques', 'Mathematics'],
            ['FRA-DEMO', 'Français', 'French'],
            ['ANG-DEMO', 'Anglais', 'English'],
            ['HGE-DEMO', 'Histoire-Géographie', 'History-Geography'],
            ['SCI-DEMO', 'Sciences', 'Sciences'],
            ['INF-DEMO', 'Informatique', 'Computer Science'],
            ['PHY-DEMO', 'Physique', 'Physics'],
            ['CHI-DEMO', 'Chimie', 'Chemistry'],
            ['BIO-DEMO', 'Biologie', 'Biology'],
            ['ECV-DEMO', 'Éducation civique', 'Civic Education'],
            ['ECO-DEMO', 'Économie', 'Economics'],
            ['PHI-DEMO', 'Philosophie', 'Philosophy'],
            ['EPS-DEMO', 'Éducation physique et sportive', 'Physical Education'],
            ['ART-DEMO', 'Arts', 'Arts'],
            ['TEC-DEMO', 'Technologie', 'Technology'],
        ];

        foreach ($subjects as [$code, $nameFr, $nameEn]) {
            Subject::firstOrCreate(
                ['code' => $code],
                [
                    'subject_category_id' => $category->id,
                    'name_fr' => $nameFr,
                    'name_en' => $nameEn,
                    'type' => 'general',
                ]
            );
        }

        $subjectIds = Subject::query()
            ->whereIn('code', array_column($subjects, 0))
            ->orderBy('id')
            ->pluck('id');

        ClassGroup::query()->select('id')->chunkById(100, function ($classes) use ($subjectIds) {
            foreach ($classes as $class) {
                $assigned = ClassSubject::query()
                    ->where('class_group_id', $class->id)
                    ->pluck('subject_id');

                foreach ($subjectIds as $subjectId) {
                    if ($assigned->contains($subjectId)) {
                        continue;
                    }

                    if ($assigned->count() >= 15) {
                        break;
                    }

                    ClassSubject::firstOrCreate(
                        ['class_group_id' => $class->id, 'subject_id' => $subjectId],
                        [
                            'grading_scale' => 20,
                            'coefficient' => 1,
                            'hours_per_week' => null,
                            'is_active' => true,
                        ]
                    );
                    $assigned->push($subjectId);
                }
            }
        });
    }

    public function down(): void
    {
        $codes = [
            'MAT-DEMO', 'FRA-DEMO', 'ANG-DEMO', 'HGE-DEMO', 'SCI-DEMO',
            'INF-DEMO', 'PHY-DEMO', 'CHI-DEMO', 'BIO-DEMO', 'ECV-DEMO',
            'ECO-DEMO', 'PHI-DEMO', 'EPS-DEMO', 'ART-DEMO', 'TEC-DEMO',
        ];

        DB::transaction(function () use ($codes) {
            $subjectIds = Subject::whereIn('code', $codes)->pluck('id');
            ClassSubject::whereIn('subject_id', $subjectIds)->delete();
            Subject::whereIn('id', $subjectIds)->delete();
        });
    }
};
