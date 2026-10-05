<?php

use App\Models\User;
use Database\Factories\UserFactory;

/*
 * Confirmation du mot de passe avant une action sensible.
 */

it('affiche le formulaire de confirmation', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->get('/mot-de-passe/confirmation')
        ->assertOk();
});

it('confirme le mot de passe', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->post('/mot-de-passe/confirmation', ['password' => UserFactory::PASSWORD])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accueil'));

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('refuse un mot de passe incorrect', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->post('/mot-de-passe/confirmation', ['password' => 'mauvais-mot-de-passe'])
        ->assertSessionHasErrors('password', trans('auth.password'));

    expect(session('auth.password_confirmed_at'))->toBeNull();
});

it('confirme aussi pour un utilisateur connecte avec son matricule', function () {
    // La session d'authentification herite du matricule ou de l'email selon la
    // saisie. Forcer `email` ferait echouer la validation pour quelqu'un
    // connecte avec son matricule.
    $utilisateur = User::factory()->create();

    $this->post('/connexion', [
        'identifiant' => (string) $utilisateur->matricule,
        'password' => UserFactory::PASSWORD,
    ])->assertRedirect('/accueil');

    $this->post('/mot-de-passe/confirmation', ['password' => UserFactory::PASSWORD])
        ->assertSessionHasNoErrors();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('refuse un compte desactive', function () {
    $utilisateur = User::factory()->inactive()->create();

    $this->actingAs($utilisateur)
        ->get('/mot-de-passe/confirmation')
        ->assertRedirect('/connexion');

    $this->assertGuest();
});
