<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualInsolvable extends Model
{
    protected $fillable = [
        'student_enrollment_id',
        'academic_year_id',
        'installment_label',
        'note',
        'total_due',
        'total_paid',
        'remaining',
        'selected_installments',
        'recorded_by',
    ];

    protected $casts = [
        'selected_installments' => 'array',
        'total_due' => 'integer',
        'total_paid' => 'integer',
        'remaining' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
