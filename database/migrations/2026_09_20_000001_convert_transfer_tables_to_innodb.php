<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These tables participate in student transfers and must support real
     * transactions. MyISAM silently commits deletes, preventing rollback.
     */
    private array $tables = [
        'students',
        'student_enrollments',
        'student_payments',
        'student_subjects',
        'grades',
        'absences',
        'bulletin_reports',
        'manual_insolvables',
        'discipline_incidents',
        'discipline_records',
        'infirmary_visits',
    ];

    public function up(): void
    {
        $grammar = DB::connection()->getQueryGrammar();

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement('ALTER TABLE ' . $grammar->wrapTable($table) . ' ENGINE=InnoDB');
            }
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: converting these tables back to MyISAM
        // would make student transfers unsafe again.
    }
};
