<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'photo', 'signature_seal', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login_at'     => 'datetime',
        ];
    }

    public function getDashboardRoute(): string
    {
        return match(true) {
            $this->hasRole('super-admin')            => route('admin.dashboard'),
            $this->hasRole('directeur')              => route('directeur.dashboard'),
            $this->hasRole('censeur')                => route('censeur.dashboard'),
            $this->hasRole('econome')                => route('econome.dashboard'),
            $this->hasRole('enseignant')             => route('enseignant.dashboard'),
            $this->hasRole('surveillant-general')    => route('surveillant.dashboard'),
            $this->hasRole('surveillant-de-secteur') => route('surveillant-secteur.dashboard'),
            $this->hasRole('secretaire')             => route('secretaire.dashboard'),
            $this->hasRole('infirmier')              => route('infirmary.dashboard'),
            default                                  => route('login'),
        };
    }

    // URL de la photo du compte de connexion uniquement.
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }

        return asset('images/default-avatar.png');
    }

    // Tableau de hiérarchie des rôles (plus le nombre est élevé, plus le rôle est puissant)
    private static array $roleHierarchy = [
        'super-admin'            => 8,
        'directeur'              => 7,
        'censeur'                => 6,
        'econome'                => 5,
        'surveillant-general'    => 4,
        'surveillant-de-secteur' => 3,
        'secretaire'             => 2,
        'infirmier'              => 2,
        'enseignant'             => 1,
    ];

    // Retourne le niveau hiérarchique du rôle le plus élevé
    public function getRoleLevel(): int
    {
        $maxLevel = 0;
        foreach ($this->roles as $role) {
            $level = self::$roleHierarchy[$role->name] ?? 0;
            if ($level > $maxLevel) {
                $maxLevel = $level;
            }
        }
        return $maxLevel;
    }

    // Vérifie si l'utilisateur connecté peut gérer un autre utilisateur
    public function canManage(User $target): bool
    {
        // Un utilisateur ne peut pas se gérer lui-même via cette méthode
        if ($this->id === $target->id) return false;

        // Super Admin gère tout le monde
        if ($this->hasRole('super-admin')) return true;

        // On ne peut gérer que les niveaux inférieurs au sien
        return $this->getRoleLevel() > $target->getRoleLevel();
    }

    // Permet de récupérer le niveau d'un rôle par son nom
    public function getRoleLevelByName(string $roleName): int
    {
        return self::$roleHierarchy[$roleName] ?? 0;
    }

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }
}
