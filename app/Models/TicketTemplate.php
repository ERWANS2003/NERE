<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ticket_category_id',
        'priorite_id',
        'titre_template',
        'description_template',
        'created_by',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function priorite(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
