<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    public const TRIMESTERS_PER_YEAR = 3;

    public const SEQUENCES_PER_TRIMESTER = 2;

    public const SEQUENCES_PER_YEAR = self::TRIMESTERS_PER_YEAR * self::SEQUENCES_PER_TRIMESTER;
    /** @return array<int, array<int, string>> */
    public static function sequenceCalendar(): array
    {
        return [
            1 => [1 => 'SEQ 1', 2 => 'SEQ 2'],
            2 => [3 => 'SEQ 3', 4 => 'SEQ 4'],
            3 => [5 => 'SEQ 5', 6 => 'SEQ 6'],
        ];
    }


    protected $fillable = [
        'label',
        'start_date',
        'end_date',
        'is_active',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_active'  => 'boolean',
            'is_locked'  => 'boolean',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────
    public function trimesters()
    {
        return $this->hasMany(Trimester::class)->orderBy('number');
    }

    public function sequences()
    {
        return $this->hasMany(Sequence::class)->orderBy('number');
    }

    public function classGroups()
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function studentEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    // ── Méthodes utilitaires ───────────────────────────────────────────────
    // Récupère l'année scolaire active
    public static function active(): ?static
    {
        return static::where('is_active', true)->first();
    }

    // Active cette année et désactive les autres
    public function activate(): void
    {
        static::query()->update(['is_active' => false]);

        $attributes = ['is_active' => true];
        if (array_key_exists('is_locked', $this->attributes)) {
            $attributes['is_locked'] = false;
        }

        $this->update($attributes);
    }

    // Scope : année active
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Vérifie si l'année est clôturée (non modifiable)
    public function isClosed(): bool
    {
        // Backwards compatible: if a dedicated 'is_locked' attribute exists, use it.
        // Otherwise treat years as not closed so administrators can reactiver/fermer
        // manuellement même si la date de fin est passée.
        if (array_key_exists('is_locked', $this->attributes)) {
            return (bool) $this->is_locked;
        }

        return false;
    }

    /** @return list<array{month: int, year: int, label: string, full_label: string}> */
    public function monthPeriods(): array
    {
        if (! $this->start_date || ! $this->end_date) {
            return [];
        }

        $shortLabels = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Aoû',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
        ];

        $periods = [];
        $current = $this->start_date->copy()->startOfMonth();
        $end     = $this->end_date->copy()->startOfMonth();

        while ($current <= $end) {
            $periods[] = [
                'month'      => $current->month,
                'year'       => $current->year,
                'label'      => $shortLabels[$current->month],
                'full_label' => $current->locale('fr')->translatedFormat('M Y'),
            ];
            $current->addMonth();
        }

        return $periods;
    }
}
