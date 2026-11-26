<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorReading extends Model
{
    public $timestamps = false;

    protected $fillable = ['sensor_id', 'checked_at', 'ok', 'value'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime', 'ok' => 'boolean', 'value' => 'float'];
    }
}
