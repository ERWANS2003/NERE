<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
 * Contraintes de la table pivot.
 *
 * Ces tests existent parce que les regles « un technicien a toujours un niveau,
 * un non-technicien jamais » sont des invariants metier, pas une convention :
 * une affectation erronee doit etre refusee par la base, y compris si elle est
 * ecrite par un script de migration ou un import massif qui ne passe pas par les
 * formulaires.
 */

it('refuse un role inconnu', function () {
    $utilisateur = User::factory()->create();
    $departement = Department::factory()->create();

    expect(fn () => DB::table('department_user')->insert([
        'user_id' => $utilisateur->id,
        'department_id' => $departement->id,
        'role' => 'superuser',
        'tech_level' => null,
    ]))->toThrow(QueryException::class);
});

it('refuse un technicien sans niveau', function () {
    $utilisateur = User::factory()->create();
    $departement = Department::factory()->create();

    expect(fn () => DB::table('department_user')->insert([
        'user_id' => $utilisateur->id,
        'department_id' => $departement->id,
        'role' => 'technician',
        'tech_level' => null,
    ]))->toThrow(QueryException::class);
});

it('refuse un niveau defini pour un role qui n en accepte pas', function () {
    $utilisateur = User::factory()->create();
    $departement = Department::factory()->create();

    expect(fn () => DB::table('department_user')->insert([
        'user_id' => $utilisateur->id,
        'department_id' => $departement->id,
        'role' => 'user',
        'tech_level' => 2,
    ]))->toThrow(QueryException::class);
});

it('refuse un niveau hors bornes', function (int $niveau) {
    $utilisateur = User::factory()->create();
    $departement = Department::factory()->create();

    expect(fn () => DB::table('department_user')->insert([
        'user_id' => $utilisateur->id,
        'department_id' => $departement->id,
        'role' => 'technician',
        'tech_level' => $niveau,
    ]))->toThrow(QueryException::class);
})->with([0, 4, 99]);

it('refuse deux affectations identiques', function () {
    $utilisateur = User::factory()->create();
    $departement = Department::factory()->create();

    DB::table('department_user')->insert([
        'user_id' => $utilisateur->id,
        'department_id' => $departement->id,
        'role' => 'user',
        'tech_level' => null,
    ]);

    expect(fn () => DB::table('department_user')->insert([
        'user_id' => $utilisateur->id,
        'department_id' => $departement->id,
        'role' => 'director',
        'tech_level' => null,
    ]))->toThrow(QueryException::class);
});

it('accepte les trois roles valides', function (string $role, ?int $niveau) {
    $utilisateur = User::factory()->create();
    $departement = Department::factory()->create();

    DB::table('department_user')->insert([
        'user_id' => $utilisateur->id,
        'department_id' => $departement->id,
        'role' => $role,
        'tech_level' => $niveau,
    ]);

    expect(DB::table('department_user')->count())->toBe(1);
})->with([
    ['user', null],
    ['technician', 1],
    ['technician', 3],
    ['director', null],
]);

it('supprime les droits quand l utilisateur est supprime', function () {
    $utilisateur = User::factory()
        ->withRole(Department::factory()->create(), DepartmentRole::User)
        ->create();

    expect(DB::table('department_user')->count())->toBe(1);

    $utilisateur->delete();

    expect(DB::table('department_user')->count())->toBe(0);
});
