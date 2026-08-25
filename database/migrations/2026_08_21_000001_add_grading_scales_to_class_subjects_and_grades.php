<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_subjects', function (Blueprint $table) {
            $table->decimal('grading_scale', 6, 2)
                ->unsigned()
                ->default(20)
                ->after('subject_id')
                ->comment('Barème de notation de la matière dans cette classe');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('raw_grade', 6, 2)
                ->unsigned()
                ->nullable()
                ->after('grade')
                ->comment('Note saisie sur le barème de la matière');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('raw_grade');
        });

        Schema::table('class_subjects', function (Blueprint $table) {
            $table->dropColumn('grading_scale');
        });
    }
};
