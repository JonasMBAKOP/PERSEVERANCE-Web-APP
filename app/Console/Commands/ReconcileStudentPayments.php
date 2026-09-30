<?php

namespace App\Console\Commands;

use App\Models\StudentEnrollment;
use App\Services\PaymentReconciliationService;
use Illuminate\Console\Command;

class ReconcileStudentPayments extends Command
{
    protected $signature = 'payments:reconcile
        {--enrollment= : Reconcile only one enrollment ID}';

    protected $description = 'Recalculate student payment receipts in chronological payment-date order';

    public function handle(PaymentReconciliationService $reconciliation): int
    {
        $query = StudentEnrollment::query()->with('classGroup.feeStructures.installments');
        if ($this->option('enrollment')) $query->whereKey((int) $this->option('enrollment'));
        $count = 0;
        $query->chunkById(100, function ($enrollments) use ($reconciliation, &$count): void {
            foreach ($enrollments as $enrollment) {
                $reconciliation->reconcileEnrollment($enrollment);
                $count++;
            }
        });
        $this->info("{$count} inscription(s) réconciliée(s).");
        return self::SUCCESS;
    }
}
