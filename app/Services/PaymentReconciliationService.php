<?php

namespace App\Services;

use App\Models\FeeInstallment;
use App\Models\StudentEnrollment;
use App\Models\StudentPayment;
use Illuminate\Support\Facades\Schema;

/**
 * Rebuilds payment snapshots in the order in which payments actually occurred.
 * The database rows and receipt numbers are kept; only derived totals and bulk
 * allocations are refreshed.
 */
class PaymentReconciliationService
{
    public function reconcileEnrollment(StudentEnrollment|int $enrollment): void
    {
        $enrollment = $enrollment instanceof StudentEnrollment
            ? $enrollment
            : StudentEnrollment::findOrFail($enrollment);

        $feeStructure = $enrollment->classGroup()
            ->with('feeStructures.installments')
            ->first()?->feeStructures->first();

        if (! $feeStructure) {
            return;
        }

        $installments = $feeStructure->installments
            ->sortBy('installment_number')
            ->values();
        $remainingByInstallment = $installments
            ->mapWithKeys(fn (FeeInstallment $installment) => [
                $installment->id => max(0, (int) $installment->amount),
            ])
            ->all();

        $events = StudentPayment::query()
            ->where('student_enrollment_id', $enrollment->id)
            ->whereNull('parent_payment_id')
            ->where(function ($query) {
                $query->whereNotNull('fee_installment_id')
                    ->orWhere('is_bulk', true);
            })
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $totalDue = (int) $installments->sum('amount');
        $runningPaid = 0;
        $hasSnapshots = Schema::hasColumn('student_payments', 'snapshot_total_due')
            && Schema::hasColumn('student_payments', 'snapshot_total_paid')
            && Schema::hasColumn('student_payments', 'snapshot_total_remaining');

        foreach ($events as $event) {
            $effectiveAmount = (int) $event->amount_paid
                + (int) ($event->scholarship_amount ?? 0);
            $runningPaid += $effectiveAmount;

            if ($hasSnapshots) {
                $event->forceFill([
                    'snapshot_total_due' => $totalDue,
                    'snapshot_total_paid' => $runningPaid,
                    'snapshot_total_remaining' => max(0, $totalDue - $runningPaid),
                ])->saveQuietly();
            }

            if ($event->is_bulk) {
                $this->rebuildBulkAllocations($event, $installments, $remainingByInstallment);
                continue;
            }

            if ($event->fee_installment_id && array_key_exists($event->fee_installment_id, $remainingByInstallment)) {
                $remainingByInstallment[$event->fee_installment_id] = max(
                    0,
                    $remainingByInstallment[$event->fee_installment_id] - $effectiveAmount
                );
            }
        }
    }

    private function rebuildBulkAllocations($payment, $installments, array &$remainingByInstallment): void
    {
        $oldReceiptNumbers = $payment->allocations()->orderBy('id')->pluck('receipt_number')->filter()->values()->all();
        $payment->allocations()->delete();
        $cashRemaining = (int) $payment->amount_paid;
        $scholarshipRemaining = (int) ($payment->scholarship_amount ?? 0);
        $allocationIndex = 0;

        foreach ($installments as $installment) {
            if ($cashRemaining + $scholarshipRemaining <= 0) break;
            $available = (int) ($remainingByInstallment[$installment->id] ?? 0);
            if ($available <= 0) continue;
            $need = min($available, $cashRemaining + $scholarshipRemaining);
            $useScholarship = min($need, $scholarshipRemaining);
            $useCash = $need - $useScholarship;
            $allocationIndex++;

            StudentPayment::create([
                'student_enrollment_id' => $payment->student_enrollment_id,
                'parent_payment_id' => $payment->id,
                'fee_installment_id' => $installment->id,
                'amount_paid' => $useCash,
                'scholarship_amount' => $useScholarship,
                'payment_date' => $payment->payment_date,
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference,
                'receipt_number' => $oldReceiptNumbers[$allocationIndex - 1] ?? ($payment->receipt_number . '-A' . $allocationIndex),
                'recorded_by' => $payment->recorded_by,
                'notes' => $payment->notes ?: 'Paiement en bloc',
                'is_bulk' => false,
            ]);

            $cashRemaining -= $useCash;
            $scholarshipRemaining -= $useScholarship;
            $remainingByInstallment[$installment->id] = $available - $need;
        }
    }
}
