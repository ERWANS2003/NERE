<?php

use App\Enums\DepartmentRole;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\DepartmentService;
use App\Models\FormSubmission;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Notifications\TicketNotification;
use Database\Seeders\DepartmentServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
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

it('links every IT service to ticket creation with its matching category', function () {
    $it = departementIt();
    app(DepartmentServiceSeeder::class)->run();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('intranet.departments.show', 'IT'))
        ->assertOk()
        ->assertSee('href="/intranet/tickets"', false)
        ->assertSee('href="/intranet/tickets/create?category=hardware"', false)
        ->assertSee('href="/intranet/tickets/create?category=access"', false)
        ->assertSee('href="/intranet/tickets/create?category=network"', false)
        ->assertSee('href="/intranet/tickets/create?category=backup"', false)
        ->assertSee('href="/intranet/tickets/create?category=security"', false);

    $this->get(route('intranet.tickets.create', ['category' => 'backup']))
        ->assertOk()
        ->assertSee('<option value="backup" selected>', false)
        ->assertSee('Sauvegarde et restauration');
});

it('updates existing IT service links when the ticket service migration runs', function () {
    $it = departementIt();
    DepartmentService::create([
        'department_id' => $it->id,
        'name' => 'Sauvegarde et restauration',
        'description' => 'Restaurer des fichiers.',
        'link_type' => 'form',
        'link_url' => null,
        'order' => 5,
        'is_active' => true,
    ]);

    $migration = require database_path('migrations/2026_10_19_140000_link_it_services_to_ticket_categories.php');
    $migration->up();

    expect(DepartmentService::where('department_id', $it->id)
        ->where('name', 'Sauvegarde et restauration')
        ->firstOrFail()
        ->only(['link_type', 'link_url']))
        ->toBe([
            'link_type' => 'page',
            'link_url' => '/intranet/tickets/create?category=backup',
        ]);
});

it('recovers the ticket migration when the main table already exists', function () {
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('it_support_ticket_activities');
    Schema::dropIfExists('it_support_ticket_attachments');
    Schema::dropIfExists('it_support_ticket_comments');
    Schema::dropIfExists('it_support_tickets');
    Schema::create('tickets', function ($table) {
        $table->id();
        $table->string('reference');
        $table->string('title');
    });
    DB::table('tickets')->insert([
        'reference' => 'LEGACY-001',
        'title' => 'Historique ITSM existant',
    ]);

    $migration = require database_path('migrations/2026_10_19_110000_create_tickets_table.php');
    $migration->up();

    expect(Schema::hasTable('it_support_tickets'))
        ->toBeTrue()
        ->and(DB::table('tickets')->where('reference', 'LEGACY-001')->value('title'))
        ->toBe('Historique ITSM existant')
        ->and(Schema::hasTable('it_support_ticket_comments'))
        ->toBeTrue()
        ->and(Schema::hasTable('it_support_ticket_attachments'))
        ->toBeTrue()
        ->and(Schema::hasTable('it_support_ticket_activities'))
        ->toBeTrue()
        ->and(Schema::hasTable('notifications'))
        ->toBeTrue();
});

it('moves only this module ticket table and preserves the legacy tickets table', function () {
    $it = departementIt();
    Schema::dropIfExists('it_support_ticket_activities');
    Schema::dropIfExists('it_support_ticket_attachments');
    Schema::dropIfExists('it_support_ticket_comments');
    Schema::dropIfExists('ticket_activities');
    Schema::dropIfExists('ticket_attachments');
    Schema::dropIfExists('ticket_comments');
    Schema::dropIfExists('it_support_tickets');
    Schema::create('tickets', function ($table) {
        $table->id();
        $table->string('number')->unique();
        $table->foreignId('department_id')->constrained()->restrictOnDelete();
        $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
        $table->string('subject', 180);
        $table->text('description');
        $table->string('category', 40);
        $table->string('priority', 20)->default('normal');
        $table->string('status', 30)->default('new');
        $table->timestamp('resolved_at')->nullable();
        $table->timestamp('closed_at')->nullable();
        $table->timestamps();
    });
    DB::table('tickets')->insert([
        'number' => 'IT-009999',
        'department_id' => $it->id,
        'subject' => 'Nouveau support existant',
        'description' => 'À conserver lors du renommage.',
        'category' => 'hardware',
        'priority' => 'normal',
        'status' => 'new',
    ]);
    Schema::create('ticket_comments', function ($table) {
        $table->id();
        $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->text('body');
        $table->boolean('is_internal')->default(false);
        $table->timestamps();
    });
    DB::table('ticket_comments')->insert([
        'ticket_id' => 1,
        'body' => 'Commentaire à conserver.',
        'is_internal' => false,
    ]);

    $migration = require database_path('migrations/2026_10_19_130000_move_it_support_tickets_to_dedicated_table.php');
    $migration->up();

    expect(Schema::hasTable('tickets'))
        ->toBeFalse()
        ->and(Ticket::where('number', 'IT-009999')->exists())
        ->toBeTrue()
        ->and(Schema::hasTable('it_support_ticket_comments'))
        ->toBeTrue()
        ->and(DB::table('it_support_ticket_comments')->where('body', 'Commentaire à conserver.')->exists())
        ->toBeTrue();
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

it('allows IT technicians to update ticket status without changing its assignment', function () {
    Notification::fake();
    $it = departementIt();
    $requester = User::factory()->create();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 2)->create();
    $otherTechnician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
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
            'assignee_id' => $otherTechnician->id,
        ])
        ->assertRedirect(route('intranet.tickets.show', $ticket));

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->fresh()->priority->value)->toBe('high')
        ->and($ticket->fresh()->assignee_id)->toBeNull()
        ->and($ticket->activities()->where('event', 'updated')->exists())->toBeTrue();

    Notification::assertSentTo($requester, TicketNotification::class);
});

it('lets the IT director take a ticket or assign it to an active technician', function () {
    Notification::fake();
    $it = departementIt();
    $director = User::factory()->withRole($it, DepartmentRole::Director)->create();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
    $requester = User::factory()->create();
    $ticket = Ticket::create([
        'number' => 'IT-000104',
        'department_id' => $it->id,
        'requester_id' => $requester->id,
        'subject' => 'Écran noir',
        'description' => 'Le poste ne démarre plus.',
        'category' => 'hardware',
        'priority' => 'normal',
        'status' => 'new',
    ]);

    $this->actingAs($director)
        ->get(route('intranet.tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Prendre en charge')
        ->assertSee($technician->name);

    $this->post(route('intranet.tickets.take', $ticket))
        ->assertRedirect(route('intranet.tickets.show', $ticket));

    expect($ticket->fresh()->assignee_id)->toBe($director->id)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::InProgress);

    $this->put(route('intranet.tickets.update', $ticket), [
        'status' => 'waiting',
        'priority' => 'high',
        'assignee_id' => $technician->id,
    ])->assertRedirect(route('intranet.tickets.show', $ticket));

    expect($ticket->fresh()->assignee_id)->toBe($technician->id)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Waiting);

    Notification::assertSentTo($requester, TicketNotification::class);
});

it('shows recent forms from handled departments and tickets the user may access', function () {
    $it = departementIt();
    $hse = Department::factory()->create(['code' => 'HSE', 'name' => 'Santé et sécurité']);
    $finance = Department::factory()->create(['code' => 'FIN', 'name' => 'Finances']);
    $technician = User::factory()->withRole($hse, DepartmentRole::Technician, 1)->create();
    $colleague = User::factory()->create();
    $hseForm = DepartmentForm::create([
        'department_id' => $hse->id,
        'name' => 'Demande HSE visible',
        'form_schema' => [],
    ]);
    $financeForm = DepartmentForm::create([
        'department_id' => $finance->id,
        'name' => 'Demande confidentielle',
        'form_schema' => [],
    ]);
    $ownForm = DepartmentForm::create([
        'department_id' => $it->id,
        'name' => 'Mon formulaire IT',
        'form_schema' => [],
    ]);
    FormSubmission::create([
        'form_id' => $hseForm->id,
        'user_id' => $colleague->id,
        'data' => [],
        'status' => 'pending',
    ]);
    FormSubmission::create([
        'form_id' => $financeForm->id,
        'user_id' => $colleague->id,
        'data' => [],
        'status' => 'pending',
    ]);
    FormSubmission::create([
        'form_id' => $ownForm->id,
        'user_id' => $technician->id,
        'data' => [],
        'status' => 'pending',
    ]);
    $ticket = Ticket::create([
        'number' => 'IT-000105',
        'department_id' => $it->id,
        'requester_id' => $technician->id,
        'subject' => 'Mon ticket IT',
        'description' => 'Demande personnelle.',
        'category' => 'software',
        'priority' => 'normal',
        'status' => 'new',
    ]);

    $this->actingAs($technician)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Demande HSE visible')
        ->assertSee('Mon formulaire IT')
        ->assertSee('Mon ticket IT')
        ->assertDontSee('Demande confidentielle')
        ->assertSee(route('intranet.tickets.show', $ticket), false);
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
        ->post(route('intranet.tickets.take', $ticket))
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
