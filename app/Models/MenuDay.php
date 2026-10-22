<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuDay extends Model
{
    protected $fillable = [
        'campaign_id',
        'date',
        'menu_one',
        'menu_two',
        'dessert',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MenuCampaign::class, 'campaign_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(MenuChoice::class, 'menu_day_id');
    }
}
