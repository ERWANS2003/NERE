<?php

namespace App\Models\Intranet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    protected $table = 'intranet_workflow_steps';

    protected $fillable = [
        'form_id', 'order', 'name',
        'responsible_role', 'tech_level',
        'needs_approval', 'sla_hours',
    ];

    protected $casts = [
        'needs_approval' => 'boolean',
        'order'          => 'integer',
        'tech_level'     => 'integer',
        'sla_hours'      => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
