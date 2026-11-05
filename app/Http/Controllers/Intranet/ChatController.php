<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\AdminAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ChatController extends Controller
{
    private const HISTORY = 100;

    public function index(): RedirectResponse
    {
        ChatRoom::ensureDefaultRooms();

        return redirect()->route('intranet.chat.show', ChatRoom::where('type', ChatRoom::GENERAL)->firstOrFail());
    }

    public function show(Request $request, ChatRoom $room): View
    {
        $user = $request->user();
        ChatRoom::ensureDefaultRooms();
        abort_unless($room->isAccessibleBy($user), 403);

        $rooms = ChatRoom::query()->accessibleTo($user)->with('department:id,name')->orderBy('name')->get();
        $ownDepartmentIds = $user->departments()->pluck('departments.id');
        $canModerate = $room->canBeModeratedBy($user);
        $messages = $room->messages()->withTrashed()->with('user')->latest('id')->limit(self::HISTORY)->get()->reverse()->values();
        $room->markReadBy($user);

        return view('intranet.chat.show', [
            'room' => $room,
            'groups' => [
                'Général' => $rooms->where('type', ChatRoom::GENERAL),
                'Mes départements' => $rooms->where('type', ChatRoom::DEPARTMENT)->whereIn('department_id', $ownDepartmentIds),
                'Salons privés' => $rooms->where('type', ChatRoom::PRIVATE),
                'Autres départements (modération)' => $rooms->where('type', ChatRoom::DEPARTMENT)->whereNotIn('department_id', $ownDepartmentIds),
            ],
            'unread' => ChatRoom::unreadCountsFor($user),
            'config' => [
                'messages' => $messages->map(fn (ChatMessage $message) => $message->toChatArray($user, $canModerate))->all(),
                'now' => now()->toIso8601String(),
                'pollUrl' => route('intranet.chat.poll', $room),
                'postUrl' => route('intranet.chat.messages.store', $room),
                'messageUrl' => route('intranet.chat.messages.update', ['message' => '__ID__']),
            ],
        ]);
    }

    public function poll(Request $request, ChatRoom $room): JsonResponse
    {
        $user = $request->user();
        abort_unless($room->isAccessibleBy($user), 403);

        $validated = $request->validate([
            'after' => ['required', 'integer', 'min:0'],
            'since' => ['required', 'date'],
        ]);
        $now = now();
        $canModerate = $room->canBeModeratedBy($user);

        $messages = $room->messages()->withTrashed()->with('user')
            ->where('id', '>', $validated['after'])
            ->orderBy('id')
            ->limit(self::HISTORY)
            ->get();
        $changes = $room->messages()->withTrashed()->with('user')
            ->where('id', '<=', $validated['after'])
            ->where('updated_at', '>=', Carbon::parse($validated['since']))
            ->get();

        if ($messages->isNotEmpty()) {
            $room->markReadBy($user);
        }

        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $message) => $message->toChatArray($user, $canModerate))->all(),
            'changes' => $changes->map(fn (ChatMessage $message) => $message->toChatArray($user, $canModerate))->all(),
            'now' => $now->toIso8601String(),
        ]);
    }

    public function store(Request $request, ChatRoom $room): JsonResponse
    {
        $user = $request->user();
        abort_unless($room->isAccessibleBy($user), 403);

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $message = $room->messages()->create([
            'user_id' => $user->getKey(),
            'body' => trim($validated['body']),
        ]);
        $room->markReadBy($user);

        return response()->json($message->load('user')->toChatArray($user, $room->canBeModeratedBy($user)), 201);
    }

    public function update(Request $request, ChatMessage $message): JsonResponse
    {
        $user = $request->user();
        abort_unless($message->isEditableBy($user), 403, 'Ce message ne peut plus être modifié.');

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $message->update(['body' => trim($validated['body']), 'edited_at' => now()]);

        return response()->json($message->load('user')->toChatArray($user, $message->room->canBeModeratedBy($user)));
    }

    public function destroy(Request $request, ChatMessage $message): JsonResponse
    {
        $user = $request->user();
        $room = $message->room;
        $canModerate = $room->canBeModeratedBy($user) && $room->isAccessibleBy($user);
        abort_unless($message->isEditableBy($user) || $canModerate, 403);

        if ($message->user_id !== $user->getKey()) {
            AdminAudit::record('suppression message chat', 'chat', $message, ['salon' => $room->name, 'auteur' => $message->user?->name, 'message' => $message->body], null, 'Message de '.($message->user?->name ?? 'inconnu'));
        }
        $message->delete();

        return response()->json($message->load('user')->toChatArray($user, $canModerate));
    }
}
