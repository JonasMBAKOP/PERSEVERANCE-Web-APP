<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPayment extends Model
{
    protected $fillable = [
        'student_enrollment_id',
        'parent_payment_id',
        'fee_installment_id',
        'amount_paid',
        'scholarship_amount',
        'payment_date',
        'payment_method',
        'reference',
        'receipt_number',
        'recorded_by',
        'notes',
        'is_bulk',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid'               => 'decimal:0',
            'scholarship_amount'        => 'integer',
            'payment_date'              => 'date',
            'is_bulk'                   => 'boolean',
            'snapshot_total_due'        => 'integer',
            'snapshot_total_paid'       => 'integer',
            'snapshot_total_remaining'  => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $payment): void {
            if (
                ! is_null($payment->snapshot_total_due)
                && ! is_null($payment->snapshot_total_paid)
                && ! is_null($payment->snapshot_total_remaining)
            ) {
                return;
            }

            $enrollment = $payment->studentEnrollment()->first();
            if (! $enrollment) {
                return;
            }

            $feeStructure = $enrollment->classGroup()->with('feeStructures.installments')->first()?->feeStructures->first();
            $totalDue = (int) ($feeStructure?->installments->sum('amount') ?? 0);
            $totalPaid = (int) static::visible()->where('student_enrollment_id', $enrollment->id)->sum('amount_paid');
            $totalScholarship = (int) static::visible()->where('student_enrollment_id', $enrollment->id)->sum('scholarship_amount');
            $totalRemaining = max(0, $totalDue - ($totalPaid + $totalScholarship));

            $payment->forceFill([
                'snapshot_total_due'       => $totalDue,
                'snapshot_total_paid'      => $totalPaid,
                'snapshot_total_remaining' => $totalRemaining,
            ])->saveQuietly();
        });
    }

    // ── Relations ──────────────────────────────────────────────────────────
    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function feeInstallment()
    {
        return $this->belongsTo(FeeInstallment::class);
    }

    public function parentPayment()
    {
        return $this->belongsTo(self::class, 'parent_payment_id');
    }

    public function allocations()
    {
        return $this->hasMany(self::class, 'parent_payment_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ── Méthodes utilitaires ───────────────────────────────────────────────
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash'          => 'Espèces',
            'orange_money'  => 'Orange Money',
            'mtn_momo'      => 'MTN MoMo',
            'bank_transfer' => 'Virement bancaire',
            default         => 'Autre',
        };
    }

    public function scopeVisible($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('parent_payment_id')
              ->orWhere('is_bulk', true);
        });
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->is_bulk) {
            return 'Paiement en bloc';
        }

        return $this->feeInstallment?->label ?? '—';
    }

    public function getIsManualInsolvablePaymentAttribute(): bool
    {
        return $this->fee_installment_id === null && ! $this->is_bulk;
    }

    public function getReceiptPaymentSubjectAttribute(): ?string
    {
        if ($this->is_manual_insolvable_payment) {
            return null;
        }

        if ($this->is_bulk) {
            return $this->allocation_summary ?: ($this->feeInstallment?->label ?? 'Paiement groupe');
        }

        return $this->feeInstallment?->label;
    }

    public function getAllocationSummaryAttribute(): string
    {
        if (! $this->is_bulk) {
            return $this->feeInstallment?->label ?? '—';
        }

        return $this->effectiveAllocations()
            ->map(function ($allocation) {
                $label = $allocation->feeInstallment?->label;
                if (! $label) {
                    return null;
                }

                return $label . ' (' . number_format((int) $allocation->effective_amount_paid, 0, ',', ' ') . ' FCFA)';
            })
            ->filter()
            ->implode(', ');
    }

    public function effectiveAllocations()
    {
        if (! $this->is_bulk) {
            return collect();
        }

        $allocations = $this->allocations()
            ->with('feeInstallment')
            ->get()
            ->sortBy(function ($allocation) {
                return $allocation->feeInstallment?->installment_number ?? 0;
            })
            ->values();

        if ($allocations->isEmpty()) {
            return collect();
        }

        $leftoverScholarship = max(0, (int) $this->scholarship_amount - $allocations->sum('scholarship_amount'));

        return $allocations->map(function ($allocation) use (&$leftoverScholarship) {
            $effectiveScholarship = (int) $allocation->scholarship_amount;
            if ($leftoverScholarship > 0) {
                $installmentAmount = (int) ($allocation->feeInstallment?->amount ?? 0);
                $remainingCapacity = max(0, $installmentAmount - (int) $allocation->amount_paid - $effectiveScholarship);
                $extra = min($leftoverScholarship, $remainingCapacity);
                $effectiveScholarship += $extra;
                $leftoverScholarship -= $extra;
            }

            $allocation->setAttribute('effective_scholarship_amount', $effectiveScholarship);
            $allocation->setAttribute('effective_amount_paid', (int) $allocation->amount_paid + $effectiveScholarship);

            return $allocation;
        });
    }

    public function getEffectiveScholarshipAmountAttribute(): int
    {
        if (! $this->parent_payment_id) {
            return (int) $this->scholarship_amount;
        }

        $parent = $this->parentPayment;
        if (! $parent || $parent->scholarship_amount <= 0) {
            return (int) $this->scholarship_amount;
        }

        $allocations = $parent->allocations()->with('feeInstallment')->get()
            ->sortBy(fn ($allocation) => $allocation->feeInstallment?->installment_number ?? 0)
            ->values();

        $leftoverScholarship = max(0, (int) $parent->scholarship_amount - $allocations->sum('scholarship_amount'));
        $extraForThis = 0;

        foreach ($allocations as $allocation) {
            $currentScholarship = (int) $allocation->scholarship_amount;
            $installmentAmount = (int) ($allocation->feeInstallment?->amount ?? 0);
            $remainingCapacity = max(0, $installmentAmount - (int) $allocation->amount_paid - $currentScholarship);
            $extra = 0;
            if ($leftoverScholarship > 0) {
                $extra = min($leftoverScholarship, $remainingCapacity);
                $leftoverScholarship -= $extra;
            }

            if ($allocation->id === $this->id) {
                $extraForThis = $extra;
                break;
            }
        }

        return (int) $this->scholarship_amount + $extraForThis;
    }

    public function getEffectiveAmountPaidAttribute(): int
    {
        $scholarship = $this->effective_scholarship_amount ?? (int) $this->scholarship_amount;

        return (int) $this->amount_paid + (int) $scholarship;
    }

    // Génère un numéro de reçu unique
    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = 'RCP-' . $year . '-';

        // count() can reuse an existing number after a payment deletion.
        $lastNumber = static::query()
            ->where('receipt_number', 'like', $prefix . '%')
            ->pluck('receipt_number')
            ->map(function ($receiptNumber): int {
                return preg_match('/(\d+)$/', (string) $receiptNumber, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        do {
            $lastNumber++;
            $receiptNumber = $prefix . str_pad((string) $lastNumber, 5, '0', STR_PAD_LEFT);
        } while (static::where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }
}
