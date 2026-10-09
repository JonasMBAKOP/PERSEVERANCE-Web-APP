<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infirmary_visits', function (Blueprint $table): void {
            $table->string('parent_phone', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('infirmary_visits', function (Blueprint $table): void {
            $table->string('parent_phone', 30)->nullable()->change();
        });
    }
};
