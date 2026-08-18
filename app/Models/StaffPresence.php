<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'date',
        'status',
        'arrival_time',
        'departure_time',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'arrival_time' => 'string',
            'departure_time' => 'string',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
