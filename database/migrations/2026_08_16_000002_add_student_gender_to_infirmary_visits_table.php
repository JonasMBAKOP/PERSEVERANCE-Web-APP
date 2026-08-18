<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('infirmary_visits', fn (Blueprint $table) => $table->char('student_gender', 1)->nullable()->after('student_name')); }
    public function down(): void { Schema::table('infirmary_visits', fn (Blueprint $table) => $table->dropColumn('student_gender')); }
};
