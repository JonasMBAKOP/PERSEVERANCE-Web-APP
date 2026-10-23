<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    public const DAILY_ABSENCE_HOURS = 7.0;

    protected $fillable = [
        'student_enrollment_id',
        'absence_date',
        'status',
        'arrival_time',
        'observation',
        'delay_minutes',
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
            'delay_minutes' => 'integer',
            'is_justified' => 'boolean',
        ];
    }

    public function getEffectiveHoursAttribute(): float
    {
        $hours = (float) $this->hours;

        if ($this->status === 'absent'
            && $this->timetable_slot_id === null
            && $this->class_subject_id === null) {
            $hours = self::DAILY_ABSENCE_HOURS;
        }

        return $hours + ((int) $this->delay_minutes / 60);
    }

    public function getEffectiveMinutesAttribute(): int
    {
        return (int) round($this->effective_hours * 60);
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
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    public function timetableSlot()
    {
        return $this->belongsTo(TimetableSlot::class);
    }
}
