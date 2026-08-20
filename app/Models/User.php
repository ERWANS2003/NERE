<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'role_id', 'departement_id', 'site_id',
        'matricule', 'telephone', 'poste',
        'est_technicien', 'disponible', 'actif',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'est_technicien' => 'boolean',
            'disponible' => 'boolean',
            'actif' => 'boolean',
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

    // Helpers permissions/rôles
    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function hasPermission(string $slug): bool
    {
        return $this->role?->hasPermission($slug) ?? false;
    }

    public function estDisponiblePourAffectation(): bool
    {
        return $this->est_technicien && $this->actif && $this->disponible;
    }
}
