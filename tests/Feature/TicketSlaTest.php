<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketSlaNotification;
use App\Services\TicketSlaCalculator;
use App\Services\TicketSlaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('adds SLA time across weekdays, Saturday shifts, and closed hours', function (
    string $start,
    int $minutes,
    string $expected,
) {
    $result = app(TicketSlaCalculator::class)->addBusinessMinutes(
        CarbonImmutable::parse($start, 'Africa/Ouagadougou'),
        $minutes,
    );

    expect($result->toDateTimeString())->toBe($expected);
})->with([
    'weekday close' => ['2026-10-26 16:30:00', 60, '2026-10-27 08:30:00'],
    'Friday to Saturday' => ['2026-10-30 16:30:00', 60, '2026-10-31 08:30:00'],
    'Saturday to Monday' => ['2026-10-31 13:30:00', 60, '2026-11-02 08:30:00'],
    'Sunday to Monday' => ['2026-11-01 08:00:00', 60, '2026-11-02 09:00:00'],
]);

it('notifies the IT team once at 75 percent and once when the response SLA is breached', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00:00', 'Africa/Ouagadougou'));

    $department = Department::factory()->create(['code' => 'IT', 'is_active' => true]);
    $technician = User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $ticket = Ticket::create([
        'number' => 'IT-000201',
        'department_id' => $department->id,
        'requester_id' => User::factory()->create()->id,
        'subject' => 'Serveur indisponible',
        'description' => 'Le service ne répond plus.',
        'category' => 'network',
        'priority' => 'urgent',
        'status' => 'new',
        'created_at' => now(),
    ]);

    app(TicketSlaService::class)->setDeadlines($ticket);
    expect($ticket->fresh()->first_response_due_at->toDateTimeString())->toBe('2026-10-26 08:15:00')
        ->and($ticket->fresh()->response_warning_at->toDateTimeString())->toBe('2026-10-26 08:12:00');

    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:12:00', 'Africa/Ouagadougou'));
    expect(app(TicketSlaService::class)->checkDueAlerts())->toBe(1);
    Notification::assertSentTo($technician, TicketSlaNotification::class, fn ($notification) => $notification
        ->toArray($technician)['sla_stage'] === 'warning');

    expect(app(TicketSlaService::class)->checkDueAlerts())->toBe(0);

    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:15:00', 'Africa/Ouagadougou'));
    expect(app(TicketSlaService::class)->checkDueAlerts())->toBe(1);
    Notification::assertSentTo($technician, TicketSlaNotification::class, fn ($notification) => $notification
        ->toArray($technician)['sla_stage'] === 'breached');
});

it('stops the first-response SLA when an IT technician posts a public comment', function () {
    Notification::fake();
    $department = Department::factory()->create(['code' => 'IT', 'is_active' => true]);
    $requester = User::factory()->create();
    $technician = User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $ticket = Ticket::create([
        'number' => 'IT-000202',
        'department_id' => $department->id,
        'requester_id' => $requester->id,
        'subject' => 'Accès impossible',
        'description' => 'Connexion refusée.',
        'category' => 'access',
        'priority' => 'normal',
        'status' => 'new',
    ]);

    $this->actingAs($technician)
        ->post(route('intranet.tickets.comments.store', $ticket), [
            'body' => 'Note interne de diagnostic.',
            'is_internal' => true,
        ])
        ->assertRedirect();
    expect($ticket->fresh()->first_responded_at)->toBeNull();

    $this->post(route('intranet.tickets.comments.store', $ticket), [
        'body' => 'Nous analysons votre demande.',
    ])->assertRedirect();

    expect($ticket->fresh()->first_responded_at)->not->toBeNull();
});

it('restarts the resolution SLA when a resolved ticket is reopened', function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-10-26 10:00:00', 'Africa/Ouagadougou'));
    $department = Department::factory()->create(['code' => 'IT', 'is_active' => true]);
    $technician = User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $ticket = Ticket::create([
        'number' => 'IT-000203',
        'department_id' => $department->id,
        'requester_id' => User::factory()->create()->id,
        'subject' => 'Demande réouverte',
        'description' => 'Le problème est revenu.',
        'category' => 'software',
        'priority' => 'normal',
        'status' => 'resolved',
        'resolved_at' => now()->subHour(),
        'created_at' => CarbonImmutable::parse('2026-10-26 08:00:00', 'Africa/Ouagadougou'),
    ]);
    $ticket->forceFill([
        'created_at' => CarbonImmutable::parse('2026-10-26 08:00:00', 'Africa/Ouagadougou'),
    ])->saveQuietly();

    $sla = app(TicketSlaService::class);
    $sla->setDeadlines($ticket);
    $previousDeadline = $ticket->fresh()->resolution_due_at;

    $this->actingAs($technician)
        ->put(route('intranet.tickets.update', $ticket), [
            'status' => 'in_progress',
            'priority' => 'normal',
        ])
        ->assertRedirect(route('intranet.tickets.show', $ticket));

    expect($ticket->fresh()->resolved_at)
        ->toBeNull()
        ->and($ticket->fresh()->resolution_due_at->greaterThan($previousDeadline))
        ->toBeTrue()
        ->and($ticket->fresh()->resolution_warning_sent_at)
        ->toBeNull();
});

it('backfills deadlines for open tickets created before SLA tracking was enabled', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 08:01:00', 'Africa/Ouagadougou'));
    $department = Department::factory()->create(['code' => 'IT', 'is_active' => true]);
    User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $ticket = Ticket::create([
        'number' => 'IT-000204',
        'department_id' => $department->id,
        'subject' => 'Ticket historique',
        'description' => 'Créé avant le déploiement des SLA.',
        'category' => 'hardware',
        'priority' => 'high',
        'status' => 'new',
        'created_at' => CarbonImmutable::parse('2026-10-26 08:00:00', 'Africa/Ouagadougou'),
    ]);
    $ticket->forceFill([
        'created_at' => CarbonImmutable::parse('2026-10-26 08:00:00', 'Africa/Ouagadougou'),
    ])->saveQuietly();

    expect(app(TicketSlaService::class)->checkDueAlerts())->toBe(0)
        ->and($ticket->fresh()->first_response_due_at->toDateTimeString())
        ->toBe('2026-10-26 09:00:00')
        ->and($ticket->fresh()->resolution_due_at->toDateTimeString())
        ->toBe('2026-10-26 16:00:00');
});
