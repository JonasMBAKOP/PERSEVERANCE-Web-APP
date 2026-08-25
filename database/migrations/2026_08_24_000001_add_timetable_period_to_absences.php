<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absences', function (Blueprint $table) {
            $table->foreignId('timetable_slot_id')
                ->nullable()
                ->after('period')
                ->constrained('timetable_slots')
                ->nullOnDelete();
            $table->unsignedTinyInteger('timetable_period_index')
                ->nullable()
                ->after('timetable_slot_id');

            $table->index(
                ['student_enrollment_id', 'absence_date', 'timetable_slot_id', 'timetable_period_index'],
                'absences_roll_call_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('absences', function (Blueprint $table) {
            $table->dropIndex('absences_roll_call_lookup_index');
            $table->dropForeign(['timetable_slot_id']);
            $table->dropColumn(['timetable_slot_id', 'timetable_period_index']);
        });
    }
};
