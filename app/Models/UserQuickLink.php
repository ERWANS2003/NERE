<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuickLink extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'icon',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
