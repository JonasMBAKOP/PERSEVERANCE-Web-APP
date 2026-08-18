<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('infirmary_visits', fn (Blueprint $table) => $table->string('recorded_by_name', 210)->nullable()->after('recorded_by_staff_id')); } public function down(): void { Schema::table('infirmary_visits', fn (Blueprint $table) => $table->dropColumn('recorded_by_name')); } };
