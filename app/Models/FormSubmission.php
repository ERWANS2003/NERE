<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_id',
        'user_id',
        'data',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
    ];

    protected $casts = [
        'data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DepartmentForm::class, 'form_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
