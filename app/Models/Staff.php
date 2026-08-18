<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    public const POSITIONS = [
        'enseignant',
        'prefet_des_etudes',
        'econome',
        'surveillant_general',
        'surveillant_de_secteur',
        'vigile',
        'agent_d_entretien',
        'directeur',
        'secretaire',
        'infirmier',
        'autre',
    ];

// Début

    public const DIPLOMAS = [
        'BEPC', 'BAC', 'Licence', 'Master', 'Doctorat', 'Autre',
    ];

    public const CONTRACT_TYPES = [
        'permanent', 'vacataire', 'semi_permanent',
    ];

// Fin


    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'photo',
        'diploma',
        'origin_school',
        'start_date',
        'contract_type',
        'monthly_salary',
        'hourly_rate',
        'period_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'start_date'    => 'date',
            'is_active'     => 'boolean',
            'monthly_salary'=> 'integer',
            'hourly_rate'   => 'integer',
            'period_rate'   => 'integer',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function positions()
    {
        return $this->hasMany(StaffPosition::class);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function presences()
    {
        return $this->hasMany(StaffPresence::class);
    }

    public function titularClasses()
    {
        return $this->hasMany(ClassGroup::class, 'titular_staff_id');
    }

    public function censeurAssignments()
    {
        return $this->hasMany(CenseurAssignment::class);
    }

    // ── Méthodes utilitaires ───────────────────────────────────────────────
    // Nom complet
    public function getFullNameAttribute(): string
    {
        return mb_strtoupper("{$this->last_name} {$this->first_name}");
    }

    public function setFirstNameAttribute(?string $value): void
    {
        $this->attributes['first_name'] = mb_strtoupper(trim((string) $value));
    }

    public function setLastNameAttribute(?string $value): void
    {
        $this->attributes['last_name'] = mb_strtoupper(trim((string) $value));
    }

    // Nom complet avec civilité
    public function getHonorificFullNameAttribute(): string
    {
        $gender = strtolower((string) $this->gender);
        $prefix = in_array($gender, ['female', 'femme', 'f'], true) ? 'Mme' : 'M.';

        return $prefix . ' ' . mb_strtoupper($this->full_name);
    }

    // Poste principal
    public function getPrimaryPositionAttribute(): ?StaffPosition
    {
        return $this->positions()->where('is_primary', true)->first();
    }

    // URL de la photo
    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('images/default-avatar.png');
    }

// Début

    public function getContractLabelAttribute(): string
    {
        return match ($this->contract_type) {
            'permanent' => 'Permanent',
            'vacataire' => 'Vacataire',
            'semi_permanent' => 'Semi Permanent',
            default     => $this->contract_type,
        };
    }

    public function getDiplomaLabelAttribute(): ?string
    {
        return $this->diploma;
    }

    public function getSalaryDisplayAttribute(): string
    {
        if (in_array($this->contract_type, ['permanent', 'semi_permanent'], true)) {
            return $this->monthly_salary
                ? number_format($this->monthly_salary) . ' FCFA / mois'
                : 'À renseigner';
        }

        if ($this->contract_type === 'vacataire') {
            return $this->hourly_rate
                ? number_format($this->hourly_rate) . ' FCFA / h'
                : 'À renseigner';
        }

        $parts = [];
        if ($this->hourly_rate) {
            $parts[] = number_format($this->hourly_rate) . ' FCFA / h';
        }
        if ($this->period_rate) {
            $parts[] = number_format($this->period_rate) . ' FCFA / période';
        }

        return $parts ? implode(' / ', $parts) : 'À renseigner';
    }

    public function isTeacher(): bool
    {
        return $this->positions()
            ->whereIn('position', [
                'enseignant',
                'prefet_des_etudes',
                'censeur',
                'surveillant_general',
            ])
            ->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTeachers($query)
    {
        return $query->active()->whereHas('positions', fn ($q) =>
            $q->whereIn('position', [
                'enseignant',
                'prefet_des_etudes',
                'censeur',
                'surveillant_general',
            ])
        );
    }

    public function scopeWithPosition($query, string $position)
    {
        return $query->whereHas('positions', fn ($q) =>
            $q->where('position', $position)
        );
    }

    public static function positionLabels(): array
    {
        return [
            'enseignant'             => 'Enseignant(e)',
            'prefet_des_etudes'      => 'Préfet des études / Dean',
            'econome'                => 'Économe',
            'surveillant_general'    => 'Surveillant(e) Général(e)',
            'surveillant_de_secteur' => 'Surveillant(e) de Secteur',
            'vigile'                 => 'Vigile',
            'agent_d_entretien'      => 'Agent d\'entretien',
            'directeur'              => 'Directeur / Principal',
            'secretaire'             => 'Secrétaire',
            'infirmier'              => 'Infirmier(ère)',
            'autre'                  => 'Autre',
        ];
    }

    public static function contractLabels(): array
    {
        return [
            'permanent' => 'Permanent',
            'vacataire' => 'Vacataire',
            'semi_permanent' => 'Semi Permanent',
        ];
    }

// Fin
}
