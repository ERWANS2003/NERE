<?php

use App\Jobs\SendWebPushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

function pushSubscriptionPayload(string $endpoint = 'https://fcm.googleapis.com/fcm/send/test'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => [
            'p256dh' => str_repeat('a', 65),
            'auth' => str_repeat('b', 22),
        ],
    ];
}

beforeEach(function () {
    config(['webpush.enabled' => true]);
});

it('stores push subscription keys encrypted and updates the current endpoint', function () {
    $user = User::factory()->create();
    $payload = pushSubscriptionPayload();

    $this->actingAs($user)
        ->postJson(route('intranet.notifications.push.subscriptions.store'), $payload)
        ->assertCreated()
        ->assertJson(['subscribed' => true]);

    expect($user->pushSubscriptions()->count())->toBe(1)
        ->and(DB::table('push_subscriptions')->value('endpoint'))->not->toBe($payload['endpoint'])
        ->and($user->pushSubscriptions()->first()->endpoint)->toBe($payload['endpoint']);

    $payload['keys']['auth'] = str_repeat('c', 22);
    $this->postJson(route('intranet.notifications.push.subscriptions.store'), $payload)
        ->assertCreated();

    expect($user->pushSubscriptions()->count())->toBe(1)
        ->and($user->pushSubscriptions()->first()->auth_token)->toBe(str_repeat('c', 22));
});

it('rejects endpoints outside the configured push providers', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(
            route('intranet.notifications.push.subscriptions.store'),
            pushSubscriptionPayload('https://attacker.example/collect')
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');

    expect(PushSubscription::count())->toBe(0);
});

it('allows a user to remove only their own push subscription', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $endpoint = pushSubscriptionPayload()['endpoint'];

    $ownSubscription = PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => $endpoint,
        'endpoint_hash' => hash('sha256', $endpoint),
        'public_key' => str_repeat('a', 65),
        'auth_token' => str_repeat('b', 22),
    ]);
    $otherEndpoint = 'https://fcm.googleapis.com/fcm/send/other';
    $otherSubscription = PushSubscription::create([
        'user_id' => $otherUser->id,
        'endpoint' => $otherEndpoint,
        'endpoint_hash' => hash('sha256', $otherEndpoint),
        'public_key' => str_repeat('a', 65),
        'auth_token' => str_repeat('b', 22),
    ]);

    $this->actingAs($user)
        ->deleteJson(route('intranet.notifications.push.subscriptions.destroy'), ['endpoint' => $endpoint])
        ->assertNoContent();

    expect(PushSubscription::find($ownSubscription->id))->toBeNull()
        ->and(PushSubscription::find($otherSubscription->id))->not->toBeNull();
});

it('queues browser push after saving an in-app notification', function () {
    Queue::fake();
    $user = User::factory()->create();
    $endpoint = pushSubscriptionPayload()['endpoint'];

    PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => $endpoint,
        'endpoint_hash' => hash('sha256', $endpoint),
        'public_key' => str_repeat('a', 65),
        'auth_token' => str_repeat('b', 22),
    ]);

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toArray(object $notifiable): array
        {
            return [
                'title' => 'Alerte de test',
                'message' => 'Une demande nécessite votre attention.',
                'url' => route('intranet.notifications.index'),
            ];
        }
    };

    $user->notify($notification);

    Queue::assertPushed(SendWebPushNotification::class, fn (SendWebPushNotification $job): bool => $job->subscriptionId > 0
        && $job->payload['title'] === 'Alerte de test'
        && $job->payload['url'] === '/intranet/notifications'
        && $job->payload['urgent'] === false);
});

it('marks server health alerts as urgent push notifications', function () {
    Queue::fake();
    $admin = User::factory()->superAdmin()->create();
    $endpoint = pushSubscriptionPayload()['endpoint'];
    PushSubscription::create([
        'user_id' => $admin->id,
        'endpoint' => $endpoint,
        'endpoint_hash' => hash('sha256', $endpoint),
        'public_key' => str_repeat('a', 65),
        'auth_token' => str_repeat('b', 22),
    ]);

    $admin->notify(new \App\Notifications\SystemHealthAlert(['backup' => 'Aucune sauvegarde.']));

    Queue::assertPushed(SendWebPushNotification::class, fn (SendWebPushNotification $job): bool => $job->payload['urgent'] === true
        && $job->payload['url'] === '/admin/sante');
});

it('invites users to enable Windows notifications when push is configured', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('intranet.dashboard'))->assertOk()->assertSee('data-push-banner', false);
    $this->actingAs($user)->get(route('intranet.notifications.index'))->assertOk()->assertDontSee('data-push-banner', false);

    config(['webpush.enabled' => false]);
    $this->actingAs($user)->get(route('intranet.dashboard'))->assertOk()->assertDontSee('data-push-banner', false);
});
