<?php

use App\Models\StudentPayment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Align existing receipt snapshots with the current fee configuration.
     * Historical payment totals remain unchanged; only the fee total and the
     * resulting balance are refreshed once.
     */
    public function up(): void
    {
        StudentPayment::query()
            ->whereNotNull('snapshot_total_due')
            ->whereNotNull('snapshot_total_remaining')
            ->with([
                'studentEnrollment.classGroup.feeStructures.installments',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($payments): void {
                foreach ($payments as $payment) {
                    // Manual insolvables have their own independent total_due.
                    if ($payment->fee_installment_id === null && ! $payment->is_bulk) {
                        continue;
                    }

                    $feeStructure = $payment->studentEnrollment?->classGroup?->feeStructures?->first();
                    $newTotalDue = (int) ($feeStructure?->installments?->sum('amount') ?? 0);

                    if ($newTotalDue <= 0) {
                        continue;
                    }

                    $oldTotalDue = (int) $payment->snapshot_total_due;
                    $oldRemaining = (int) $payment->snapshot_total_remaining;
                    $historicalPaid = max(0, $oldTotalDue - $oldRemaining);

                    $payment->forceFill([
                        'snapshot_total_due' => $newTotalDue,
                        'snapshot_total_remaining' => max(0, $newTotalDue - $historicalPaid),
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        // The previous snapshots cannot be reconstructed safely after fee
        // changes, so rollback intentionally does not overwrite them.
    }
};
