<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPaySlip extends Model
{
    protected $table = 'staff_pay_slips';

    protected $fillable = [
        'staff_id',
        'amount_received',
        'period',
    ];

    protected $casts = [
        'amount_received' => 'float',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }
}
