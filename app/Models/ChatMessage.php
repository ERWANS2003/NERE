<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'body',
        'edited_at',
    ];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEditableBy(User $user): bool
    {
        return ! $this->trashed()
            && $this->user_id === $user->getKey()
            && $this->created_at->gt(now()->subMinutes(ChatRoom::EDIT_WINDOW_MINUTES));
    }

    /**
     * @return array<string, mixed>
     */
    public function toChatArray(User $viewer, bool $canModerate): array
    {
        $deleted = $this->trashed();

        return [
            'id' => $this->id,
            'author' => $this->user?->name ?? 'Utilisateur supprimé',
            'initials' => $this->user?->initials ?? '?',
            'mine' => $this->user_id === $viewer->getKey(),
            'body' => $deleted ? '' : $this->body,
            'deleted' => $deleted,
            'edited' => $this->edited_at !== null,
            'time' => $this->created_at->format('H:i'),
            'date' => $this->created_at->format('d/m/Y'),
            'can_edit' => $this->isEditableBy($viewer),
            'can_delete' => ! $deleted && ($this->isEditableBy($viewer) || $canModerate),
        ];
    }
}
