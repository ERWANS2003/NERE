<?php

namespace App\Models;

use App\Enums\TicketType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'titre', 'description', 'type', 'solution', 'motif_attente',
        'user_id', 'site_id', 'departement_id', 'ticket_category_id',
        'impact', 'urgence', 'ticket_priority_id', 'ticket_status_id',
        'team_id', 'assigned_to', 'assigned_by', 'date_assignation', 'date_mise_en_attente',
        'sla_id', 'date_echeance_reponse', 'date_echeance_resolution',
        'date_premiere_reponse', 'date_resolution', 'date_cloture', 'sla_depasse',
        'sla_temps_pause_secondes',
        'satisfaction_note', 'satisfaction_commentaire',
    ];

    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'date_assignation' => 'datetime',
            'date_mise_en_attente' => 'datetime',
            'date_echeance_reponse' => 'datetime',
            'date_echeance_resolution' => 'datetime',
            'date_premiere_reponse' => 'datetime',
            'date_resolution' => 'datetime',
            'date_cloture' => 'datetime',
            'sla_depasse' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            $ticket->reference ??= static::genererReference();
            $ticket->type ??= TicketType::Incident;
        });
    }

    public static function genererReference(): string
    {
        $annee = now()->year;
        $dernier = static::withTrashed()
            ->whereYear('created_at', $annee)
            ->count() + 1;

        return sprintf('TCK-%d-%06d', $annee, $dernier);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function priorite(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id');
    }

    public function statut(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function sla(): BelongsTo
    {
        return $this->belongsTo(Sla::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class)->orderByDesc('created_at');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_ticket');
    }

    public function estModifiable(): bool
    {
        return ! $this->statut?->est_final;
    }

    public function estEnAttente(): bool
    {
        return $this->statut?->slug === 'en_attente';
    }

    public function estResolu(): bool
    {
        return $this->statut?->slug === 'resolu';
    }

    public function scopeOuverts(Builder $query): Builder
    {
        return $query->whereHas('statut', fn ($q) => $q->where('est_final', false));
    }

    public function scopeCritiques(Builder $query): Builder
    {
        return $query->whereHas('priorite', fn ($q) => $q->where('niveau', '>=', 4));
    }

    public function scopeParSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (blank($terme)) {
            return $query;
        }

        return $query->where(function ($q) use ($terme) {
            $q->where('reference', 'like', "%{$terme}%")
                ->orWhere('titre', 'like', "%{$terme}%")
                ->orWhere('description', 'like', "%{$terme}%");
        });
    }

    public function estEnRetard(): bool
    {
        if ($this->date_mise_en_attente || $this->statut?->met_en_pause_sla) {
            return false;
        }

        return $this->date_echeance_resolution
            && now()->greaterThan($this->date_echeance_resolution)
            && ! $this->statut?->est_final;
    }
}
