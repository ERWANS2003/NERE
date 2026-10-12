<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Resource extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'file_type',
        'file_path',
        'file_size',
        'tags',
        'department_id',
        'uploaded_by',
        'download_count',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'download_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Utilisateurs ayant mis cette ressource en favori (table pivot resource_user).
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_user')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('download_count', 'desc')->limit($limit);
    }

    public function incrementDownloads()
    {
        $this->increment('download_count');
    }
}
