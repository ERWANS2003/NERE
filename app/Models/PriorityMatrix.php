<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriorityMatrix extends Model
{
    protected $table = 'priority_matrices';

    protected $fillable = ['impact', 'urgence', 'ticket_priority_id'];

    public function priorite(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'ticket_priority_id');
    }
}
