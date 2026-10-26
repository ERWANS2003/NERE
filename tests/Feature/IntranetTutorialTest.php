<?php

use App\Models\User;

it('affiche le tutoriel aux nouveaux utilisateurs et permet de le terminer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Bienvenue dans votre intranet')
        ->assertSee('intranet-services-section')
        ->assertSee('intranet-company-section')
        ->assertSee('intranet-search-trigger')
        ->assertSee('intranet-notifications-trigger')
        ->assertSee('Revoir le tutoriel');

    $this->post(route('intranet.tutorial.complete'))
        ->assertRedirect()
        ->assertSessionHas('status', 'Tutoriel terminé.');

    expect($user->fresh()->tutorial_completed_at)->not->toBeNull();

    $this->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('x-init="init(false)"', false);
});

it('permet de relancer le tutoriel depuis le menu du profil', function () {
    $user = User::factory()->create([
        'tutorial_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Revoir le tutoriel')
        ->assertSee('open-intranet-tutorial');
});

it('réserve la validation du tutoriel aux utilisateurs connectés', function () {
    $this->post(route('intranet.tutorial.complete'))
        ->assertRedirect('/connexion');
});
