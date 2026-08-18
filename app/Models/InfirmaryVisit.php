<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfirmaryVisit extends Model
{
    protected $fillable = [
        'academic_year_id', 'student_id', 'class_group_id', 'recorded_by_staff_id', 'recorded_by_name',
        'visit_date', 'visit_time', 'student_name', 'student_gender', 'class_name', 'student_age',
        'parent_phone', 'temperature', 'visit_reason', 'treatment',
    ];

    protected function casts(): array
    {
        return ['visit_date' => 'date', 'student_age' => 'integer', 'temperature' => 'decimal:1'];
    }

    public function recordedBy() { return $this->belongsTo(Staff::class, 'recorded_by_staff_id'); }
    public function student() { return $this->belongsTo(Student::class); }
    public function classGroup() { return $this->belongsTo(ClassGroup::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function getRecorderNameAttribute(): string { return $this->recorded_by_name ?: $this->recordedBy?->full_name ?: 'Non renseigne (consultation anterieure)'; }
}
