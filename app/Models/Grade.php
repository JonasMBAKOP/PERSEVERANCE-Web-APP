<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'student_enrollment_id',
        'class_subject_id',
        'sequence_id',
        'grade',
        'raw_grade',
        'is_absent',
        'entered_by',
        'entered_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'grade'      => 'decimal:2',
            'raw_grade'  => 'decimal:2',
            'is_absent'  => 'boolean',
            'entered_at' => 'datetime',
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

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Méthodes utilitaires ───────────────────────────────────────────────
    public function getAppreciationAttribute(): ?AppreciationScale
    {
        if ($this->grade === null) return null;
        return AppreciationScale::forGrade((float) $this->grade);
    }
}
