<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyIncident extends Model
{
    protected $table = 'safety_incidents';

    protected $fillable = [
        'titre',
        'description',
        'severity',
        'statut',
        'operational_zone_id',
        'reported_by',
        'reported_at',
        'incident_at',
        'investigated_by',
        'investigation_notes',
        'corrective_actions',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'incident_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    public function operationalZone(): BelongsTo
    {
        return $this->belongsTo(OperationalZone::class);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('reported_at', '>=', now()->subDays($days));
    }

    /**
     * Valeurs réellement acceptées par l'enum PostgreSQL `statut`.
     *
     * @return array<string, string>
     */
    public static function statuts(): array
    {
        return [
            'reported' => 'Signalé',
            'investigating' => 'En investigation',
            'resolved' => 'Résolu',
            'closed' => 'Clôturé',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function severites(): array
    {
        return [
            'minor' => 'Mineure',
            'moderate' => 'Modérée',
            'serious' => 'Sérieuse',
            'critical' => 'Critique',
        ];
    }

    public function libelleStatut(): string
    {
        return self::statuts()[$this->statut] ?? $this->statut;
    }

    public function libelleSeverite(): string
    {
        return self::severites()[$this->severity] ?? $this->severity;
    }

    /** Classes de badge alignées sur les tokens du design system. */
    public function couleurStatut(): string
    {
        return match ($this->statut) {
            'resolved', 'closed' => 'nm-badge-success',
            'investigating' => 'nm-badge-warning',
            default => 'nm-badge-info',
        };
    }

    public function couleurSeverite(): string
    {
        return match ($this->severity) {
            'critical' => 'nm-badge-danger',
            'serious' => 'nm-badge-warning',
            'moderate' => 'nm-badge-info',
            default => 'nm-badge-neutral',
        };
    }
}
