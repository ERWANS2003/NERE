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
