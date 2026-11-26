<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorDiscovery extends Model
{
    protected $fillable = ['requested_by', 'group_id', 'cidr', 'status', 'results', 'error'];

    protected function casts(): array
    {
        return ['results' => 'array'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MonitorGroup::class, 'group_id');
    }
}
