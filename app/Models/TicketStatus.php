<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    protected $table = 'ticket_statuses';

    protected $fillable = ['nom', 'slug', 'couleur', 'ordre', 'est_final', 'met_en_pause_sla'];

    protected function casts(): array
    {
        return [
            'est_final' => 'boolean',
            'met_en_pause_sla' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
