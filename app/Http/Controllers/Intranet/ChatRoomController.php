<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\User;
use App\Services\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Salons privés : créés et gérés par les administrateurs. */
class ChatRoomController extends Controller
{
    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('intranet.chat.room-form', ['room' => new ChatRoom, 'memberIds' => [$request->user()->id], 'users' => $this->users()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        $validated = $this->validated($request);

        $room = ChatRoom::create(['name' => $validated['name'], 'type' => ChatRoom::PRIVATE, 'created_by' => $request->user()->id]);
        $room->members()->sync(array_unique([...$validated['members'], $request->user()->id]));
        AdminAudit::record('création salon privé', 'chat', $room, null, ['membres' => $room->members()->count()]);

        return redirect()->route('intranet.chat.show', $room)->with('success', 'Le salon privé a été créé.');
    }

    public function edit(Request $request, ChatRoom $room): View
    {
        $this->ensureAdmin($request, $room);

        return view('intranet.chat.room-form', ['room' => $room, 'memberIds' => $room->members()->pluck('users.id')->all(), 'users' => $this->users()]);
    }

    public function update(Request $request, ChatRoom $room): RedirectResponse
    {
        $this->ensureAdmin($request, $room);
        $validated = $this->validated($request);

        $room->update(['name' => $validated['name']]);
        $room->members()->sync($validated['members']);
        AdminAudit::record('modification salon privé', 'chat', $room, null, ['membres' => count($validated['members'])]);

        return redirect()->route('intranet.chat.show', $room->members()->whereKey($request->user()->id)->exists() ? $room : ChatRoom::where('type', ChatRoom::GENERAL)->firstOrFail())
            ->with('success', 'Le salon privé a été modifié.');
    }

    public function destroy(Request $request, ChatRoom $room): RedirectResponse
    {
        $this->ensureAdmin($request, $room);

        AdminAudit::record('suppression salon privé', 'chat', $room, ['nom' => $room->name]);
        $room->delete();

        return redirect()->route('intranet.chat.index')->with('success', 'Le salon privé a été supprimé.');
    }

    private function ensureAdmin(Request $request, ?ChatRoom $room = null): void
    {
        abort_unless($request->user()->is_super_admin, 403);
        abort_if($room !== null && $room->type !== ChatRoom::PRIVATE, 404);
    }

    /**
     * @return array{name: string, members: array<int, int>}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'members' => ['required', 'array', 'min:1'],
            'members.*' => ['integer', Rule::exists('users', 'id')->where('actif', true)],
        ]);
    }

    private function users()
    {
        return User::query()->active()->orderBy('name')->get(['id', 'name', 'poste']);
    }
}
