<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('manual_insolvables', function (Blueprint $table) {
            $table->bigInteger('total_due')->nullable()->after('installment_label');
            $table->bigInteger('total_paid')->default(0)->after('total_due');
            $table->bigInteger('remaining')->default(0)->after('total_paid');
            $table->json('selected_installments')->nullable()->after('remaining');
        });
    }

    public function down(): void
    {
        Schema::table('manual_insolvables', function (Blueprint $table) {
            $table->dropColumn(['total_due', 'total_paid', 'remaining', 'selected_installments']);
        });
    }
};
