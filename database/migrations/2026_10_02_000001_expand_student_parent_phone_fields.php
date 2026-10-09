<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('father_phone', 255)->nullable()->change();
            $table->string('mother_phone', 255)->nullable()->change();
            $table->string('guardian_phone', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('father_phone', 20)->nullable()->change();
            $table->string('mother_phone', 20)->nullable()->change();
            $table->string('guardian_phone', 20)->nullable()->change();
        });
    }
};
