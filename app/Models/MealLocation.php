<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class MealLocation extends Model
{
    protected $fillable = ['name', 'code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'meal_location_id');
    }

    public function weeklyMenus(): HasMany
    {
        return $this->hasMany(WeeklyMenu::class);
    }
}
