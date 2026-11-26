<?php

namespace App\Models;

use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['sensor_id', 'user_id', 'from_status', 'to_status', 'message'];

    protected function casts(): array
    {
        return [
            'from_status' => MonitorStatus::class,
            'to_status' => MonitorStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(MonitorSensor::class, 'sensor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
