<?php

use App\Models\User;
use App\Notifications\SystemHealthAlert;

function notifyTimes(User $user, int $times): void
{
    foreach (range(1, $times) as $i) {
        $user->notifyNow(new SystemHealthAlert(['disk' => "Alerte de test {$i}"]), ['database']);
    }
}

it('deletes one of my notifications and returns the remaining counts', function () {
    $user = User::factory()->create();
    notifyTimes($user, 3);
    $first = $user->notifications()->first();
    $first->markAsRead();

    $this->actingAs($user)
        ->deleteJson(route('intranet.notifications.destroy', $first->id))
        ->assertOk()
        ->assertJson(['unread' => 2, 'total' => 2]);

    expect($user->notifications()->count())->toBe(2);
});

it('deletes all my notifications at once without touching other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    notifyTimes($user, 4);
    notifyTimes($other, 2);

    $this->actingAs($user)
        ->deleteJson(route('intranet.notifications.destroy-all'))
        ->assertOk()
        ->assertJson(['unread' => 0, 'total' => 0]);

    expect($user->notifications()->count())->toBe(0)
        ->and($other->notifications()->count())->toBe(2);
});

it('refuses to delete a notification that belongs to someone else', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    notifyTimes($other, 1);

    $this->actingAs($user)
        ->deleteJson(route('intranet.notifications.destroy', $other->notifications()->first()->id))
        ->assertNotFound();

    expect($other->notifications()->count())->toBe(1);
});

it('falls back to a redirect without JavaScript and shows the delete controls', function () {
    $user = User::factory()->create();
    notifyTimes($user, 2);

    $this->actingAs($user)->get(route('intranet.notifications.index'))
        ->assertOk()
        ->assertSee('Tout supprimer')
        ->assertSee('data-notification=', false)
        ->assertSee('Supprimer la notification', false);

    $this->delete(route('intranet.notifications.destroy-all'))
        ->assertRedirect(route('intranet.notifications.index'))
        ->assertSessionHas('success', 'Toutes vos notifications ont été supprimées.');
});
