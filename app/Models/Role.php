<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['nom', 'slug', 'description'];

    /**
     * Slugs référencés par le code applicatif (hasRole, middleware, navigation).
     * Toute évolution de la hiérarchie doit passer par cette liste.
     */
    public const SLUG_ADMIN = 'admin';
    public const SLUG_DSI = 'dsi';
    public const SLUG_DIRECTEUR = 'directeur_departement';
    public const SLUG_TECHNICIEN = 'technicien';
    public const SLUG_DEMANDEUR = 'demandeur';

    /**
     * Rôles système : le slug est figé car il est comparé strictement par
     * User::hasRole(), par le middleware CheckRole et par la navigation.
     */
    public const SLUGS_SYSTEME = [
        self::SLUG_ADMIN,
        self::SLUG_DSI,
        self::SLUG_DIRECTEUR,
        self::SLUG_TECHNICIEN,
        self::SLUG_DEMANDEUR,
    ];

    /** Rôles ayant accès au pilotage (Kanban, SLA, modèles, actifs). */
    public const SLUGS_PILOTAGE = [
        self::SLUG_ADMIN,
        self::SLUG_DSI,
        self::SLUG_DIRECTEUR,
        self::SLUG_TECHNICIEN,
    ];

    /** Rôles ayant accès aux rapports et à la gestion de département. */
    public const SLUGS_DIRECTION = [
        self::SLUG_ADMIN,
        self::SLUG_DSI,
        self::SLUG_DIRECTEUR,
    ];

    /** Rôles relevant de l'interface demandeur (pas de menu pilotage). */
    public const SLUGS_DEMANDEUR = [
        self::SLUG_DEMANDEUR,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function hasPermission(string $slug): bool
    {
        return $this->slug === self::SLUG_ADMIN
            || $this->permissions()->whereIn('slug', [$slug, '*'])->exists();
    }

    public function estSysteme(): bool
    {
        return in_array($this->slug, self::SLUGS_SYSTEME, true);
    }

    public function estDemandeur(): bool
    {
        return in_array($this->slug, self::SLUGS_DEMANDEUR, true);
    }

    public function estPilotage(): bool
    {
        return in_array($this->slug, self::SLUGS_PILOTAGE, true);
    }

    public function estDirection(): bool
    {
        return in_array($this->slug, self::SLUGS_DIRECTION, true);
    }
}
