<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOffBalance extends Model
{
    protected $fillable = [
        'user_id',
        'policy_id',
        'year',
        'total_days',
        'used_days',
        'remaining_days',
        'carry_over_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_days' => 'decimal:1',
        'used_days' => 'decimal:1',
        'remaining_days' => 'decimal:1',
        'carry_over_days' => 'decimal:1',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(TimeOffPolicy::class);
    }

    public function updateBalance()
    {
        $this->remaining_days = $this->total_days + $this->carry_over_days - $this->used_days;
        $this->save();
    }
}
