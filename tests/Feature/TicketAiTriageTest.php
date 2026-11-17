<?php

use App\Enums\DepartmentRole;
use App\Jobs\SuggestTicketTriage;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function aiTicketSetup(): array
{
    Notification::fake();
    $it = Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);

    return [
        User::factory()->create(),
        User::factory()->withRole($it, DepartmentRole::Technician, 1)->create(),
    ];
}

function aiReply(array $content): array
{
    return ['message' => ['role' => 'assistant', 'content' => json_encode($content)], 'done' => true];
}

it('does not call the AI while it is not configured', function () {
    config(['services.ticket_ai.url' => null]);
    Queue::fake();
    [$requester] = aiTicketSetup();

    $this->actingAs($requester)->post(route('intranet.tickets.store'), [
        'subject' => 'Outlook bloqué', 'description' => 'Chargement du profil.', 'category' => 'software', 'priority' => 'normal',
    ])->assertRedirect();

    Queue::assertNothingPushed();
});

it('queues the triage on the AI queue when a ticket is created', function () {
    config(['services.ticket_ai.url' => 'http://ia.local:11434']);
    Queue::fake();
    [$requester] = aiTicketSetup();

    $this->actingAs($requester)->post(route('intranet.tickets.store'), [
        'subject' => 'Outlook bloqué', 'description' => 'Chargement du profil.', 'category' => 'software', 'priority' => 'normal',
    ])->assertRedirect();

    Queue::assertPushedOn('ai', SuggestTicketTriage::class, fn ($job) => $job->ticketId === Ticket::firstOrFail()->id);
});

it('stores the suggestion and shows it to the IT team only', function () {
    config(['services.ticket_ai.url' => 'http://ia.local:11434', 'queue.default' => 'sync']);
    Http::fake(['ia.local:11434/api/chat' => Http::response(aiReply([
        'category' => 'email',
        'priority' => 'high',
        'summary' => 'Outlook reste bloqué au chargement du profil.',
        'reply' => "Bonjour,\nAvez-vous redémarré votre poste ?",
    ]))]);
    [$requester, $technician] = aiTicketSetup();

    $this->actingAs($requester)->post(route('intranet.tickets.store'), [
        'subject' => 'Outlook bloqué', 'description' => 'Chargement du profil.', 'category' => 'software', 'priority' => 'normal',
    ])->assertRedirect();

    $ticket = Ticket::firstOrFail();
    expect($ticket->ai_suggestion['category'])->toBe('email')
        ->and($ticket->ai_suggestion['priority'])->toBe('high')
        ->and($ticket->ai_suggested_at)->not->toBeNull();

    Http::assertSent(fn (Request $request) => $request['model'] === 'qwen3:4b-instruct'
        && str_contains($request['messages'][1]['content'], 'Outlook bloqué')
        && $request['format']['properties']['priority']['enum'] === ['low', 'normal', 'high', 'urgent']);

    $this->actingAs($technician)->get(route('intranet.tickets.show', $ticket))
        ->assertOk()
        ->assertSee("Suggestion de l'IA", false)
        ->assertSee('Outlook reste bloqué au chargement du profil.')
        ->assertSee('Messagerie')
        ->assertSee('Reporter dans le traitement')
        ->assertSee('Copier dans la réponse');

    $this->actingAs($requester)->get(route('intranet.tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee("Suggestion de l'IA", false)
        ->assertDontSee('Outlook reste bloqué au chargement du profil.');
});

it('ignores an answer outside the allowed values', function () {
    config(['services.ticket_ai.url' => 'http://ia.local:11434', 'queue.default' => 'sync']);
    Http::fake(['*' => Http::response(aiReply([
        'category' => 'imprimante', 'priority' => 'critique', 'summary' => 'x', 'reply' => 'y',
    ]))]);
    [$requester] = aiTicketSetup();

    $this->actingAs($requester)->post(route('intranet.tickets.store'), [
        'subject' => 'Imprimante', 'description' => '', 'category' => 'hardware', 'priority' => 'low',
    ])->assertRedirect();

    expect(Ticket::firstOrFail()->ai_suggestion)->toBeNull();
});
