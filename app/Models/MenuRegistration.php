<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuRegistration extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'employee_name',
        'employee_identifier',
        'department',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MenuCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(MenuChoice::class, 'registration_id');
    }
}
