<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorMapItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['map_id', 'device_id', 'x', 'y'];

    protected function casts(): array
    {
        return ['x' => 'float', 'y' => 'float'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MonitorDevice::class, 'device_id');
    }
}
