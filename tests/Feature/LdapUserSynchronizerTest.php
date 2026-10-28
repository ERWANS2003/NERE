<?php

use App\Ldap\LdapUserSynchronizer;
use App\Models\User;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;

it('updates both displayed and legacy job title fields from Active Directory', function () {
    $localUser = User::factory()->make([
        'poste' => 'Ancien poste',
        'position_title' => 'Ancien intitulé organisationnel',
    ]);
    $ldapUser = new LdapUser;
    $ldapUser->setAttribute('title', 'Responsable des opérations');

    $attributes = app(LdapUserSynchronizer::class)($localUser, $ldapUser);

    expect($attributes['position_title'])->toBe('Responsable des opérations')
        ->and($attributes['poste'])->toBe('Responsable des opérations');

    $localUser->forceFill($attributes);

    expect($localUser->job_title)->toBe('Responsable des opérations');
});

it('preserves the stored job title when Active Directory has no title', function () {
    $localUser = User::factory()->make([
        'poste' => 'Poste conservé',
        'position_title' => 'Fonction conservée',
    ]);
    $ldapUser = new LdapUser;

    $attributes = app(LdapUserSynchronizer::class)($localUser, $ldapUser);

    expect($attributes)->not->toHaveKey('poste')
        ->and($attributes)->not->toHaveKey('position_title');
});

it('uses the Active Directory description as a fallback when title is not set', function () {
    $localUser = User::factory()->make();
    $ldapUser = new LdapUser;
    $ldapUser->setAttribute('description', 'Directeur des Systèmes d’Information');

    $attributes = app(LdapUserSynchronizer::class)($localUser, $ldapUser);

    expect($attributes['position_title'])->toBe('Directeur des Systèmes d’Information')
        ->and($attributes['poste'])->toBe('Directeur des Systèmes d’Information');
});

it('keeps a job title edited in the intranet when Active Directory has another one', function () {
    $localUser = User::factory()->make([
        'poste' => 'Chef de quart',
        'position_title' => 'Chef de quart',
        'poste_modifie_localement' => true,
    ]);
    $ldapUser = new LdapUser;
    $ldapUser->setAttribute('title', 'Ancien poste AD');

    $attributes = app(LdapUserSynchronizer::class)($localUser, $ldapUser);

    expect($attributes)->not->toHaveKey('poste')
        ->and($attributes)->not->toHaveKey('position_title');
});

it('marks a job title changed by an administrator as owned by the intranet', function () {
    $user = User::factory()->create(['poste' => 'Ancien poste AD', 'position_title' => 'Ancien poste AD']);
    $admin = User::factory()->superAdmin()->create();
    $payload = fn (string $poste) => [
        'name' => $user->name,
        'email' => $user->email,
        'matricule' => $user->matricule,
        'actif' => '1',
        'poste' => $poste,
    ];

    $this->actingAs($admin)->put(route('admin.users.update', $user), $payload('Ancien poste AD'));
    expect($user->fresh()->poste_modifie_localement)->toBeFalse();

    $this->actingAs($admin)->put(route('admin.users.update', $user), $payload('Chef de quart'));
    expect($user->fresh())
        ->poste_modifie_localement->toBeTrue()
        ->job_title->toBe('Chef de quart');

    $this->actingAs($admin)->put(route('admin.users.update', $user), $payload(''));
    expect($user->fresh()->poste_modifie_localement)->toBeFalse();
});
