<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPriority extends Model
{
    protected $fillable = ['nom', 'niveau', 'couleur', 'delai_resolution_heures'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function slas(): HasMany
    {
        return $this->hasMany(Sla::class);
    }
}
