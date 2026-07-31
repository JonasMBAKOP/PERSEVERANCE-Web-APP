<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sourceCategories = DB::table('subject_categories')
            ->orderBy('order_index')
            ->orderBy('id')
            ->get();

        if ($sourceCategories->isEmpty()) {
            return;
        }

        DB::table('sections')->orderBy('id')->each(function (object $section) use ($sourceCategories): void {
            if (DB::table('subject_categories')->where('section_id', $section->id)->exists()) {
                return;
            }

            foreach ($sourceCategories as $index => $source) {
                $name = trim((string) $source->name) . ' - ' . $section->name;

                DB::table('subject_categories')->insert([
                    'section_id' => $section->id,
                    'code' => 'CAT-S' . $section->id . '-' . $source->id,
                    'name' => $name,
                    'name_fr' => $name,
                    'name_en' => null,
                    'order_index' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('subject_categories')
            ->where('code', 'like', 'CAT-S%-')
            ->delete();
    }
};
