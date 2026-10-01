<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('staff_pay_slips', 'hours_worked')) {
            Schema::table('staff_pay_slips', function (Blueprint $table): void {
                $table->decimal('hours_worked', 10, 2)->nullable()->after('amount_received');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff_pay_slips', 'hours_worked')) {
            Schema::table('staff_pay_slips', function (Blueprint $table): void {
                $table->dropColumn('hours_worked');
            });
        }
    }
};
