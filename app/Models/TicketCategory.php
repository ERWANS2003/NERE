<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    protected $fillable = ['nom', 'parent_id', 'team_id', 'description', 'actif'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(TicketCategory::class, 'parent_id');
    }

    // Équipe par défaut, utilisée pour l'affectation automatique (Phase 6)
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }
}
