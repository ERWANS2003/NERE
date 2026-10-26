<?php

// Non-régression des anomalies relevées lors de l'audit de la logique métier
// (tickets IT, congés, envoi des e-mails).

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TimeOffBalance;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Notifications\SubmissionReviewed;
use App\Notifications\TicketNotification;
use App\Notifications\TimeOffManagerReminder;
use App\Notifications\TimeOffReviewed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

function regressionItDepartment(): Department
{
    return Department::query()->where('code', 'IT')->first()
        ?? Department::factory()->create(['code' => 'IT', 'name' => 'Département IT', 'is_active' => true]);
}

function regressionTicket(Department $it, array $attributes = []): Ticket
{
    return Ticket::create([
        'number' => 'IT-'.random_int(100000, 999999),
        'department_id' => $it->id,
        'requester_id' => User::factory()->create()->id,
        'subject' => 'Incident',
        'description' => 'Description',
        'category' => 'network',
        'priority' => 'normal',
        'status' => 'new',
        ...$attributes,
    ]);
}

function regressionPolicy(array $attributes = []): TimeOffPolicy
{
    return TimeOffPolicy::create([
        'name' => 'Congé annuel', 'type' => 'vacation', 'annual_allocation' => 30,
        'carry_over_limit' => 0, 'requires_approval' => true, 'advance_notice_days' => 0, 'is_active' => true,
        ...$attributes,
    ]);
}

function regressionBalance(User $user, TimeOffPolicy $policy, float $days): TimeOffBalance
{
    return TimeOffBalance::create([
        'user_id' => $user->id, 'policy_id' => $policy->id, 'year' => now()->addWeek()->startOfWeek()->year,
        'total_days' => $days, 'used_days' => 0, 'remaining_days' => $days, 'carry_over_days' => 0,
    ]);
}

/** Lundi de la semaine située $weeks semaines plus tard (jour ouvré garanti). */
function regressionMonday(int $weeks = 1, int $plusDays = 0): string
{
    return now()->addWeeks($weeks)->startOfWeek()->addDays($plusDays)->toDateString();
}

// ── Tickets IT ─────────────────────────────────────────────────────────────

it('conserve la date de résolution quand un ticket résolu est fermé', function () {
    $it = regressionItDepartment();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
    $resolvedAt = now()->subHour()->startOfSecond();
    $ticket = regressionTicket($it, ['status' => 'resolved', 'resolved_at' => $resolvedAt]);

    $this->actingAs($technician)
        ->put(route('intranet.tickets.update', $ticket), ['status' => 'closed', 'priority' => 'normal'])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->resolved_at->equalTo($resolvedAt))->toBeTrue()
        ->and($ticket->closed_at)->not->toBeNull();
});

it('date la résolution d’un ticket fermé directement et l’efface s’il est rouvert', function () {
    $it = regressionItDepartment();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
    $ticket = regressionTicket($it);

    $this->actingAs($technician)
        ->put(route('intranet.tickets.update', $ticket), ['status' => 'closed', 'priority' => 'normal']);
    expect($ticket->fresh()->resolved_at)->not->toBeNull();

    $this->put(route('intranet.tickets.update', $ticket), ['status' => 'in_progress', 'priority' => 'normal']);
    expect($ticket->fresh()->resolved_at)->toBeNull()
        ->and($ticket->fresh()->closed_at)->toBeNull();
});

it('laisse un directeur IT enregistrer un ticket qu’il a pris en charge', function () {
    $it = regressionItDepartment();
    $director = User::factory()->withRole($it, DepartmentRole::Director)->create();
    $ticket = regressionTicket($it);

    $this->actingAs($director)->post(route('intranet.tickets.take', $ticket))->assertRedirect();

    $this->put(route('intranet.tickets.update', $ticket), [
        'status' => 'resolved', 'priority' => 'normal', 'assignee_id' => $director->id,
    ])->assertSessionHasNoErrors();

    expect($ticket->fresh()->status->value)->toBe('resolved')
        ->and($ticket->fresh()->assignee_id)->toBe($director->id);
});

it('n’accepte comme assigné qu’un membre de l’équipe IT', function () {
    $it = regressionItDepartment();
    $director = User::factory()->withRole($it, DepartmentRole::Director)->create();
    $outsider = User::factory()->create();
    $ticket = regressionTicket($it);

    $this->actingAs($director)
        ->put(route('intranet.tickets.update', $ticket), [
            'status' => 'in_progress', 'priority' => 'normal', 'assignee_id' => $outsider->id,
        ])
        ->assertSessionHasErrors('assignee_id');
});

// ── Congés ─────────────────────────────────────────────────────────────────

it('décompte le solde d’un congé approuvé automatiquement', function () {
    $user = User::factory()->create();
    $policy = regressionPolicy(['requires_approval' => false]);
    $balance = regressionBalance($user, $policy, 10);

    $this->actingAs($user)->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id, 'start_date' => regressionMonday(), 'end_date' => regressionMonday(),
    ])->assertSessionHasNoErrors();

    $request = TimeOffRequest::where('user_id', $user->id)->sole();
    expect($request->status)->toBe('approved')
        ->and($request->approved_at)->not->toBeNull()
        ->and((float) $balance->fresh()->remaining_days)->toBe(9.0)
        ->and((float) $balance->fresh()->used_days)->toBe(1.0);
});

it('refuse deux demandes de congé qui se chevauchent', function () {
    $user = User::factory()->create();
    $policy = regressionPolicy();
    regressionBalance($user, $policy, 20);

    $this->actingAs($user)->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id, 'start_date' => regressionMonday(), 'end_date' => regressionMonday(1, 2),
    ])->assertSessionHasNoErrors();

    $this->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id, 'start_date' => regressionMonday(1, 2), 'end_date' => regressionMonday(1, 3),
    ])->assertSessionHasErrors('start_date');

    expect(TimeOffRequest::where('user_id', $user->id)->count())->toBe(1);
});

it('autorise une nouvelle demande sur des dates libérées par un refus', function () {
    $user = User::factory()->create();
    $policy = regressionPolicy();
    regressionBalance($user, $policy, 20);
    TimeOffRequest::create([
        'user_id' => $user->id, 'policy_id' => $policy->id,
        'start_date' => regressionMonday(), 'end_date' => regressionMonday(), 'total_days' => 1, 'status' => 'denied',
    ]);

    $this->actingAs($user)->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id, 'start_date' => regressionMonday(), 'end_date' => regressionMonday(),
    ])->assertSessionHasNoErrors();
});

it('tient compte des demandes en attente dans le solde disponible', function () {
    $user = User::factory()->create();
    $policy = regressionPolicy();
    regressionBalance($user, $policy, 3);

    $this->actingAs($user)->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id, 'start_date' => regressionMonday(1), 'end_date' => regressionMonday(1, 1),
    ])->assertSessionHasNoErrors();

    $this->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id, 'start_date' => regressionMonday(2), 'end_date' => regressionMonday(2, 1),
    ])->assertSessionHasErrors('end_date');
});

it('refuse une approbation qui rendrait le solde négatif', function () {
    $manager = User::factory()->create();
    $user = User::factory()->create(['manager_id' => $manager->id]);
    $policy = regressionPolicy();
    $balance = regressionBalance($user, $policy, 3);
    // Demandes créées hors formulaire (import, solde modifié entre-temps…).
    $requests = collect([1, 2])->map(fn (int $week) => TimeOffRequest::create([
        'user_id' => $user->id, 'policy_id' => $policy->id,
        'start_date' => regressionMonday($week), 'end_date' => regressionMonday($week, 1),
        'total_days' => 2, 'status' => 'pending',
    ]));

    $this->actingAs($manager)
        ->put(route('intranet.time-off.approvals.update', $requests[0]), ['decision' => 'approve'])
        ->assertRedirect(route('intranet.time-off.approvals'));
    $this->put(route('intranet.time-off.approvals.update', $requests[1]), ['decision' => 'approve'])
        ->assertSessionHas('error');

    expect((float) $balance->fresh()->remaining_days)->toBe(1.0)
        ->and($requests[0]->fresh()->status)->toBe('approved')
        ->and($requests[1]->fresh()->status)->toBe('pending');
});

it('ne traite pas deux fois une demande déjà décidée', function () {
    $manager = User::factory()->create();
    $user = User::factory()->create(['manager_id' => $manager->id]);
    $policy = regressionPolicy();
    $balance = regressionBalance($user, $policy, 10);
    $request = TimeOffRequest::create([
        'user_id' => $user->id, 'policy_id' => $policy->id,
        'start_date' => regressionMonday(), 'end_date' => regressionMonday(), 'total_days' => 1, 'status' => 'pending',
    ]);

    $this->actingAs($manager)->put(route('intranet.time-off.approvals.update', $request), ['decision' => 'approve']);
    $this->put(route('intranet.time-off.approvals.update', $request), ['decision' => 'approve'])->assertStatus(409);

    expect((float) $balance->fresh()->remaining_days)->toBe(9.0);
});

it('refuse une politique de congé désactivée', function () {
    $user = User::factory()->create();
    $policy = regressionPolicy(['is_active' => false]);

    $this->actingAs($user)->post(route('intranet.time-off.store'), [
        'policy_id' => $policy->id, 'start_date' => regressionMonday(), 'end_date' => regressionMonday(),
    ])->assertSessionHasErrors('policy_id');

    expect(TimeOffRequest::count())->toBe(0);
});

// ── E-mails ────────────────────────────────────────────────────────────────

it('met les e-mails en file d’attente et garde la notification intranet immédiate', function (string $notification) {
    $reflection = new ReflectionClass($notification);
    $instance = $reflection->newInstanceWithoutConstructor();

    expect($reflection->implementsInterface(ShouldQueue::class))->toBeTrue()
        ->and($instance->viaConnections())->toBe(['database' => 'sync']);
})->with([
    AnnouncementPublished::class,
    SubmissionReviewed::class,
    TicketNotification::class,
    TimeOffManagerReminder::class,
    TimeOffReviewed::class,
]);

it('crée un ticket même si le serveur de messagerie est injoignable', function () {
    config([
        'queue.default' => 'database',
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'smtp.invalid',
        'mail.mailers.smtp.port' => 2,
    ]);
    $it = regressionItDepartment();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
    $requester = User::factory()->create();

    $this->actingAs($requester)
        ->post(route('intranet.tickets.store'), [
            'subject' => 'Imprimante en panne',
            'description' => 'Plus rien ne sort.',
            'category' => 'hardware',
            'priority' => 'normal',
        ])
        ->assertRedirect();

    expect(Ticket::where('subject', 'Imprimante en panne')->exists())->toBeTrue()
        ->and($technician->fresh()->notifications()->count())->toBe(1)
        ->and(DB::table('jobs')->count())->toBeGreaterThan(0);
});
