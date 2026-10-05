<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'departement_id',
        'site_id',
        'matricule',
        'telephone',
        'poste',
        'est_technicien',
        'disponible',
        'actif',
        'is_super_admin',  // intranet : super admin global
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'est_technicien'     => 'boolean',
            'disponible'         => 'boolean',
            'actif'              => 'boolean',
            'is_super_admin'     => 'boolean',
            'derniere_connexion' => 'datetime',
        ];
    }

    // Relations
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')->withPivot('chef_equipe')->withTimestamps();
    }

    public function ticketsCrees(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function ticketsAssignes(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class, 'auteur_id');
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    // Helpers permissions/rôles
    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function hasAnyRole(array $slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->role?->hasPermission($slug) ?? false;
    }

    public function estDemandeur(): bool
    {
        return $this->role?->estDemandeur() ?? false;
    }

    public function estPilotage(): bool
    {
        return $this->role?->estPilotage() ?? false;
    }

    public function estDirection(): bool
    {
        return $this->role?->estDirection() ?? false;
    }

    public function departementDirige(): HasOne
    {
        return $this->hasOne(Departement::class, 'directeur_id');
    }

    public function estDisponiblePourAffectation(): bool
    {
        return $this->est_technicien && $this->actif && $this->disponible;
    }

    public function isDirecteur(): bool
    {
        return $this->estDirection();
    }

    /* ── Relations Intranet ───────────────────────────────────── */

    /** Départements intranet où l'utilisateur a un rôle. */
    public function intranetDepartments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Intranet\Department::class,
            'intranet_department_user'
        )->withPivot(['role', 'tech_level'])->withTimestamps();
    }

    /** Rôle intranet de l'utilisateur dans un département donné (null si absent). */
    public function intranetPivot(\App\Models\Intranet\Department $dept): ?object
    {
        if ($this->is_super_admin) {
            return (object) ['role' => 'director', 'tech_level' => null];
        }

        return $dept->users()->where('users.id', $this->id)->first()?->pivot;
    }
}
