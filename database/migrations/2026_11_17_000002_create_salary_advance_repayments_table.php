<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_advance_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_advance_id')->constrained('salary_advances')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('repaid_on');
            $table->string('payment_method', 30)->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['salary_advance_id', 'repaid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_advance_repayments');
    }
};
