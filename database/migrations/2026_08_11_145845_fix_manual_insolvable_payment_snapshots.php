<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Corriger les snapshot_total_remaining pour les paiements d'insolvables manuels
        // Ces paiements doivent avoir un 'remaining' figé au moment du paiement
        // et non un reste global qui change

        // Pour chaque inscription avec des paiements d'insolvables manuels
        $manualPayments = DB::table('student_payments')
            ->where('fee_installment_id', null)
            ->where('is_bulk', false)
            ->whereNull('parent_payment_id')
            ->orderBy('student_enrollment_id')
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->get();

        foreach ($manualPayments as $payment) {
            // Si snapshot_total_due est NULL, il faut le récupérer du ManualInsolvable
            $totalDue = (int) $payment->snapshot_total_due;
            if ($totalDue === 0) {
                $manual = DB::table('manual_insolvables')
                    ->where('student_enrollment_id', $payment->student_enrollment_id)
                    ->latest('id')
                    ->first();
                $totalDue = (int) ($manual?->total_due ?? 0);
            }

            if ($totalDue === 0) {
                continue; // Ignorer si on ne peut pas déterminer le total
            }

            // Récupérer tous les paiements manuels de cette inscription jusqu'à celui-ci (inclus)
            $totalPaidUpToThisPayment = DB::table('student_payments')
                ->where('student_enrollment_id', $payment->student_enrollment_id)
                ->where('fee_installment_id', null)
                ->where('is_bulk', false)
                ->whereNull('parent_payment_id')
                ->where(function ($query) use ($payment) {
                    $query->where('payment_date', '<', $payment->payment_date)
                          ->orWhere(function ($q) use ($payment) {
                              $q->where('payment_date', $payment->payment_date)
                                ->where('id', '<=', $payment->id);
                          });
                })
                ->sum('amount_paid');

            // Le remaining pour ce paiement doit être: total_due - total_paid_jusqu'à_ce_paiement
            $newRemaining = max(0, $totalDue - $totalPaidUpToThisPayment);

            DB::table('student_payments')
                ->where('id', $payment->id)
                ->update([
                    'snapshot_total_due' => $totalDue,
                    'snapshot_total_paid' => $totalPaidUpToThisPayment,
                    'snapshot_total_remaining' => $newRemaining,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Impossible de reverser complètement car on n'a pas les anciens snapshots
        // On peut juste remettre les remaning à 0
        DB::table('student_payments')
            ->where('fee_installment_id', null)
            ->where('is_bulk', false)
            ->whereNull('parent_payment_id')
            ->update(['snapshot_total_remaining' => 0]);
    }
};
