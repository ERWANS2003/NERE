<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuChoice extends Model
{
    protected $fillable = [
        'registration_id',
        'menu_day_id',
        'selected_menu',
    ];

    protected function casts(): array
    {
        return ['selected_menu' => 'integer'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(MenuRegistration::class, 'registration_id');
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(MenuDay::class, 'menu_day_id');
    }
}
