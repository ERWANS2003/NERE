<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentService extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'description',
        'icon',
        'link_type',
        'link_url',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function destinationUrl(): ?string
    {
        if (! is_string($this->link_url) || trim($this->link_url) === '') {
            return null;
        }

        if ($this->link_type === 'external') {
            $url = filter_var($this->link_url, FILTER_VALIDATE_URL);
            $scheme = parse_url($this->link_url, PHP_URL_SCHEME);

            return $url !== false && $scheme === 'https'
                ? $this->link_url
                : null;
        }

        if (
            in_array($this->link_type, ['form', 'page'], true)
            && str_starts_with($this->link_url, '/intranet/')
            && ! str_starts_with($this->link_url, '//')
        ) {
            return $this->link_url;
        }

        return null;
    }
}
