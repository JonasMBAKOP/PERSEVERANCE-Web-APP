<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionsAndLevelsSeeder extends Seeder
{
    /** @return array<int, array{name: string, code: string, language: string, levels: array<int, string>}> */
    public static function primaryStructure(): array
    {
        return [
            [
                'name' => 'Francophone',
                'code' => 'FR',
                'language' => 'fr',
                'levels' => ['Petite Section', 'Moyenne Section', 'Grande Section', 'SIL', 'CP', 'CE1', 'CE2', 'CM1', 'CM2'],
            ],
            [
                'name' => 'Anglophone',
                'code' => 'EN',
                'language' => 'en',
                'levels' => ['Pre-Nursery', 'Nursery 1', 'Nursery 2', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6'],
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::primaryStructure() as $sectionData) {
            $section = DB::table('sections')
                ->where('code', $sectionData['code'])
                ->where('language', $sectionData['language'])
                ->first();

            if (! $section) {
                $sectionId = DB::table('sections')->insertGetId([
                    'name' => $sectionData['name'],
                    'code' => $sectionData['code'],
                    'language' => $sectionData['language'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $sectionId = $section->id;
                DB::table('sections')->where('id', $sectionId)->update([
                    'name' => $sectionData['name'],
                    'updated_at' => now(),
                ]);
            }

            foreach ($sectionData['levels'] as $index => $name) {
                DB::table('levels')->updateOrInsert(
                    ['section_id' => $sectionId, 'name' => $name],
                    [
                        'order_index' => $index + 1,
                        'is_exam_class' => false,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Primary sections and levels created.');
    }
}