<?php

namespace App\Models\Intranet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionEvent extends Model
{
    protected $table = 'intranet_submission_events';

    public const UPDATED_AT = null; // immutable

    protected $fillable = ['submission_id', 'actor_id', 'action', 'payload'];

    protected $casts = ['payload' => 'array'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
