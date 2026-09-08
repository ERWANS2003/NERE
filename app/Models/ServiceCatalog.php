<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCatalog extends Model
{
    protected $table = 'service_catalog';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'category_id',
        'request_form',
        'approval_workflow',
        'requires_approval',
        'estimated_time',
        'default_assignee_id',
        'default_team_id',
        'is_active',
        'order',
    ];

    protected $casts = [
        'request_form' => 'array',
        'approval_workflow' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function defaultTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'default_team_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
