<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('absences')
            ->where('status', 'absent')
            ->whereNull('timetable_slot_id')
            ->whereNull('class_subject_id')
            ->where(function ($query) {
                $query->whereNull('hours')->orWhere('hours', '<=', 0);
            })
            ->update(['hours' => 7.0]);
    }

    public function down(): void
    {
        // The previous value was not reliable; do not erase valid attendance data.
    }
};
