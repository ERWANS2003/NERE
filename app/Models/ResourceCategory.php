<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'category_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
