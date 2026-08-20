<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['nom', 'departement_id', 'description'];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function techniciens(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')->withPivot('chef_equipe')->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TicketCategory::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** Technicien disponible le moins chargé de l'équipe (Phase 6) */
    public function technicienLeMoinsCharge()
    {
        return $this->techniciens()
            ->where('disponible', true)
            ->where('actif', true)
            ->withCount(['ticketsAssignes as tickets_ouverts_count' => function ($q) {
                $q->whereHas('statut', fn ($s) => $s->where('est_final', false));
            }])
            ->orderBy('tickets_ouverts_count')
            ->first();
    }
}
