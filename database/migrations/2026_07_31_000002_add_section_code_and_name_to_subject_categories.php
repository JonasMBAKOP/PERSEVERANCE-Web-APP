<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_categories', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('id')->constrained('sections')->nullOnDelete();
            $table->string('code', 30)->nullable()->after('section_id');
            $table->string('name', 100)->nullable()->after('code');
        });

        $defaultSectionId = DB::table('sections')->orderBy('id')->value('id');
        $usedNames = [];

        DB::table('subject_categories')->orderBy('id')->each(function (object $category) use ($defaultSectionId, &$usedNames): void {
            $baseName = trim((string) ($category->name_fr ?: 'Categorie ' . $category->id));
            $name = $baseName;
            while (isset($usedNames[mb_strtolower($name)])) {
                $name = $baseName . ' ' . $category->id;
            }
            $usedNames[mb_strtolower($name)] = true;

            DB::table('subject_categories')->where('id', $category->id)->update([
                'section_id' => $defaultSectionId,
                'code' => 'CAT-' . $category->id,
                'name' => $name,
            ]);
        });

        Schema::table('subject_categories', function (Blueprint $table) {
            $table->unique('code', 'subject_categories_code_unique');
            $table->unique('name', 'subject_categories_name_unique');
            $table->index(['section_id', 'order_index'], 'subject_categories_section_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('subject_categories', function (Blueprint $table) {
            $table->dropIndex('subject_categories_section_order_index');
            $table->dropUnique('subject_categories_code_unique');
            $table->dropUnique('subject_categories_name_unique');
            $table->dropConstrainedForeignId('section_id');
            $table->dropColumn(['code', 'name']);
        });
    }
};
