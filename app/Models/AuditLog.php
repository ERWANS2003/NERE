<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'anciennes_valeurs',
        'nouvelles_valeurs',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'anciennes_valeurs' => 'array',
        'nouvelles_valeurs' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
