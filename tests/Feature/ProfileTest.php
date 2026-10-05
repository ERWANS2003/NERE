<?php

use App\Models\User;

/*
 * Profil personnel.
 *
 * Le profil est volontairement reduit : le matricule et le statut du compte sont
 * des donnees administratives. Ces tests verrouillent cette frontiere — c'est
 * elle qui empeche un salarie de s'effacer de l'organigramme depuis son poste.
 */

it('affiche la page mon compte', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->get('/mon-compte')
        ->assertOk()
        ->assertSee('Mon compte')
        ->assertSee((string) $utilisateur->matricule);
});

it('met a jour le nom et l adresse e-mail', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->patch('/mon-compte', [
            'name' => 'Camille Roux',
            'email' => 'camille.roux@nere.fr',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profil.edit'))
        ->assertSessionHas('statut', 'profil-modifie');

    $utilisateur->refresh();

    expect($utilisateur->name)->toBe('Camille Roux')
        ->and($utilisateur->email)->toBe('camille.roux@nere.fr');
});

it('normalise l adresse e-mail en minuscules et supprime les espaces', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->patch('/mon-compte', [
            'name' => '  Camille Roux  ',
            'email' => '  Camille.Roux@NERE.FR ',
        ])
        ->assertSessionHasNoErrors();

    $utilisateur->refresh();

    expect($utilisateur->name)->toBe('Camille Roux')
        ->and($utilisateur->email)->toBe('camille.roux@nere.fr');
});

it('n autorise pas deux comptes sur la meme adresse', function () {
    $utilisateur = User::factory()->create();
    $autre = User::factory()->create();

    $this->actingAs($utilisateur)
        ->patch('/mon-compte', [
            'name' => 'Camille Roux',
            'email' => $autre->email,
        ])
        ->assertSessionHasErrors('email');

    expect($autre->fresh()->email)->toBe($autre->email);
});

it('accepte de conserver sa propre adresse', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->patch('/mon-compte', [
            'name' => 'Camille Roux',
            'email' => $utilisateur->email,
        ])
        ->assertSessionHasNoErrors();
});

it('exige un nom et une adresse valide', function (array $champs, array $attendues) {
    $this->actingAs(User::factory()->create())
        ->patch('/mon-compte', $champs)
        ->assertSessionHasErrors($attendues);
})->with([
    'nom vide' => [['name' => '', 'email' => 'a@nere.fr'], ['name']],
    'adresse invalide' => [['name' => 'Camille', 'email' => 'pas-une-adresse'], ['email']],
    'rien' => [[], ['name', 'email']],
]);

it('ne modifie ni le matricule ni le statut du compte', function () {
    $utilisateur = User::factory()->create(['is_active' => true]);

    $this->actingAs($utilisateur)
        ->patch('/mon-compte', [
            'name' => 'Camille Roux',
            'email' => 'camille.roux@nere.fr',
            // Tentatives d'ecrasement de donnees administratives.
            'matricule' => 'HACK-1',
            'is_active' => false,
            'is_super_admin' => true,
        ])
        ->assertSessionHasNoErrors();

    $utilisateur->refresh();

    expect($utilisateur->matricule)->not->toBe('HACK-1')
        ->and($utilisateur->is_active)->toBeTrue()
        ->and($utilisateur->is_super_admin)->toBeFalse();
});

it('n a aucune suppression de compte depuis le profil', function () {
    // Un salarie ne decide pas de son propre depart : la desactivation passe par
    // l'administration, avec conservation de l'historique. Aucune route n'accepte
    // DELETE sur le profil, d'ou un 405 plutot qu'un 404 : l'URL existe bien, c'est
    // la methode qui est refusee.
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->delete('/mon-compte')
        ->assertStatus(405);

    expect(User::find($utilisateur->id))->not->toBeNull();
});

it('refuse un compte desactive', function () {
    $utilisateur = User::factory()->inactive()->create();

    $this->actingAs($utilisateur)
        ->get('/mon-compte')
        ->assertRedirect('/connexion')
        ->assertSessionHasErrors('identifiant', trans('auth.inactive'));

    $this->assertGuest();
});

it('deconnecte un compte desactive qui avait deja une session ouverte', function () {
    // C'est tout l'interet du middleware `active` : la desactivation ne se limite
    // pas au moment de la connexion.
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)->get('/mon-compte')->assertOk();

    $utilisateur->update(['is_active' => false]);

    $this->actingAs($utilisateur)
        ->get('/mon-compte')
        ->assertRedirect('/connexion')
        ->assertSessionHasErrors('identifiant', trans('auth.inactive'));

    $this->assertGuest();
});
