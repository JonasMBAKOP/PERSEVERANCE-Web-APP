<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('infirmary_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('visit_date')->index(); $table->time('visit_time');
            $table->string('student_name', 210); $table->string('class_name', 150)->nullable();
            $table->unsignedTinyInteger('student_age')->nullable(); $table->string('parent_phone', 30)->nullable();
            $table->decimal('temperature', 4, 1)->nullable(); $table->text('visit_reason'); $table->text('treatment')->nullable();
            $table->timestamps(); $table->index(['academic_year_id', 'visit_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('infirmary_visits'); }
};
