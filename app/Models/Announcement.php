<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'priority',
        'department_id',
        'created_by',
        'is_active',
        'published_at',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Annonces que cet utilisateur peut voir : celles de toute l'entreprise et de ses départements. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->is_super_admin) {
            return;
        }

        $query->where(fn (Builder $visible) => $visible
            ->whereNull('department_id')
            ->orWhere('created_by', $user->id)
            ->orWhereIn('department_id', $user->departments()->pluck('departments.id')));
    }

    /**
     * Destinataires de la notification de publication, sans l'auteur.
     *
     * @return Collection<int, User>
     */
    public function audience(): Collection
    {
        return User::query()
            ->where('actif', true)
            ->when($this->department_id, fn (Builder $users) => $users
                ->whereHas('departments', fn (Builder $departments) => $departments->where('departments.id', $this->department_id)))
            ->when($this->created_by, fn (Builder $users) => $users->whereKeyNot($this->created_by))
            ->get();
    }

    public function replaceAttachment(UploadedFile $file): void
    {
        $this->removeAttachment();
        $this->update([
            'attachment_path' => $file->store('announcements/'.$this->id, 'local'),
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getMimeType() ?: 'application/octet-stream',
            'attachment_size' => $file->getSize(),
        ]);
    }

    public function removeAttachment(): void
    {
        if (! $this->attachment_path) {
            return;
        }

        Storage::disk('local')->delete($this->attachment_path);
        $this->update([
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }
}
