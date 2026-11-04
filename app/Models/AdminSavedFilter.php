<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSavedFilter extends Model
{
    protected $fillable = ['user_id', 'scope', 'name', 'criteria'];

    protected function casts(): array
    {
        return ['criteria' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
