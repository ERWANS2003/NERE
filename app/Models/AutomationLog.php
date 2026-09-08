<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'automation_id',
        'status',
        'context',
        'result',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'result' => 'array',
        'executed_at' => 'datetime',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(WorkflowAutomation::class, 'automation_id');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('executed_at', '>=', now()->subDays($days));
    }
}
