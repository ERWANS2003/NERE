<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorMap extends Model
{
    protected $fillable = ['name', 'background_path'];

    public function items(): HasMany
    {
        return $this->hasMany(MonitorMapItem::class, 'map_id');
    }
}
