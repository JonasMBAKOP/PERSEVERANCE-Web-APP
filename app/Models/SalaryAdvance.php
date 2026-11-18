<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryAdvance extends Model
{
    protected $fillable = [
        'staff_id',
        'issued_on',
        'amount',
        'purpose',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'amount' => 'integer',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }

    public function repayments()
    {
        return $this->hasMany(SalaryAdvanceRepayment::class)
            ->orderBy('repaid_on')
            ->orderBy('id');
    }

    public function getRepaidAmountAttribute(): int
    {
        if (array_key_exists('repaid_amount', $this->attributes)) {
            return (int) $this->attributes['repaid_amount'];
        }

        return (int) $this->repayments()->sum('amount');
    }

    public function getBalanceAttribute(): int
    {
        return max(0, (int) $this->amount - $this->repaid_amount);
    }
}
