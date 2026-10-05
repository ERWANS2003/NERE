<?php

namespace App\Models\Intranet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $table = 'intranet_forms';

    protected $fillable = ['service_id', 'name', 'code', 'version', 'status', 'description'];

    protected $casts = ['version' => 'integer'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('position');
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Département via service. */
    public function getDepartmentAttribute(): Department
    {
        return $this->service->department;
    }
}
