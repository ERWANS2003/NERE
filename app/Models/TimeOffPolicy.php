<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeOffPolicy extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'annual_allocation',
        'carry_over_limit',
        'requires_approval',
        'advance_notice_days',
        'is_active',
    ];

    protected $casts = [
        'annual_allocation' => 'integer',
        'carry_over_limit' => 'integer',
        'requires_approval' => 'boolean',
        'advance_notice_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(TimeOffBalance::class, 'policy_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(TimeOffRequest::class, 'policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
