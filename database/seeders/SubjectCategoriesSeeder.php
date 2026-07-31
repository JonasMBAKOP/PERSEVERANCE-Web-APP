<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $sections = DB::table('sections')->orderBy('id')->get();

        foreach ($sections as $section) {
            $categories = [
                ['code' => 'LIT-' . $section->code, 'name' => 'Matieres litteraires - ' . $section->name],
                ['code' => 'SCI-' . $section->code, 'name' => 'Matieres scientifiques - ' . $section->name],
                ['code' => 'AUT-' . $section->code, 'name' => 'Autres matieres - ' . $section->name],
            ];

            foreach ($categories as $index => $category) {
                DB::table('subject_categories')->updateOrInsert(
                    ['code' => $category['code']],
                    [
                        'section_id' => $section->id,
                        'name' => $category['name'],
                        'name_fr' => $category['name'],
                        'name_en' => null,
                        'order_index' => $index + 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Subject categories created by section.');
    }
}
