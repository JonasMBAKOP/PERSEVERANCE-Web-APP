<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSubject extends Model
{
    protected $fillable = [
        'class_group_id',
        'subject_id',
        'grading_scale',
        'coefficient',
        'hours_per_week',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grading_scale' => 'decimal:2',
            'coefficient'    => 'decimal:1',
            'hours_per_week' => 'decimal:1',
            'is_active'      => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────
    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    public function bulletinSubjectDetails()
    {
        return $this->hasMany(BulletinSubjectDetail::class);
    }

    // ── Méthodes utilitaires ───────────────────────────────────────────────
    // Enseignant assigné pour une année donnée
    public function teacherFor(int $academicYearId): ?Staff
    {
        $assignment = $this->teacherAssignments()
                           ->where('academic_year_id', $academicYearId)
                           ->with('staff')
                           ->first();
        return $assignment?->staff;
    }
}
