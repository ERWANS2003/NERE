<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyIncident extends Model
{
    protected $table = 'safety_incidents';

    protected $fillable = [
        'incident_number',
        'title',
        'description',
        'severity',
        'incident_type',
        'location',
        'operational_zone_id',
        'reported_by',
        'reported_at',
        'investigated_by',
        'root_cause',
        'corrective_actions',
        'resolved_at',
        'status',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

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
}
