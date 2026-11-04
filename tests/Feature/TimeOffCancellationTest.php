<?php

use App\Models\TimeOffBalance;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;

function congeAnnulable(User $user, string $status, int $startInDays, float $days = 3): TimeOffRequest
{
    $policy = TimeOffPolicy::firstOrCreate(['type' => 'vacation'], [
        'name' => 'Congé annuel',
        'annual_allocation' => 30,
        'carry_over_limit' => 5,
        'requires_approval' => true,
        'advance_notice_days' => 0,
        'is_active' => true,
    ]);

    return TimeOffRequest::create([
        'user_id' => $user->id,
        'policy_id' => $policy->id,
        'start_date' => now()->addDays($startInDays)->toDateString(),
        'end_date' => now()->addDays($startInDays + 2)->toDateString(),
        'total_days' => $days,
        'status' => $status,
    ]);
}

it('lets an employee cancel a pending request', function () {
    $user = User::factory()->create();
    $request = congeAnnulable($user, 'pending', 10);

    $this->actingAs($user)->get(route('intranet.time-off.index'))->assertSee(route('intranet.time-off.cancel', $request));
    $this->actingAs($user)->post(route('intranet.time-off.cancel', $request))->assertRedirect(route('intranet.time-off.index'));

    expect($request->fresh()->status)->toBe('cancelled');
});

it('gives the days back when an approved leave that has not started is cancelled', function () {
    $user = User::factory()->create();
    $request = congeAnnulable($user, 'approved', 10);
    $balance = TimeOffBalance::create([
        'user_id' => $user->id, 'policy_id' => $request->policy_id, 'year' => $request->start_date->year,
        'total_days' => 30, 'used_days' => 3, 'remaining_days' => 27, 'carry_over_days' => 0,
    ]);

    $this->actingAs($user)->post(route('intranet.time-off.cancel', $request))->assertSessionHas('success');

    expect($request->fresh()->status)->toBe('cancelled')
        ->and((float) $balance->fresh()->remaining_days)->toBe(30.0)
        ->and((float) $balance->fresh()->used_days)->toBe(0.0);
});

it('refuses to cancel a leave that has started or another employee request', function () {
    $user = User::factory()->create();
    $started = congeAnnulable($user, 'approved', -1);
    $other = congeAnnulable(User::factory()->create(), 'pending', 10);

    $this->actingAs($user)->post(route('intranet.time-off.cancel', $started))->assertSessionHas('error');
    $this->actingAs($user)->post(route('intranet.time-off.cancel', $other))->assertForbidden();

    expect($started->fresh()->status)->toBe('approved')
        ->and($other->fresh()->status)->toBe('pending');
});

it('does not count public holidays in a leave request', function () {
    $this->travelTo(now()->parse('2026-12-01 08:00'));
    $user = User::factory()->create();
    $policy = TimeOffPolicy::firstOrCreate(['type' => 'vacation'], [
        'name' => 'Congé annuel', 'annual_allocation' => 30, 'carry_over_limit' => 5,
        'requires_approval' => true, 'advance_notice_days' => 0, 'is_active' => true,
    ]);

    // Du lundi 7 au vendredi 11 décembre 2026 : le 11 (Proclamation de l'Indépendance) est férié.
    $this->actingAs($user)->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id,
        'start_date' => '2026-12-07',
        'end_date' => '2026-12-11',
    ])->assertSessionHasNoErrors();

    expect((float) TimeOffRequest::firstOrFail()->total_days)->toBe(4.0);
});

it('notifies the approvers of a new leave request', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $department = \App\Models\Department::factory()->create();
    $employee = User::factory()->create();
    $employee->departments()->attach($department, ['role' => 'user']);
    $head = User::factory()->create();
    $head->departments()->attach($department, ['role' => 'director']);
    $colleague = User::factory()->create();
    $colleague->departments()->attach($department, ['role' => 'user']);
    $policy = TimeOffPolicy::firstOrCreate(['type' => 'vacation'], [
        'name' => 'Congé annuel', 'annual_allocation' => 30, 'carry_over_limit' => 5,
        'requires_approval' => true, 'advance_notice_days' => 0, 'is_active' => true,
    ]);

    $this->actingAs($employee)->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id,
        'start_date' => now()->next('Monday')->toDateString(),
        'end_date' => now()->next('Monday')->addDay()->toDateString(),
    ])->assertSessionHasNoErrors();

    \Illuminate\Support\Facades\Notification::assertSentTo($head, \App\Notifications\NewRequestToProcess::class);
    \Illuminate\Support\Facades\Notification::assertNotSentTo($colleague, \App\Notifications\NewRequestToProcess::class);
    \Illuminate\Support\Facades\Notification::assertNotSentTo($employee, \App\Notifications\NewRequestToProcess::class);
});
