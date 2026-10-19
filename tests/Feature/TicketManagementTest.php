<?php

use App\Enums\DepartmentRole;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\DepartmentService;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function departementIt(): Department
{
    return Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);
}

it('shows ticket support as a service on the IT department page', function () {
    $it = departementIt();
    DepartmentService::create([
        'department_id' => $it->id,
        'name' => 'Support IT',
        'description' => 'Signaler un incident et suivre vos demandes.',
        'link_type' => 'page',
        'link_url' => '/intranet/tickets',
        'order' => 1,
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('intranet.departments.show', 'IT'))
        ->assertOk()
        ->assertSee('Support IT')
        ->assertSee('href="/intranet/tickets"', false)
        ->assertDontSee('Mes tickets / file IT');
});

it('lets an active user create an IT ticket and notifies the IT team', function () {
    Notification::fake();
    $it = departementIt();
    $requester = User::factory()->create();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();

    $response = $this->actingAs($requester)->post(route('intranet.tickets.store'), [
        'subject' => 'Ordinateur en panne',
        'description' => 'Le poste ne démarre plus.',
        'category' => 'hardware',
        'priority' => 'high',
    ]);

    $ticket = Ticket::firstOrFail();
    $response->assertRedirect(route('intranet.tickets.show', $ticket));
    expect($ticket->number)->toBe('IT-000001')
        ->and($ticket->status)->toBe(TicketStatus::New)
        ->and($ticket->requester_id)->toBe($requester->id);

    Notification::assertSentTo($technician, TicketNotification::class);
});

it('restricts requesters to their tickets and hides internal comments', function () {
    $it = departementIt();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
    $ticket = Ticket::create([
        'number' => 'IT-000101',
        'department_id' => $it->id,
        'requester_id' => $owner->id,
        'subject' => 'Accès à la messagerie',
        'description' => 'Impossible de me connecter.',
        'category' => 'email',
        'priority' => 'normal',
        'status' => 'new',
    ]);
    $ticket->comments()->create([
        'user_id' => $technician->id,
        'body' => 'Information réservée à l’équipe IT.',
        'is_internal' => true,
    ]);

    $this->actingAs($other)
        ->get(route('intranet.tickets.show', $ticket))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('intranet.tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee('Information réservée à l’équipe IT.');
});

it('allows IT technicians to update a ticket and records its status change', function () {
    Notification::fake();
    $it = departementIt();
    $requester = User::factory()->create();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 2)->create();
    $ticket = Ticket::create([
        'number' => 'IT-000102',
        'department_id' => $it->id,
        'requester_id' => $requester->id,
        'subject' => 'VPN indisponible',
        'description' => 'La connexion VPN échoue.',
        'category' => 'network',
        'priority' => 'normal',
        'status' => 'new',
    ]);

    $this->actingAs($technician)
        ->put(route('intranet.tickets.update', $ticket), [
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $technician->id,
        ])
        ->assertRedirect(route('intranet.tickets.show', $ticket));

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->fresh()->priority->value)->toBe('high')
        ->and($ticket->fresh()->assignee_id)->toBe($technician->id)
        ->and($ticket->activities()->where('event', 'updated')->exists())->toBeTrue();

    Notification::assertSentTo($requester, TicketNotification::class);
});

it('prevents non-IT users from updating tickets or adding internal comments', function () {
    $it = departementIt();
    $requester = User::factory()->withRole($it, DepartmentRole::User)->create();
    $ticket = Ticket::create([
        'number' => 'IT-000103',
        'department_id' => $it->id,
        'requester_id' => $requester->id,
        'subject' => 'Demande de logiciel',
        'description' => 'Besoin d’un outil métier.',
        'category' => 'software',
        'priority' => 'normal',
        'status' => 'new',
    ]);

    $this->actingAs($requester)
        ->put(route('intranet.tickets.update', $ticket), [
            'status' => 'resolved',
            'priority' => 'normal',
            'assignee_id' => '',
        ])
        ->assertForbidden();

    $this->actingAs($requester)
        ->post(route('intranet.tickets.comments.store', $ticket), [
            'body' => 'Commentaire du demandeur.',
            'is_internal' => '1',
        ])
        ->assertRedirect(route('intranet.tickets.show', $ticket));

    expect($ticket->comments()->firstOrFail()->is_internal)->toBeFalse();
});

it('stores ticket attachments privately and enforces ticket access on downloads', function () {
    Notification::fake();
    Storage::fake('local');
    $it = departementIt();
    $requester = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($requester)->post(route('intranet.tickets.store'), [
        'subject' => 'Capture écran',
        'description' => 'Voir le fichier joint.',
        'category' => 'other',
        'priority' => 'low',
        'attachments' => [UploadedFile::fake()->image('capture.png')],
    ])->assertRedirect();

    $attachment = TicketAttachment::firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);

    $this->actingAs($other)
        ->get(route('intranet.tickets.attachments.download', $attachment))
        ->assertForbidden();
});
