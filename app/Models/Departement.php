<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    protected $fillable = ['nom', 'code', 'description', 'actif'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
