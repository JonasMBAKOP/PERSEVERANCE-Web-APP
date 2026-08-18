<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_presences', function (Blueprint $table) {
            $table->time('arrival_time')->nullable()->after('status');
            $table->time('departure_time')->nullable()->after('arrival_time');
        });
    }

    public function down(): void
    {
        Schema::table('staff_presences', function (Blueprint $table) {
            $table->dropColumn(['arrival_time', 'departure_time']);
        });
    }
};
