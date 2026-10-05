<?php

namespace App\Models\Intranet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'intranet_attachments';

    protected $fillable = ['submission_id', 'uploaded_by', 'original_name', 'path', 'mime', 'size'];

    protected $casts = ['size' => 'integer'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        $kb = $this->size / 1024;
        return $kb > 1024 ? round($kb / 1024, 1).' Mo' : round($kb, 0).' Ko';
    }
}
