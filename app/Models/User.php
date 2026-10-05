<?php

namespace App\Models;

use App\Enums\DepartmentRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Nombre d'echecs de connexion consecutifs avant verrouillage (EF1). */
    public const MAX_LOGIN_ATTEMPTS = 5;

    /** Duree du verrouillage apres saturation des tentatives. */
    public const LOCKOUT_MINUTES = 15;

    public const LOWEST_TECH_LEVEL = 1;

    public const HIGHEST_TECH_LEVEL = 3;

    protected $fillable = [
        'matricule',
        'name',
        'email',
        'password',
        'is_super_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    // ── Relations ───────────────────────────────────────────────────────

    /**
     * Les droits sont portada par le pivot : un utilisateur peut etre
     * technicien N2 en IT et simple utilisateur a la SURETE.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withPivot(['role', 'tech_level'])
            ->withTimestamps();
    }

    // ── Droits par departement ───────────────────────────────────────────

    /**
     * Droits mis en cache pour la requete courante, charges en une seule
     * requete. Sans ce cache, une grille de neuf cartes declenche neuf requetes
     * Supplementaires pour determiner le role de l'utilisateur sur chacune.
     *
     * @var array<int, array{role: string, tech_level: int|null}>|null
     */
    private ?array $memberships = null;

    /**
     * Charge en une seule requete tous les droits de l'utilisateur.
     */
    public function loadAuthorisations(): static
    {
        if ($this->memberships !== null || ! $this->exists) {
            return $this;
        }

        $rows = DB::table('department_user')
            ->where('user_id', $this->getKey())
            ->get(['department_id', 'role', 'tech_level']);

        $this->memberships = [];

        foreach ($rows as $row) {
            $this->memberships[(int) $row->department_id] = [
                'role' => $row->role,
                'tech_level' => $row->tech_level === null ? null : (int) $row->tech_level,
            ];
        }

        return $this;
    }

    /**
     * Vide le cache puis recharge. A appeler apres un attach/detach sur le
     * pivot dans le meme cycle de requete (tests, actions en cascade).
     */
    public function refreshAuthorisations(): static
    {
        $this->memberships = null;

        return $this->loadAuthorisations();
    }

    /**
     * Droits bruts de l'utilisateur sur un departement, ou null s'il n'y a pas
     * d'affectation. L'admin general n'a pas besoin d'une ligne de pivot pour
     * agir partout : les methodes de role le traitent en amont.
     *
     * @return array{role: string, tech_level: int|null}|null
     */
    public function membershipIn(int $departmentId): ?array
    {
        $this->loadAuthorisations();

        return $this->memberships[$departmentId] ?? null;
    }

    public function isMemberOf(int $departmentId): bool
    {
        return $this->is_super_admin || $this->membershipIn($departmentId) !== null;
    }

    /**
     * Role effectif dans un departement.
     *
     * L'admin general est traite comme directeur partout : c'est ce qui rend
     * `canValidateIn` et `canManageDepartment` vrais sans duplicer une condition
     * `is_super_admin` dans chaque verificateur.
     */
    public function roleIn(int $departmentId): ?DepartmentRole
    {
        if ($this->is_super_admin) {
            return DepartmentRole::Director;
        }

        $membership = $this->membershipIn($departmentId);

        return $membership === null ? null : DepartmentRole::tryFrom($membership['role']);
    }

    public function techLevelIn(int $departmentId): ?int
    {
        return $this->membershipIn($departmentId)['tech_level'] ?? null;
    }

    public function hasRoleIn(int $departmentId, DepartmentRole ...$roles): bool
    {
        $role = $this->roleIn($departmentId);

        return $role !== null && in_array($role, $roles, true);
    }

    /** Le directeur herite des droits technicien dans son departement. */
    public function canHandleIn(int $departmentId): bool
    {
        return $this->roleIn($departmentId)?->canHandleSubmissions() ?? false;
    }

    public function canManageDepartment(int $departmentId): bool
    {
        return $this->roleIn($departmentId)?->canManageDepartment() ?? false;
    }

    public function canValidateIn(int $departmentId): bool
    {
        return $this->roleIn($departmentId)?->canValidate() ?? false;
    }

    /**
     * Niveau de traitement effectif.
     *
     * Un directeur (et l'admin general) recoit le niveau le plus haut : ils
     * doivent voir toute la file du departement, pas seulement le niveau 1.
     * Un technicien N2 voit en revanche les demandes N1 et N2, jamais N3.
     */
    public function effectiveTechLevel(int $departmentId): ?int
    {
        $role = $this->roleIn($departmentId);

        if ($role === null) {
            return null;
        }

        return match ($role) {
            DepartmentRole::Director => self::HIGHEST_TECH_LEVEL,
            DepartmentRole::Technician => $this->techLevelIn($departmentId),
            DepartmentRole::User => null,
        };
    }

    // ── Verrouillage de connexion ────────────────────────────────────────

    public function isLockedOut(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function registerFailedAttempt(): void
    {
        $attempts = $this->failed_attempts + 1;

        $this->forceFill([
            'failed_attempts' => $attempts >= self::MAX_LOGIN_ATTEMPTS ? 0 : $attempts,
            'locked_until' => $attempts >= self::MAX_LOGIN_ATTEMPTS
                ? now()->addMinutes(self::LOCKOUT_MINUTES)
                : $this->locked_until,
        ])->save();
    }

    public function clearFailedAttempts(): void
    {
        if ($this->failed_attempts === 0 && $this->locked_until === null) {
            return;
        }

        $this->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    // ── Portee des requetes ──────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
