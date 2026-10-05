<?php

use App\Models\User;
use Database\Factories\UserFactory;

/*
 * Connexion par matricule ou adresse e-mail.
 *
 * Les tests d'enumeration de comptes et de verrouillage sont les plus importants
 * du lot : ce sont les deux garde-fous de securite du formulaire, et les deux
 * endroits ou une reecriture trop permissive ouvrirait la porte sans que
 * l'interface ne change d'aspect.
 */

/** Saisit le formulaire de connexion et renvoie la reponse. */
function seConnecter(string $identifiant, string $motDePasse = UserFactory::PASSWORD, array $extra = [])
{
    return test()->post('/connexion', array_merge([
        'identifiant' => $identifiant,
        'password' => $motDePasse,
    ], $extra));
}

it('affiche la page de connexion aux invites', function () {
    $this->get('/connexion')
        ->assertOk()
        ->assertSee('Connexion')
        ->assertSee('identifiant', false);
});

it('renvoie les invites vers la page de connexion', function (string $url) {
    $this->get($url)->assertRedirect('/connexion');
})->with(['/', '/accueil', '/mon-compte']);

it('accepte le matricule', function () {
    $utilisateur = User::factory()->create(['matricule' => '1042']);

    seConnecter('1042')->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($utilisateur);
});

it('accepte l adresse e-mail', function () {
    $utilisateur = User::factory()->create(['email' => 'camille.roux@nere.fr']);

    seConnecter('camille.roux@nere.fr')->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($utilisateur);
});

it('ignore la casse de l adresse e-mail', function () {
    $utilisateur = User::factory()->create(['email' => 'camille.roux@nere.fr']);

    seConnecter('Camille.Roux@NERE.FR')->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($utilisateur);
});

it('preserve la casse d un matricule mixte', function () {
    // Un matricule est un identifiant fonctionnel saisi au clavier : le
    // normaliser en minuscules empecherait `ab123` de retrouver la ligne `AB123`.
    $utilisateur = User::factory()->create(['matricule' => 'AB123']);

    seConnecter('AB123')->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($utilisateur);
});

it('ignore les espaces autour de l identifiant', function () {
    $utilisateur = User::factory()->create(['matricule' => '1042']);

    seConnecter('  1042  ')->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($utilisateur);
});

it('refuse un mauvais mot de passe', function () {
    $utilisateur = User::factory()->create();

    seConnecter((string) $utilisateur->matricule, 'mauvais-mot-de-passe')
        ->assertSessionHasErrors('identifiant', trans('auth.failed'));

    $this->assertGuest();
});

it('renvoie le meme message pour un compte inconnu que pour un mot de passe faux', function () {
    // Sinon la page de connexion revele quels matricules existent sur le site.
    seConnecter('matricule-inexistant-999999')
        ->assertSessionHasErrors('identifiant', trans('auth.failed'));

    $utilisateur = User::factory()->create();

    seConnecter((string) $utilisateur->matricule, 'mauvais-mot-de-passe')
        ->assertSessionHasErrors('identifiant', trans('auth.failed'));
});

it('refuse un compte desactive, meme avec le bon mot de passe', function () {
    $utilisateur = User::factory()->inactive()->create();

    seConnecter((string) $utilisateur->matricule)
        ->assertSessionHasErrors('identifiant', trans('auth.inactive'));

    $this->assertGuest();
});

it('repond une erreur de validation, pas une erreur 500, sur un compte desactive', function () {
    // Regression : le controle de verrouillage etait appele avant le controle
    // d'activite et lisait `locked_until` sur un compte qui n'a jamais echoue,
    // ce qui renvoyait une erreur serveur au lieu du message prevu.
    $utilisateur = User::factory()->inactive()->create();

    seConnecter((string) $utilisateur->matricule)
        ->assertRedirect()
        ->assertSessionHasErrors('identifiant', trans('auth.inactive'));

    $this->assertGuest();
});

it('exige un identifiant et un mot de passe', function (array $champs) {
    seConnecter('', '', $champs)->assertSessionHasErrors(array_keys($champs));

    $this->assertGuest();
})->with([
    'sans rien' => [['identifiant' => '', 'password' => '']],
    'sans identifiant' => [['identifiant' => '']],
    'sans mot de passe' => [['password' => '']],
]);

it('verrouille le compte apres cinq echecs et l annonce', function () {
    $utilisateur = User::factory()->create();

    for ($essai = 1; $essai < User::MAX_LOGIN_ATTEMPTS; $essai++) {
        seConnecter((string) $utilisateur->matricule, 'mauvais-mot-de-passe')
            ->assertSessionHasErrors('identifiant', trans('auth.failed'));
    }

    // La cinquieme tentative arme le verrouillage : le message doit le dire
    // immediatement plutot que de laisser l'utilisateur retenter pour decouvrir
    // un blocage.
    seConnecter((string) $utilisateur->matricule, 'mauvais-mot-de-passe')
        ->assertSessionHasErrors('identifiant');

    $utilisateur->refresh();

    expect($utilisateur->isLockedOut())->toBeTrue()
        ->and($utilisateur->locked_until->greaterThan(now()))->toBeTrue();

    $this->assertGuest();
});

it('refuse le bon mot de passe pendant un verrouillage', function () {
    $utilisateur = User::factory()->lockedOut()->create();

    seConnecter((string) $utilisateur->matricule)
        ->assertSessionHasErrors('identifiant');

    $this->assertGuest();
});

it('rend le meme message de verrouillage quelle que soit la saisie', function () {
    // Temps fige : la duree affichee est recalculee a chaque requete, et on veut
    // comparer deux messages octet pour octet.
    $this->freezeTime();

    $utilisateur = User::factory()->lockedOut()->create();

    $attendu = trans('auth.locked', [
        'seconds' => $utilisateur->locked_until->getTimestamp() - now()->getTimestamp(),
        'minutes' => 15,
    ]);

    seConnecter((string) $utilisateur->matricule)
        ->assertSessionHasErrors(['identifiant' => $attendu]);

    seConnecter((string) $utilisateur->matricule, 'mauvais-mot-de-passe')
        ->assertSessionHasErrors(['identifiant' => $attendu]);
});

it('laisse reconnecter quand le verrouillage est echu', function () {
    $utilisateur = User::factory()->lockoutExpired()->create();

    seConnecter((string) $utilisateur->matricule)->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($utilisateur);
});

it('compte les echecs puis les remet a zero apres un succes', function () {
    $utilisateur = User::factory()->create();

    seConnecter((string) $utilisateur->matricule, 'mauvais-mot-de-passe');
    seConnecter((string) $utilisateur->matricule, 'mauvais-mot-de-passe');

    expect($utilisateur->fresh()->failed_attempts)->toBe(2);

    seConnecter((string) $utilisateur->matricule)->assertRedirect('/accueil');

    expect($utilisateur->fresh()->failed_attempts)->toBe(0)
        ->and($utilisateur->fresh()->locked_until)->toBeNull();
});

it('ne bloque pas un collegue derriere la meme adresse IP', function () {
    // La cle de limitation inclut l'IP : sur le reseau de la mine, plusieurs
    // personnes partagent souvent la meme adresse. Viser le seul identifiant
    // verrouillerait le collegue innocent du compte attaque.
    $attaque = User::factory()->create();

    for ($essai = 1; $essai <= User::MAX_LOGIN_ATTEMPTS; $essai++) {
        seConnecter((string) $attaque->matricule, 'mauvais-mot-de-passe');
    }

    $collegue = User::factory()->create();

    seConnecter((string) $collegue->matricule)->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($collegue);
});

it('ne contourne pas la limite en changeant la casse de l identifiant', function () {
    // La cle du limiteur est normalisee : un intrus qui change la casse a chaque
    // tentative ne repart pas avec un compteur neuf. Le matricule `ab123` ne
    // retrouvant aucune ligne, c'est le limiteur — et non le verrouillage en base,
    // qui ne peut pas attribuer l'echec a un compte — qui finit par bloquer.
    $utilisateur = User::factory()->create(['matricule' => 'AB123']);

    // Temps fige : la duree restante du limiteur est sinon dependante de
    // l'horloge et rendrait l'assertion instable.
    $this->freezeTime();

    // Cinq tentatives echouent franchement (aucun compte ne peut etre verrouille,
    // puisque `ab123` ne correspond a aucune ligne) ; la sixieme est refusee par
    // le limiteur avant meme de toucher la base.
    for ($essai = 1; $essai <= User::MAX_LOGIN_ATTEMPTS; $essai++) {
        seConnecter('ab123', 'mauvais-mot-de-passe')
            ->assertSessionHasErrors('identifiant', trans('auth.failed'));
    }

    seConnecter('ab123', 'mauvais-mot-de-passe')
        ->assertSessionHasErrors([
            'identifiant' => trans('auth.throttle', ['seconds' => 60]),
        ]);

    $this->assertGuest();
});

it('deconnecte', function () {
    $utilisateur = User::factory()->create();

    seConnecter((string) $utilisateur->matricule);

    $this->assertAuthenticatedAs($utilisateur);

    $this->post('/deconnexion')->assertRedirect('/connexion');

    $this->assertGuest();
});

it('n a aucune page d inscription publique', function (string $url) {
    $this->get($url)->assertNotFound();
})->with(['/inscription', '/register']);

it('renvoie un utilisateur deja connecte vers l accueil', function () {
    // Le middleware `guest` renvoie vers la racine, qui redirige a son tour vers
    // l'accueil : le test suit la chaine complete.
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)->get('/connexion')->assertRedirect('/');

    $this->actingAs($utilisateur)->get('/')->assertRedirect('/accueil');
});

it('ouvre une session prolongee quand la case se souvenir de moi est cochee', function () {
    $utilisateur = User::factory()->create();

    seConnecter((string) $utilisateur->matricule, UserFactory::PASSWORD, ['remember' => 'on'])
        ->assertRedirect('/accueil');

    $this->assertAuthenticatedAs($utilisateur);

    expect($utilisateur->fresh()->remember_token)->not->toBeNull();
});
