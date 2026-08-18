<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'matricule',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'birth_certificate_number',
        'nationality',
        'photo',
        'father_name',
        'father_phone',
        'mother_name',
        'mother_phone',
        'guardian_name',
        'guardian_phone',
        'guardian_relationship',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function infirmaryVisits()
    {
        return $this->hasMany(InfirmaryVisit::class);
    }

    public function currentEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class)
            ->whereHas(
                'academicYear',
                fn($q) =>
                $q->where('is_active', true)
            );
    }

    // ── Méthodes utilitaires ───────────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return "{$this->last_name} {$this->first_name}";
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('images/default-avatar.png');
    }

    // Génère un matricule automatique
    public static function generateMatricule(): string
    {
        $year   = date('Y');
        $nextId = (static::withTrashed()->max('id') ?? 0) + 1;
        return 'CP' . $year . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    public function activeEnrollmentForYear(?int $yearId = null): ?StudentEnrollment
    {
        return app(\App\Services\EnrollmentService::class)
            ->activeEnrollmentForYear($this, $yearId);
    }
}
