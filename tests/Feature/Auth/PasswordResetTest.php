<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/*
 * Demande et consommation d'un lien de reinitialisation.
 */

it('affiche le formulaire de demande de lien', function () {
    $this->get('/mot-de-passe/oubie')
        ->assertOk()
        ->assertSee('Mot de passe oublié', false);
});

it('exige une adresse e-mail valide', function (array $champs) {
    $this->from('/mot-de-passe/oubie')
        ->post('/mot-de-passe/oubie', $champs)
        ->assertSessionHasErrors('email');
})->with([
    'vide' => [['email' => '']],
    'pas une adresse' => [['email' => 'pas-une-adresse']],
]);

it('envoie le lien de reinitialisation', function () {
    Notification::fake();

    $utilisateur = User::factory()->create();

    $this->from('/mot-de-passe/oubie')
        ->post('/mot-de-passe/oubie', ['email' => $utilisateur->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('statut', trans('passwords.sent'));

    Notification::assertSentTo($utilisateur, ResetPassword::class);
});

it('repond pareil pour un compte inconnu, pour ne pas recenser les employes', function () {
    // Anti-enumeration : le message doit etre rigoureusement identique a celui
    // d'un compte existant, sinon le formulaire devient un annuaire.
    Notification::fake();

    $utilisateur = User::factory()->create();

    $this->from('/mot-de-passe/oubie')
        ->post('/mot-de-passe/oubie', ['email' => $utilisateur->email]);

    $avecCompte = session('statut');

    Notification::fake();

    $this->from('/mot-de-passe/oubie')
        ->post('/mot-de-passe/oubie', ['email' => 'personne@nere.fr']);

    expect(session('statut'))->toBe($avecCompte)
        ->and($avecCompte)->toBe(trans('passwords.sent'));

    Notification::assertNothingSent();
});

it('affiche le formulaire de reinitialisation a partir du lien', function () {
    Notification::fake();

    $utilisateur = User::factory()->create();

    $this->post('/mot-de-passe/oubie', ['email' => $utilisateur->email]);

    Notification::assertSentTo($utilisateur, ResetPassword::class, function ($notification) {
        $this->get('/mot-de-passe/reinitialisation/'.$notification->token)->assertOk();

        return true;
    });
});

it('change le mot de passe avec un jeton valide', function () {
    Notification::fake();

    $utilisateur = User::factory()->create();

    $this->post('/mot-de-passe/oubie', ['email' => $utilisateur->email]);

    Notification::assertSentTo($utilisateur, ResetPassword::class, function ($notification) use ($utilisateur) {
        $this->post('/mot-de-passe/reinitialisation', [
            'token' => $notification->token,
            'email' => $utilisateur->email,
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'NouveauMotDePasse!2026',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('NouveauMotDePasse!2026', $utilisateur->fresh()->password))->toBeTrue();

        return true;
    });
});

it('permet de se connecter avec le nouveau mot de passe', function () {
    Notification::fake();

    $utilisateur = User::factory()->create();

    $this->post('/mot-de-passe/oubie', ['email' => $utilisateur->email]);

    Notification::assertSentTo($utilisateur, ResetPassword::class, function ($notification) use ($utilisateur) {
        $this->post('/mot-de-passe/reinitialisation', [
            'token' => $notification->token,
            'email' => $utilisateur->email,
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'NouveauMotDePasse!2026',
        ]);

        $this->post('/connexion', [
            'identifiant' => (string) $utilisateur->matricule,
            'password' => 'NouveauMotDePasse!2026',
        ])->assertRedirect('/accueil');

        $this->assertAuthenticatedAs($utilisateur->fresh());

        return true;
    });
});

it('refuse un jeton invalide', function () {
    $utilisateur = User::factory()->create();

    $ancienMotDePasse = $utilisateur->password;

    $this->from('/mot-de-passe/reinitialisation/jeton-bidon')
        ->post('/mot-de-passe/reinitialisation', [
            'token' => 'jeton-bidon',
            'email' => $utilisateur->email,
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'NouveauMotDePasse!2026',
        ])
        ->assertSessionHasErrors('email', trans('passwords.token'));

    expect($utilisateur->fresh()->password)->toBe($ancienMotDePasse);
});

it('impose la politique de mot de passe lors d une reinitialisation', function () {
    Notification::fake();

    $utilisateur = User::factory()->create();

    $this->post('/mot-de-passe/oubie', ['email' => $utilisateur->email]);

    Notification::assertSentTo($utilisateur, ResetPassword::class, function ($notification) use ($utilisateur) {
        $this->post('/mot-de-passe/reinitialisation', [
            'token' => $notification->token,
            'email' => $utilisateur->email,
            // 12 caracteres et un symbole, mais ni majuscule ni chiffre.
            'password' => 'tropcourt!abc',
            'password_confirmation' => 'tropcourt!abc',
        ])->assertSessionHasErrors('password');

        return true;
    });
});

it('exige la confirmation du nouveau mot de passe', function () {
    Notification::fake();

    $utilisateur = User::factory()->create();

    $this->post('/mot-de-passe/oubie', ['email' => $utilisateur->email]);

    Notification::assertSentTo($utilisateur, ResetPassword::class, function ($notification) use ($utilisateur) {
        $this->post('/mot-de-passe/reinitialisation', [
            'token' => $notification->token,
            'email' => $utilisateur->email,
            'password' => 'NouveauMotDePasse!2026',
            'password_confirmation' => 'AutreMotDePasse!2026',
        ])->assertSessionHasErrors('password');

        return true;
    });
});
