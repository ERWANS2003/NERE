<?php

use App\Models\AdminAuditLog;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Department;
use App\Models\User;

function chatRooms(): array
{
    ChatRoom::ensureDefaultRooms();

    return [
        ChatRoom::where('type', ChatRoom::GENERAL)->firstOrFail(),
        fn (Department $department) => ChatRoom::where('department_id', $department->id)->firstOrFail(),
    ];
}

it('creates the general room and one room per active department', function () {
    $finance = Department::factory()->create(['name' => 'Finances', 'is_active' => true]);
    Department::factory()->create(['name' => 'Ancien', 'is_active' => false]);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('intranet.chat.index'))
        ->assertRedirect(route('intranet.chat.show', ChatRoom::where('type', ChatRoom::GENERAL)->first()));

    expect(ChatRoom::where('type', ChatRoom::DEPARTMENT)->pluck('department_id')->all())->toBe([$finance->id]);
    $this->get(route('intranet.chat.index'));
    expect(ChatRoom::count())->toBe(2);
});

it('limits department rooms to their members and lets admins moderate them', function () {
    $finance = Department::factory()->create(['is_active' => true]);
    $it = Department::factory()->create(['is_active' => true]);
    [$general, $roomOf] = chatRooms();
    $member = User::factory()->create();
    $member->departments()->attach($finance, ['role' => 'user']);
    $outsider = User::factory()->create();
    $outsider->departments()->attach($it, ['role' => 'user']);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($member)->get(route('intranet.chat.show', $roomOf($finance)))->assertOk();
    $this->actingAs($member)->get(route('intranet.chat.show', $general))->assertOk();
    $this->actingAs($outsider)->get(route('intranet.chat.show', $roomOf($finance)))->assertForbidden();
    $this->actingAs($outsider)->postJson(route('intranet.chat.messages.store', $roomOf($finance)), ['body' => 'Intrus'])->assertForbidden();
    $this->actingAs($admin)->get(route('intranet.chat.show', $roomOf($finance)))->assertOk()->assertSee('Autres départements (modération)');
});

it('posts, polls, edits and deletes messages within the allowed window', function () {
    [$general] = chatRooms();
    $alice = User::factory()->create(['name' => 'Alice Chat']);
    $bob = User::factory()->create(['name' => 'Bob Chat']);

    $since = now()->subSecond()->toIso8601String();
    $sent = $this->actingAs($alice)->postJson(route('intranet.chat.messages.store', $general), ['body' => 'Bonjour https://exemple.test'])
        ->assertCreated()
        ->assertJson(['author' => 'Alice Chat', 'mine' => true, 'can_edit' => true])
        ->json();

    $this->actingAs($bob)->getJson(route('intranet.chat.poll', [$general, 'after' => 0, 'since' => $since]))
        ->assertOk()
        ->assertJsonPath('messages.0.body', 'Bonjour https://exemple.test')
        ->assertJsonPath('messages.0.mine', false)
        ->assertJsonPath('messages.0.can_delete', false);

    $this->actingAs($bob)->putJson(route('intranet.chat.messages.update', $sent['id']), ['body' => 'Piraté'])->assertForbidden();
    $this->actingAs($bob)->deleteJson(route('intranet.chat.messages.destroy', $sent['id']))->assertForbidden();

    $this->actingAs($alice)->putJson(route('intranet.chat.messages.update', $sent['id']), ['body' => 'Bonjour à tous'])
        ->assertOk()->assertJson(['body' => 'Bonjour à tous', 'edited' => true]);

    $this->travel(16)->minutes();
    $this->actingAs($alice)->putJson(route('intranet.chat.messages.update', $sent['id']), ['body' => 'Trop tard'])->assertForbidden();
    $this->actingAs($alice)->deleteJson(route('intranet.chat.messages.destroy', $sent['id']))->assertForbidden();
    $this->travelBack();

    $this->actingAs($alice)->deleteJson(route('intranet.chat.messages.destroy', $sent['id']))
        ->assertOk()->assertJson(['deleted' => true, 'body' => '']);
    expect(ChatMessage::withTrashed()->find($sent['id'])->trashed())->toBeTrue();
});

it('lets department heads delete messages in their department room with an audit entry', function () {
    $finance = Department::factory()->create(['is_active' => true]);
    [, $roomOf] = chatRooms();
    $room = $roomOf($finance);
    $head = User::factory()->create();
    $head->departments()->attach($finance, ['role' => 'director']);
    $member = User::factory()->create();
    $member->departments()->attach($finance, ['role' => 'user']);
    $message = $room->messages()->create(['user_id' => $member->id, 'body' => 'Message à modérer']);

    $this->actingAs($member)->getJson(route('intranet.chat.poll', [$room, 'after' => 0, 'since' => now()->toIso8601String()]))
        ->assertJsonPath('messages.0.can_delete', true);

    $this->actingAs($head)->deleteJson(route('intranet.chat.messages.destroy', $message))->assertOk();
    expect($message->fresh()->trashed())->toBeTrue()
        ->and(AdminAuditLog::where('action', 'suppression message chat')->exists())->toBeTrue();
});

it('counts unread messages and clears them when the room is opened', function () {
    [$general] = chatRooms();
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $general->messages()->create(['user_id' => $alice->id, 'body' => 'Un']);
    $general->messages()->create(['user_id' => $alice->id, 'body' => 'Deux']);

    expect(ChatRoom::unreadCountsFor($bob))->toBe([$general->id => 2])
        ->and(ChatRoom::unreadCountsFor($alice))->toBe([]);

    $this->actingAs($bob)->get(route('intranet.dashboard'))->assertSee('message(s) non lu(s)');
    $this->actingAs($bob)->get(route('intranet.chat.show', $general))->assertOk();
    expect(ChatRoom::unreadCountsFor($bob))->toBe([]);
});

it('lets only administrators manage private rooms visible to their members', function () {
    chatRooms();
    $admin = User::factory()->superAdmin()->create();
    $member = User::factory()->create(['name' => 'Membre Privé']);
    $other = User::factory()->create();

    $this->actingAs($member)->get(route('intranet.chat.rooms.create'))->assertForbidden();

    $this->actingAs($admin)->post(route('intranet.chat.rooms.store'), [
        'name' => 'Comité de direction',
        'members' => [$member->id],
    ])->assertRedirect();
    $room = ChatRoom::where('type', ChatRoom::PRIVATE)->firstOrFail();
    expect($room->members()->pluck('users.id')->sort()->values()->all())->toBe(collect([$admin->id, $member->id])->sort()->values()->all());

    $this->actingAs($member)->get(route('intranet.chat.show', $room))->assertOk()->assertSee('Comité de direction');
    $this->actingAs($other)->get(route('intranet.chat.show', $room))->assertForbidden();
    $this->actingAs($member)->get(route('intranet.chat.rooms.edit', $room))->assertForbidden();

    $this->actingAs($admin)->put(route('intranet.chat.rooms.update', $room), ['name' => 'Comité', 'members' => [$admin->id, $other->id]])->assertRedirect();
    $this->actingAs($member)->get(route('intranet.chat.show', $room))->assertForbidden();
    $this->actingAs($other)->get(route('intranet.chat.show', $room))->assertOk();

    $this->actingAs($admin)->delete(route('intranet.chat.rooms.destroy', $room))->assertRedirect(route('intranet.chat.index'));
    expect(ChatRoom::find($room->id))->toBeNull();
});

it('purges chat messages older than six months', function () {
    [$general] = chatRooms();
    $user = User::factory()->create();
    $old = $general->messages()->create(['user_id' => $user->id, 'body' => 'Ancien']);
    $old->forceFill(['created_at' => now()->subMonths(7)])->save();
    $recent = $general->messages()->create(['user_id' => $user->id, 'body' => 'Récent']);

    $this->artisan('intranet:purge-chat')->assertSuccessful();

    expect(ChatMessage::withTrashed()->pluck('id')->all())->toBe([$recent->id]);
});
