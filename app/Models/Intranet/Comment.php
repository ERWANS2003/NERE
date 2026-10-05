<?php

namespace App\Models\Intranet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $table = 'intranet_comments';

    protected $fillable = ['submission_id', 'author_id', 'body', 'is_internal'];

    protected $casts = ['is_internal' => 'boolean'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
