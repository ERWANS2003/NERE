<?php

namespace App\Models\Intranet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $table = 'intranet_services';

    protected $fillable = ['department_id', 'name', 'description', 'is_active', 'position'];

    protected $casts = ['is_active' => 'boolean'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    public function publishedForms(): HasMany
    {
        return $this->forms()->where('status', 'published');
    }
}
