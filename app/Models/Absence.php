<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    protected $fillable = [
        'student_enrollment_id',
        'absence_date',
        'period',
        'timetable_slot_id',
        'timetable_period_index',
        'class_subject_id',
        'hours',
        'is_justified',
        'justification',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'absence_date' => 'date',
            'hours'        => 'decimal:1',
            'is_justified' => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────
    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function classSubject()
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function timetableSlot()
    {
        return $this->belongsTo(TimetableSlot::class);
    }
}
