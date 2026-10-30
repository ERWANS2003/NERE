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
use App\Support\DepartmentRequestCatalog;
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

it('activates HR and administration service forms and accepts valid submissions', function () {
    $hr = Department::factory()->create(['code' => 'RH', 'name' => 'Ressources humaines']);
    $admin = Department::factory()->create(['code' => 'ADMIN', 'name' => 'Administration']);

    foreach ([
        ...array_keys(DepartmentRequestCatalog::serviceForms()['RH']),
        'Demande de congé',
    ] as $name) {
        DepartmentService::create([
            'department_id' => $hr->id,
            'name' => $name,
            'description' => 'Service RH',
            'link_type' => 'form',
            'order' => 1,
            'is_active' => true,
        ]);
    }
    foreach (array_keys(DepartmentRequestCatalog::serviceForms()['ADMIN']) as $name) {
        DepartmentService::create([
            'department_id' => $admin->id,
            'name' => $name,
            'description' => 'Service administratif',
            'link_type' => 'form',
            'order' => 1,
            'is_active' => true,
        ]);
    }

    $migration = require database_path('migrations/2026_10_19_150000_activate_hr_admin_request_forms.php');
    $migration->up();

    $form = DepartmentForm::where('department_id', $hr->id)
        ->where('name', 'Attestation de travail')
        ->firstOrFail();
    $service = DepartmentService::where('department_id', $hr->id)
        ->where('name', 'Attestation de travail')
        ->firstOrFail();
    $user = User::factory()->create();

    expect($service->link_url)->toBe('/intranet/forms/'.$form->id)
        ->and($service->link_type)->toBe('page')
        ->and(DepartmentService::where('department_id', $hr->id)
            ->where('name', 'Demande de congé')
            ->value('link_url'))->toBe('/intranet/time-off/demande')
        ->and(DepartmentService::where('department_id', $admin->id)->where('link_type', 'page')->count())->toBe(5);

    $this->actingAs($user)
        ->get(route('intranet.forms.show', $form))
        ->assertOk()
        ->assertSee('Motif de la demande')
        ->assertSee('Format souhaité');

    $this->get(route('intranet.departments.show', 'RH'))
        ->assertOk()
        ->assertSee('href="/intranet/forms/'.$form->id.'"', false)
        ->assertSee('href="/intranet/time-off/demande"', false);

    $adminForm = DepartmentForm::where('department_id', $admin->id)
        ->where('name', 'Réservation de salle')
        ->firstOrFail();
    $this->get(route('intranet.departments.show', 'ADMIN'))
        ->assertOk()
        ->assertSee('href="/intranet/forms/'.$adminForm->id.'"', false);

    $this->post(route('intranet.submissions.store', $form), [
        'data' => [
            'purpose' => 'Dossier bancaire',
            'format' => 'PDF',
            'extra' => 'doit être rejeté du stockage',
        ],
    ])->assertRedirect();

    expect(FormSubmission::firstOrFail()->data)
        ->toBe(['purpose' => 'Dossier bancaire', 'format' => 'PDF']);

    $this->post(route('intranet.submissions.store', $form), [
        'data' => [
            'purpose' => 'Dossier bancaire',
            'format' => 'Exécutable',
        ],
    ])->assertSessionHasErrors('data.format');
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
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
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
    $technician = User::factory()->create();
    $this->actingAs(User::factory()->superAdmin()->create())
        ->post(route('admin.users.departments.assign', $technician), [
            'department_id' => $it->id,
            'role' => DepartmentRole::Technician->value,
            'tech_level' => 1,
        ])
        ->assertRedirect(route('admin.users.show', $technician))
        ->assertSessionHas('success');

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

it('lets a system administrator take an IT ticket without a department membership', function () {
    Notification::fake();
    $it = departementIt();
    $admin = User::factory()->superAdmin()->create();
    $requester = User::factory()->create();
    $ticket = Ticket::create([
        'number' => 'IT-000106',
        'department_id' => $it->id,
        'requester_id' => $requester->id,
        'subject' => 'Accès à un outil',
        'description' => 'La connexion à l’outil est impossible.',
        'category' => 'access',
        'priority' => 'normal',
        'status' => 'new',
    ]);

    $this->actingAs($admin)
        ->post(route('intranet.tickets.take', $ticket))
        ->assertRedirect(route('intranet.tickets.show', $ticket));

    expect($ticket->fresh()->assignee_id)->toBe($admin->id)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->activities()->where('event', 'taken')->exists())->toBeTrue();

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
        ->assertSee('Bienvenue dans l’intranet de')
        ->assertSee('NÉRÉ MINING')
        ->assertSee('Accès rapides')
        ->assertSee('Mon espace')
        ->assertSee('Services')
        ->assertSee('Vie de l’entreprise')
        ->assertDontSee('Pilotage')
        ->assertSee('Faire une demande')
        ->assertSee(route('intranet.forms.index'), false)
        ->assertSee('Signaler un problème')
        ->assertSee(route('intranet.tickets.create'), false)
        ->assertSee('Traiter les demandes du département')
        ->assertSee(route('intranet.submissions.queue'), false)
        ->assertDontSee('Demande confidentielle')
        ->assertSee(route('intranet.tickets.show', $ticket), false);
});

it('shows employees personal navigation without request-processing tools', function () {
    $department = departementIt();
    $employee = User::factory()->withRole($department, DepartmentRole::User)->create();

    $this->actingAs($employee)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Accès rapides')
        ->assertSee('Mon espace')
        ->assertSee('Services')
        ->assertDontSee('Pilotage')
        ->assertDontSee('Demandes à traiter');
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

it('keeps a deactivated assignee when the IT director saves the ticket', function () {
    Notification::fake();
    $it = departementIt();
    $director = User::factory()->withRole($it, DepartmentRole::Director)->create();
    $former = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create(['name' => 'Technicien Parti']);
    $ticket = Ticket::create([
        'number' => 'IT-000105',
        'department_id' => $it->id,
        'requester_id' => User::factory()->create()->id,
        'subject' => 'Imprimante bloquée',
        'description' => 'Bourrage papier.',
        'category' => 'hardware',
        'priority' => 'normal',
        'status' => 'in_progress',
        'assignee_id' => $former->id,
    ]);
    $former->update(['actif' => false]);

    $this->actingAs($director)
        ->get(route('intranet.tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Technicien Parti');

    $this->put(route('intranet.tickets.update', $ticket), [
        'status' => 'waiting',
        'priority' => 'normal',
        'assignee_id' => $former->id,
    ])->assertSessionHasNoErrors();

    expect($ticket->fresh()->assignee_id)->toBe($former->id)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Waiting);
});

it('searches tickets by subject regardless of case', function () {
    $it = departementIt();
    $requester = User::factory()->create();
    Ticket::create([
        'number' => 'IT-000106',
        'department_id' => $it->id,
        'requester_id' => $requester->id,
        'subject' => 'Imprimante du bureau 3',
        'description' => 'Ne répond plus.',
        'category' => 'hardware',
        'priority' => 'normal',
        'status' => 'new',
    ]);

    $this->actingAs($requester)
        ->get(route('intranet.tickets.index', ['search' => 'imprimante']))
        ->assertOk()
        ->assertSee('IT-000106');
});

it('numbers each new ticket from its id', function () {
    Notification::fake();
    $it = departementIt();
    $requester = User::factory()->create();

    foreach (['Premier', 'Second'] as $subject) {
        $this->actingAs($requester)->post(route('intranet.tickets.store'), [
            'subject' => $subject,
            'description' => 'Description.',
            'category' => 'software',
            'priority' => 'normal',
        ])->assertSessionHasNoErrors();
    }

    expect(Ticket::orderBy('id')->get()->map(fn (Ticket $t) => $t->number === 'IT-'.str_pad((string) $t->id, 6, '0', STR_PAD_LEFT))->all())
        ->toBe([true, true]);
});

function ticketAffectation(Department $it, ?User $assignee = null): Ticket
{
    return Ticket::create([
        'number' => 'IT-'.fake()->unique()->numerify('######'),
        'department_id' => $it->id,
        'requester_id' => User::factory()->create()->id,
        'assignee_id' => $assignee?->id,
        'subject' => 'Poste en panne',
        'description' => 'Le poste ne démarre plus.',
        'category' => 'hardware',
        'priority' => 'normal',
        'status' => 'new',
    ]);
}

function affecterTicket(Ticket $ticket, ?User $assignee)
{
    return test()->put(route('intranet.tickets.update', $ticket), [
        'status' => 'in_progress',
        'priority' => 'normal',
        'assignee_id' => $assignee?->id,
    ]);
}

it('lets technicians assign tickets only to strictly lower levels', function (int $level, array $allowed) {
    Notification::fake();
    $it = departementIt();
    $assigner = User::factory()->withRole($it, DepartmentRole::Technician, $level)->create();
    $targets = [
        1 => User::factory()->withRole($it, DepartmentRole::Technician, 1)->create(),
        2 => User::factory()->withRole($it, DepartmentRole::Technician, 2)->create(),
        3 => User::factory()->withRole($it, DepartmentRole::Technician, 3)->create(),
    ];
    $this->actingAs($assigner);

    foreach ($targets as $targetLevel => $target) {
        $ticket = ticketAffectation($it);
        $response = affecterTicket($ticket, $target);

        if (in_array($targetLevel, $allowed, true)) {
            $response->assertSessionHasNoErrors();
            expect($ticket->fresh()->assignee_id)->toBe($target->id);
            Notification::assertSentTo($target, TicketNotification::class);
        } else {
            // Un N1 n'a pas le champ : sa valeur est ignorée. Les autres sont refusés.
            expect($ticket->fresh()->assignee_id)->toBeNull();
        }
    }
})->with([
    'niveau 3' => [3, [1, 2]],
    'niveau 2' => [2, [1]],
    'niveau 1' => [1, []],
]);

it('does not let a technician assign to a director or to themselves', function () {
    $it = departementIt();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 3)->create();
    $director = User::factory()->withRole($it, DepartmentRole::Director)->create();
    $ticket = ticketAffectation($it);
    $this->actingAs($technician);

    affecterTicket($ticket, $director)->assertSessionHasErrors('assignee_id');
    affecterTicket($ticket, $technician)->assertSessionHasErrors('assignee_id');

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

it('does not let a technician take a ticket away from a peer or a higher level', function () {
    Notification::fake();
    $it = departementIt();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 2)->create();
    $peer = User::factory()->withRole($it, DepartmentRole::Technician, 2)->create();
    $senior = User::factory()->withRole($it, DepartmentRole::Technician, 3)->create();
    $junior = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
    $this->actingAs($technician);

    foreach ([$peer, $senior] as $holder) {
        $ticket = ticketAffectation($it, $holder);
        $this->get(route('intranet.tickets.show', $ticket))->assertOk()->assertDontSee('id="assignee_id"', false);
        affecterTicket($ticket, $junior)->assertRedirect();
        expect($ticket->fresh()->assignee_id)->toBe($holder->id);
    }

    $ticket = ticketAffectation($it, $technician);
    affecterTicket($ticket, $junior)->assertSessionHasNoErrors();
    expect($ticket->fresh()->assignee_id)->toBe($junior->id);

    affecterTicket($ticket, null)->assertSessionHasNoErrors();
    expect($ticket->fresh()->assignee_id)->toBeNull();
});

it('lists only lower levels in the assignment field and keeps take for directors', function () {
    $it = departementIt();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 2)->create();
    $junior = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create(['name' => 'Junior Niveau Un']);
    User::factory()->withRole($it, DepartmentRole::Technician, 3)->create(['name' => 'Senior Niveau Trois']);
    User::factory()->withRole($it, DepartmentRole::Technician, 2)->create(['name' => 'Pair Niveau Deux']);

    $this->actingAs($technician)
        ->get(route('intranet.tickets.show', ticketAffectation($it)))
        ->assertOk()
        ->assertSee('id="assignee_id"', false)
        ->assertSee($junior->name)
        ->assertDontSee('Senior Niveau Trois')
        ->assertDontSee('Pair Niveau Deux')
        ->assertDontSee('Prendre en charge');
});
