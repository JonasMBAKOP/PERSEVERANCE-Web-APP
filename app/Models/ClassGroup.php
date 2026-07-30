<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClassGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'level_id',
        'name',
        'sub_group',
        'series',
        'max_students',
        'titular_staff_id',
        'room',
    ];

    protected function casts(): array
    {
        return [
            'max_students' => 'integer',
        ];
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function titularStaff()
    {
        return $this->belongsTo(Staff::class, 'titular_staff_id');
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function studentEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function gradeLocks()
    {
        return $this->hasMany(GradeLock::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public static function composeName(string $levelName, ?string $series = null, ?string $subGroup = null): string
    {
        return Str::squish(implode(' ', array_filter([
            $levelName,
            $subGroup,
        ], fn (?string $part) => filled($part))));
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getStudentsCountAttribute(): int
    {
        return $this->studentEnrollments()
                    ->where('status', 'active')
                    ->count();
    }
}
