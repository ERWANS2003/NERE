<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    public const CATEGORIES = [
        'suggestion' => 'Suggestion',
        'bug' => 'Problème rencontré',
        'compliment' => 'Ce qui me plaît',
        'other' => 'Autre',
    ];

    public const STATUSES = [
        'new' => 'Nouveau',
        'read' => 'Lu',
        'done' => 'Traité',
    ];

    protected $fillable = [
        'user_id',
        'category',
        'rating',
        'message',
        'status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
