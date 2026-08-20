<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'titre', 'contenu', 'ticket_category_id', 'auteur_id',
        'mots_cles', 'vues', 'utile_count', 'publie',
    ];

    protected function casts(): array
    {
        return ['publie' => 'boolean'];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'knowledge_article_id');
    }

    // Phase 9 : suggestion d'articles pertinents avant la création d'un ticket
    public function scopeSuggeresPour(Builder $query, string $terme, ?int $categoryId = null): Builder
    {
        return $query->where('publie', true)
            ->when($categoryId, fn ($q) => $q->where('ticket_category_id', $categoryId))
            ->whereFullText(['titre', 'contenu', 'mots_cles'], $terme)
            ->limit(5);
    }
}
