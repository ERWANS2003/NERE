<?php

use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Notifications\TimeOffManagerReminder;
use App\Services\TimeOffReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function createPendingTimeOffRequest(?int $managerId = null): TimeOffRequest
{
    $user = User::factory()->create(['manager_id' => $managerId]);
    $policy = TimeOffPolicy::create([
        'name' => 'Congé annuel',
        'type' => 'vacation',
        'annual_allocation' => 30,
        'carry_over_limit' => 5,
        'requires_approval' => true,
        'advance_notice_days' => 7,
        'is_active' => true,
    ]);

    $request = TimeOffRequest::create([
        'user_id' => $user->id,
        'policy_id' => $policy->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-06',
        'total_days' => 5,
        'status' => 'pending',
    ]);
    $request->forceFill([
        'created_at' => CarbonImmutable::parse('2026-10-21 08:00:00'),
    ])->saveQuietly();

    return $request;
}

it('sends one reminder to the manager after a request has been pending for 48 hours', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-10-23 07:59:00'));
    $manager = User::factory()->create();
    $request = createPendingTimeOffRequest($manager->id);
    $service = app(TimeOffReminderService::class);

    expect($service->sendDueReminders())->toBe(['sent' => 0, 'unroutable' => 0]);

    $this->travelTo(CarbonImmutable::parse('2026-10-23 08:00:00'));
    expect($service->sendDueReminders())->toBe(['sent' => 1, 'unroutable' => 0]);
    Notification::assertSentTo($manager, TimeOffManagerReminder::class);
    expect($request->fresh()->manager_reminder_sent_at)->not->toBeNull();

    expect($service->sendDueReminders())->toBe(['sent' => 0, 'unroutable' => 0]);
    Notification::assertSentToTimes($manager, TimeOffManagerReminder::class, 1);
});

it('routes the reminder to active administrators when no active manager is assigned', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-10-23 08:00:00'));
    $admin = User::factory()->superAdmin()->create();
    createPendingTimeOffRequest();

    expect(app(TimeOffReminderService::class)->sendDueReminders())
        ->toBe(['sent' => 1, 'unroutable' => 0]);

    Notification::assertSentTo($admin, TimeOffManagerReminder::class);
});

it('does not remind on requests that are no longer pending', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-10-23 08:00:00'));
    $manager = User::factory()->create();
    $request = createPendingTimeOffRequest($manager->id);
    $request->update(['status' => 'approved']);

    expect(app(TimeOffReminderService::class)->sendDueReminders())
        ->toBe(['sent' => 0, 'unroutable' => 0]);

    Notification::assertNothingSent();
    expect($request->fresh()->manager_reminder_sent_at)->toBeNull();
});
