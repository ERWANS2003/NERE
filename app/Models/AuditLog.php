<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id',
        'anciennes_valeurs', 'nouvelles_valeurs', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'anciennes_valeurs' => 'array',
            'nouvelles_valeurs' => 'array',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
