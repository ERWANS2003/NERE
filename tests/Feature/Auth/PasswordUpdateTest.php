<?php

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;

/*
 * Changement de mot de passe depuis le profil.
 */

it('change le mot de passe', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->from('/mon-compte')
        ->put('/mot-de-passe', [
            'current_password' => UserFactory::PASSWORD,
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'NouveauMotDePasse!2026',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('statut', 'mot-de-passe-modifie');

    expect(Hash::check('NouveauMotDePasse!2026', $utilisateur->fresh()->password))->toBeTrue();
});

it('exige le mot de passe actuel', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->from('/mon-compte')
        ->put('/mot-de-passe', [
            'current_password' => 'mauvais-mot-de-passe',
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'NouveauMotDePasse!2026',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check(UserFactory::PASSWORD, $utilisateur->fresh()->password))->toBeTrue();
});

it('applique la politique de mot de passe', function (string $motDePasse) {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->from('/mon-compte')
        ->put('/mot-de-passe', [
            'current_password' => UserFactory::PASSWORD,
            'password' => $motDePasse,
            'password_confirmation' => $motDePasse,
        ])
        ->assertSessionHasErrors('password');

    expect(Hash::check(UserFactory::PASSWORD, $utilisateur->fresh()->password))->toBeTrue();
})->with([
    'trop court' => ['tropcourt'],
    'sans majuscule' => ['nere-mining2026!'],
    'sans chiffre' => ['Nere-Mining-ve!'],
    'sans symbole' => ['NereMining2026x'],
]);

it('liberere un compte verrouille', function () {
    // Changer de mot de passe est le reflexe attendu d'un utilisateur bloque : le
    // verrouillage doit sauter, sinon il resterait bloque avec un mot de passe
    // qu'il vient de changer.
    $utilisateur = User::factory()->withFailedAttempts(4)->create();

    $this->actingAs($utilisateur)
        ->from('/mon-compte')
        ->put('/mot-de-passe', [
            'current_password' => UserFactory::PASSWORD,
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'NouveauMotDePasse!2026',
        ])
        ->assertSessionHasNoErrors();

    $utilisateur->refresh();

    expect($utilisateur->failed_attempts)->toBe(0)
        ->and($utilisateur->locked_until)->toBeNull();
});

it('refuse un compte desactive', function () {
    $utilisateur = User::factory()->inactive()->create();

    $this->actingAs($utilisateur)
        ->from('/mon-compte')
        ->put('/mot-de-passe', [
            'current_password' => UserFactory::PASSWORD,
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'NouveauMotDePasse!2026',
        ])
        ->assertRedirect('/connexion');

    $this->assertGuest();
});
