<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class WeeklyMenuChoice extends Model
{
    public const MENU_1 = 'menu_1';
    public const MENU_2 = 'menu_2';
    public const NO_MEAL = 'no_meal';

    protected $fillable = [
        'weekly_menu_day_id',
        'user_id',
        'choice',
        'comment',
        'selected_at',
    ];

    protected $casts = ['selected_at' => 'datetime'];

    public function day(): BelongsTo
    {
        return $this->belongsTo(WeeklyMenuDay::class, 'weekly_menu_day_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->choice) {
            self::MENU_1 => 'Menu 1',
            self::MENU_2 => 'Menu 2',
            self::NO_MEAL => 'Aucun repas',
            default => 'Non répondu',
        };
    }
}
