<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalZone extends Model
{
    protected $fillable = [
        'name',
        'code',
        'zone_type',
        'site_id',
        'access_level_required',
        'is_hazardous',
        'description',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_hazardous' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function safetyIncidents(): HasMany
    {
        return $this->hasMany(SafetyIncident::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHazardous($query)
    {
        return $query->where('is_hazardous', true);
    }
}
