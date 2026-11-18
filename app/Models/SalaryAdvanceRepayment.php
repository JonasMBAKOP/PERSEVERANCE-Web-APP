<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryAdvanceRepayment extends Model
{
    protected $fillable = [
        'salary_advance_id',
        'amount',
        'repaid_on',
        'payment_method',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'repaid_on' => 'date',
        ];
    }

    public function advance()
    {
        return $this->belongsTo(SalaryAdvance::class, 'salary_advance_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }
}
