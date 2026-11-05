<?php

use App\Models\Feedback;
use App\Models\User;
use App\Notifications\FeedbackReceived;
use Illuminate\Support\Facades\Notification;

it('lets an employee send feedback that notifies administrators', function () {
    Notification::fake();
    $employee = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($employee)->get(route('intranet.dashboard'))->assertSee('intranet-feedback-link', false);
    $this->actingAs($employee)->get(route('intranet.feedback.create'))->assertOk()->assertSee('Donner mon avis');

    $this->actingAs($employee)->post(route('intranet.feedback.store'), [
        'category' => 'suggestion',
        'rating' => 4,
        'message' => 'Ajouter un annuaire téléphonique.',
    ])->assertRedirect(route('intranet.feedback.create'));

    $feedback = Feedback::firstOrFail();
    expect($feedback->user_id)->toBe($employee->id)
        ->and($feedback->rating)->toBe(4)
        ->and($feedback->status)->toBe('new');
    Notification::assertSentTo($admin, FeedbackReceived::class);
    Notification::assertNotSentTo($employee, FeedbackReceived::class);
});

it('keeps anonymous feedback anonymous and validates the input', function () {
    Notification::fake();
    $employee = User::factory()->create();

    $this->actingAs($employee)->post(route('intranet.feedback.store'), [
        'category' => 'bug',
        'message' => 'La recherche est lente.',
        'anonymous' => '1',
    ])->assertSessionHasNoErrors();
    expect(Feedback::firstOrFail()->user_id)->toBeNull();

    $this->actingAs($employee)->post(route('intranet.feedback.store'), [
        'category' => 'inconnue',
        'rating' => 9,
        'message' => '',
    ])->assertSessionHasErrors(['category', 'rating', 'message']);
});

it('lets administrators review feedback and answer the author', function () {
    $employee = User::factory()->create(['name' => 'Awa Auteur']);
    $feedback = Feedback::create(['user_id' => $employee->id, 'category' => 'suggestion', 'rating' => 5, 'message' => 'Super intranet.']);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($employee)->get(route('admin.feedback.index'))->assertForbidden();

    $this->actingAs($admin)->get(route('admin.feedback.index'))
        ->assertOk()
        ->assertSee('Super intranet.')
        ->assertSee('Awa Auteur');

    $this->actingAs($admin)->patch(route('admin.feedback.update', $feedback), [
        'status' => 'done',
        'admin_note' => 'Merci, c’est noté.',
    ])->assertRedirect();

    expect($feedback->fresh()->status)->toBe('done');
    $this->actingAs($employee)->get(route('intranet.feedback.create'))->assertSee('Merci, c’est noté.');
});

it('renders the page skeleton and the role label in the layouts', function () {
    $admin = User::factory()->superAdmin()->create(['poste' => null, 'position_title' => null]);
    $employee = User::factory()->create(['poste' => null, 'position_title' => null]);

    $this->actingAs($admin)->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('data-page-skeleton-root', false)
        ->assertSee('Administrateur');
    $this->actingAs($admin)->get(route('admin.health'))
        ->assertOk()
        ->assertSee('data-page-skeleton-root', false);
    $this->actingAs($employee)->get(route('intranet.dashboard'))->assertSee('Employé');

    $this->get(route('login'))->assertDontSee('ou adresse e-mail');
});
