<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'nom',
        'slug',
        'description',
    ];

    /**
     * Les utilisateurs ayant ce rôle
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Les permissions de ce rôle
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Accesseur pour compatibilité avec 'name'
     */
    public function getNameAttribute()
    {
        return $this->nom;
    }

    public function setNameAttribute($value)
    {
        $this->nom = $value;
    }
}
