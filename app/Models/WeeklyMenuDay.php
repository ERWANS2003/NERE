<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class WeeklyMenuDay extends Model
{
    protected $fillable = [
        'weekly_menu_id',
        'menu_date',
        'day_name',
        'menu_1',
        'menu_2',
        'dessert',
    ];

    protected $casts = ['menu_date' => 'date'];

    public function weeklyMenu(): BelongsTo
    {
        return $this->belongsTo(WeeklyMenu::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(WeeklyMenuChoice::class);
    }
}
