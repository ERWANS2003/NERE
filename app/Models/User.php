<?php

namespace App\Models;

use App\Enums\DepartmentRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;

class User extends Authenticatable implements LdapAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use AuthenticatesWithLdap, HasFactory, Notifiable;

    /** Nombre d'echecs de connexion consecutifs avant verrouillage (EF1). */
    public const MAX_LOGIN_ATTEMPTS = 5;

    /** Duree du verrouillage apres saturation des tentatives. */
    public const LOCKOUT_MINUTES = 15;

    public const LOWEST_TECH_LEVEL = 1;

    public const HIGHEST_TECH_LEVEL = 3;

    /**
     * Permissions de role systeme qui elevent le role dans chacun des
     * departements de l'utilisateur (profils Chef de departement et
     * Technicien de departement). Le plus haut role l'emporte sur le pivot.
     */
    public const DEPARTMENT_ROLE_PERMISSIONS = [
        'departments.manage' => DepartmentRole::Director,
        'tickets.update' => DepartmentRole::Technician,
    ];

    protected $fillable = [
        'matricule',
        'name',
        'email',
        'password',
        'is_super_admin',
        'actif',
        'role_id',
        'departement_id',
        'site_id',
        'telephone',
        'poste',
        'est_technicien',
        'disponible',
        // Organigramme
        'manager_id',
        'position_title',
        'poste_modifie_localement',
        'org_level',
        'hire_date',
        'birth_date',
        // Liaison Active Directory (LdapRecord)
        'guid',
        'domain',
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
            'actif' => 'boolean',
            'poste_modifie_localement' => 'boolean',
            'est_technicien' => 'boolean',
            'disponible' => 'boolean',
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
            'tutorial_completed_at' => 'datetime',
            'org_level' => 'integer',
            'hire_date' => 'date',
            'birth_date' => 'date',
        ];
    }

    // Accesseur pour compatibilité avec is_active
    public function getIsActiveAttribute()
    {
        return $this->actif;
    }

    public function setIsActiveAttribute($value)
    {
        $this->actif = $value;
    }

    /**
     * URL de l'avatar. Aucune colonne de photo n'existe pour l'instant :
     * l'annuaire se rabat sur les initiales. Renvoyer null explicitement évite
     * une recherche d'attribut inexistant dans les vues.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return null;
    }

    /**
     * Prénom déduit du nom complet (pas de colonne dédiée).
     */
    public function getFirstNameAttribute(): string
    {
        return explode(' ', trim($this->name))[0] ?? $this->name;
    }

    /**
     * Initiales (2 lettres max) pour l'avatar.
     */
    public function getInitialsAttribute(): string
    {
        $mots = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initiales = mb_substr($mots[0] ?? '', 0, 1);

        if (count($mots) > 1) {
            $initiales .= mb_substr($mots[count($mots) - 1], 0, 1);
        }

        return mb_strtoupper($initiales) ?: '?';
    }

    /**
     * Poste affiché : privilégie position_title, retombe sur poste.
     */
    public function getJobTitleAttribute(): ?string
    {
        return $this->position_title ?: $this->poste;
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

    /**
     * Les rôles système de l'utilisateur
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->is_super_admin
            || $this->roles()
                ->whereHas('permissions', fn (Builder $query) => $query->where('slug', $permission))
                ->exists();
    }

    /**
     * Ressources de la bibliothèque mises en favori par l'utilisateur.
     */
    public function favoriteResources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_user')->withTimestamps();
    }

    /**
     * Supérieur hiérarchique direct (organigramme).
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * Collaborateurs directs (organigramme).
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /**
     * Départements que cet utilisateur dirige (droit de publier une annonce,
     * de gérer services/formulaires...). L'admin général est traité à part
     * par les appelants (canManageDepartment() le court-circuite déjà).
     *
     * @return Collection<int, Department>
     */
    public function directedDepartments(): Collection
    {
        if ($this->is_super_admin) {
            return Department::active()->get();
        }

        $this->loadAuthorisations();

        $ids = collect($this->memberships ?? [])
            ->filter(fn (array $m) => $m['role'] === DepartmentRole::Director->value)
            ->keys();

        return $ids->isEmpty() ? collect() : Department::whereKey($ids)->active()->get();
    }

    /** Responsable direct d'au moins un collaborateur, directeur de departement ou admin. */
    public function canApproveTimeOff(): bool
    {
        return $this->is_super_admin
            || $this->isDepartmentHead()
            || self::query()->where('manager_id', $this->getKey())->exists();
    }

    public function isDepartmentHead(): bool
    {
        $this->loadAuthorisations();

        return collect($this->memberships)
            ->contains(fn (array $membership) => $membership['role'] === DepartmentRole::Director->value);
    }

    public function hasDepartmentProcessingAccess(): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $this->loadAuthorisations();

        return collect($this->memberships)->contains(
            fn (array $membership) => in_array($membership['role'], [
                DepartmentRole::Technician->value,
                DepartmentRole::Director->value,
            ], true)
        );
    }

    /**
     * Les demandes (soumissions de formulaires) creees par l'utilisateur.
     */
    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function menuRegistrations(): HasMany
    {
        return $this->hasMany(MenuRegistration::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
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

    /** Role donne par les roles systeme ; false tant qu'il n'est pas calcule. */
    private DepartmentRole|false|null $systemRole = false;

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
            $role = $this->elevatedDepartmentRole(DepartmentRole::tryFrom($row->role) ?? DepartmentRole::User);

            $this->memberships[(int) $row->department_id] = [
                'role' => $role->value,
                'tech_level' => $this->elevatedTechLevel($role, $row->tech_level === null ? null : (int) $row->tech_level),
            ];
        }

        return $this;
    }

    /**
     * Role de departement donne par les roles systeme, ou null.
     * Utilise la relation roles.permissions si elle est deja chargee.
     */
    public function systemDepartmentRole(): ?DepartmentRole
    {
        if ($this->systemRole !== false) {
            return $this->systemRole;
        }

        $slugs = $this->relationLoaded('roles')
            ? $this->roles->loadMissing('permissions')->flatMap->permissions->pluck('slug')
            : Permission::query()
                ->whereIn('slug', array_keys(self::DEPARTMENT_ROLE_PERMISSIONS))
                ->whereHas('roles.users', fn (Builder $query) => $query->whereKey($this->getKey()))
                ->pluck('slug');

        $this->systemRole = null;
        foreach (self::DEPARTMENT_ROLE_PERMISSIONS as $slug => $role) {
            if ($slugs->contains($slug)) {
                $this->systemRole = $role;
                break;
            }
        }

        return $this->systemRole;
    }

    /** Le plus haut entre le role du pivot et celui des roles systeme. */
    public function elevatedDepartmentRole(DepartmentRole $pivotRole): DepartmentRole
    {
        $systemRole = $this->systemDepartmentRole();
        $rang = fn (?DepartmentRole $role): int => match ($role) {
            DepartmentRole::Director => 2,
            DepartmentRole::Technician => 1,
            default => 0,
        };

        return $rang($systemRole) > $rang($pivotRole) ? $systemRole : $pivotRole;
    }

    /** Un technicien sans niveau sur le pivot (eleve par son role systeme) traite le niveau 1. */
    public function elevatedTechLevel(DepartmentRole $role, ?int $pivotLevel): ?int
    {
        return $role === DepartmentRole::Technician ? ($pivotLevel ?? self::LOWEST_TECH_LEVEL) : $pivotLevel;
    }

    /**
     * Utilisateurs qui traitent les demandes d'un departement :
     * technicien ou directeur sur le pivot, ou membre eleve par un role systeme.
     */
    public function scopeHandlersOf(Builder $query, int $departmentId): Builder
    {
        return $query
            ->whereHas('departments', fn (Builder $departments) => $departments->where('departments.id', $departmentId))
            ->where(fn (Builder $handlers) => $handlers
                ->whereHas('departments', fn (Builder $departments) => $departments
                    ->where('departments.id', $departmentId)
                    ->whereIn('department_user.role', [DepartmentRole::Technician->value, DepartmentRole::Director->value]))
                ->orWhereHas('roles.permissions', fn (Builder $permissions) => $permissions
                    ->whereIn('slug', array_keys(self::DEPARTMENT_ROLE_PERMISSIONS))));
    }

    /** @return list<int> departements ou l'utilisateur traite les demandes */
    public function handledDepartmentIds(): array
    {
        $this->loadAuthorisations();

        return collect($this->memberships)
            ->filter(fn (array $membership) => in_array($membership['role'], [
                DepartmentRole::Technician->value,
                DepartmentRole::Director->value,
            ], true))
            ->keys()
            ->all();
    }

    /**
     * Vide le cache puis recharge. A appeler apres un attach/detach sur le
     * pivot dans le meme cycle de requete (tests, actions en cascade).
     */
    public function refreshAuthorisations(): static
    {
        $this->memberships = null;
        $this->systemRole = false;

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
        return $query->where('actif', true);
    }
}
