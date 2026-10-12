<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'event_date',
        'event_time',
        'department_id',
        'user_id',
        'created_by',
        'all_day',
    ];

    protected $casts = [
        'event_date' => 'date',
        'all_day' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUpcoming($query, $days = 30)
    {
        return $query->where('event_date', '>=', now())
                     ->where('event_date', '<=', now()->addDays($days))
                     ->orderBy('event_date');
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
