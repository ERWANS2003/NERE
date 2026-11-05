<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Salon de discussion : général (tout le monde), département (ses membres) ou
 * privé (membres choisis par un administrateur).
 */
class ChatRoom extends Model
{
    public const GENERAL = 'general';

    public const DEPARTMENT = 'department';

    public const PRIVATE = 'private';

    /** Délai pendant lequel l'auteur peut modifier ou supprimer son message. */
    public const EDIT_WINDOW_MINUTES = 15;

    /** Durée de conservation des messages. */
    public const RETENTION_MONTHS = 6;

    protected $fillable = [
        'name',
        'type',
        'department_id',
        'created_by',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_members')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /** Crée le salon général et ceux des départements actifs qui n'en ont pas encore. */
    public static function ensureDefaultRooms(): void
    {
        static::query()->firstOrCreate(['type' => self::GENERAL], ['name' => 'Général']);

        $existing = static::query()->where('type', self::DEPARTMENT)->pluck('department_id');
        Department::active()->whereNotIn('id', $existing)->get(['id', 'name'])
            ->each(fn (Department $department) => static::query()->create([
                'type' => self::DEPARTMENT,
                'department_id' => $department->id,
                'name' => $department->name,
            ]));
    }

    /**
     * Salons que l'utilisateur peut lire. Les administrateurs voient en plus
     * tous les salons de département pour la modération ($forModeration).
     */
    public function scopeAccessibleTo(Builder $query, User $user, bool $forModeration = true): void
    {
        $departmentIds = $user->departments()->where('is_active', true)->pluck('departments.id');
        $allDepartments = $forModeration && $user->is_super_admin;

        $query->where(function (Builder $query) use ($user, $departmentIds, $allDepartments): void {
            $query->where('type', self::GENERAL)
                ->orWhere(function (Builder $query) use ($departmentIds, $allDepartments): void {
                    $query->where('type', self::DEPARTMENT)
                        ->whereHas('department', fn (Builder $department) => $department->where('is_active', true));
                    if (! $allDepartments) {
                        $query->whereIn('department_id', $departmentIds);
                    }
                })
                ->orWhere(fn (Builder $query) => $query->where('type', self::PRIVATE)
                    ->whereHas('members', fn (Builder $members) => $members->whereKey($user->getKey())));
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        return static::query()->whereKey($this->getKey())->accessibleTo($user)->exists();
    }

    public function canBeModeratedBy(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $this->type === self::DEPARTMENT && $user->canManageDepartment($this->department_id);
    }

    /** Nombre de messages non lus par salon (hors messages de l'utilisateur). */
    public static function unreadCountsFor(User $user): array
    {
        $roomIds = static::query()->accessibleTo($user, false)->pluck('id');
        if ($roomIds->isEmpty()) {
            return [];
        }

        return ChatMessage::query()
            ->whereIn('chat_messages.chat_room_id', $roomIds)
            ->where(fn (Builder $query) => $query->whereNull('chat_messages.user_id')->orWhere('chat_messages.user_id', '!=', $user->getKey()))
            ->leftJoin('chat_reads', fn ($join) => $join
                ->on('chat_reads.chat_room_id', '=', 'chat_messages.chat_room_id')
                ->where('chat_reads.user_id', '=', $user->getKey()))
            ->whereRaw('chat_messages.id > COALESCE(chat_reads.last_read_message_id, 0)')
            ->groupBy('chat_messages.chat_room_id')
            ->selectRaw('chat_messages.chat_room_id, COUNT(*) as total')
            ->pluck('total', 'chat_room_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function markReadBy(User $user): void
    {
        $lastId = (int) $this->messages()->withTrashed()->max('id');
        if ($lastId === 0) {
            return;
        }

        ChatRead::query()->updateOrCreate(
            ['chat_room_id' => $this->getKey(), 'user_id' => $user->getKey()],
            ['last_read_message_id' => $lastId],
        );
    }
}
