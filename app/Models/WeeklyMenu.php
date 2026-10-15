<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class WeeklyMenu extends Model
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const CLOSED = 'closed';
    public const ARCHIVED = 'archived';

    protected $fillable = [
        'meal_location_id',
        'created_by',
        'week_start',
        'week_end',
        'response_deadline',
        'status',
        'published_at',
        'closed_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'response_deadline' => 'datetime',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(MealLocation::class, 'meal_location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function days(): HasMany
    {
        return $this->hasMany(WeeklyMenuDay::class)->orderBy('menu_date');
    }

    public function isOpen(): bool
    {
        return $this->status === self::PUBLISHED
            && $this->response_deadline?->isFuture()
            && ! $this->closed_at;
    }
}
