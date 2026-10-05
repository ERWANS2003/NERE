<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * Droits par departement.
 *
 * Le modele `User` est le point de decision unique : policies, page d'accueil et
 * controleurs appellent tous `roleIn`, `canHandleIn`, `canValidateIn`. Ces tests
 * verrouillent ce contrat, en particulier les deux heritage qui sont les pieges
 * habituels : le directeur herite des droits technicien, et l'admin
 * general se comporte en directeur partout sans avoir de ligne de pivot.
 */

it('ne renvoie aucun role dans un departement ou l utilisateur n est pas affecte', function () {
    $utilisateur = User::factory()->create();
    $departement = Department::factory()->create();

    expect($utilisateur->roleIn($departement->id))->toBeNull()
        ->and($utilisateur->membershipIn($departement->id))->toBeNull()
        ->and($utilisateur->techLevelIn($departement->id))->toBeNull()
        ->and($utilisateur->isMemberOf($departement->id))->toBeFalse()
        ->and($utilisateur->canHandleIn($departement->id))->toBeFalse()
        ->and($utilisateur->canValidateIn($departement->id))->toBeFalse()
        ->and($utilisateur->canManageDepartment($departement->id))->toBeFalse()
        ->and($utilisateur->effectiveTechLevel($departement->id))->toBeNull();
});

it('donne a chaque role les droits qui lui appartiennent', function (DepartmentRole $role, ?int $niveau, bool $traitement, bool $validation, bool $gestion) {
    $departement = Department::factory()->create();

    $utilisateur = User::factory()
        ->withRole($departement, $role, $niveau)
        ->create();

    expect($utilisateur->roleIn($departement->id))->toBe($role)
        ->and($utilisateur->isMemberOf($departement->id))->toBeTrue()
        ->and($utilisateur->canHandleIn($departement->id))->toBe($traitement)
        ->and($utilisateur->canValidateIn($departement->id))->toBe($validation)
        ->and($utilisateur->canManageDepartment($departement->id))->toBe($gestion);
})->with([
    'utilisateur' => [DepartmentRole::User, null, false, false, false],
    'technicien N1' => [DepartmentRole::Technician, 1, true, false, false],
    'technicien N3' => [DepartmentRole::Technician, 3, true, false, false],
    'directeur' => [DepartmentRole::Director, null, true, true, true],
]);

it('donne a un directeur le niveau le plus haut pour qu il voie toute la file', function () {
    $departement = Department::factory()->create();

    $directeur = User::factory()->withRole($departement, DepartmentRole::Director)->create();

    expect($directeur->effectiveTechLevel($departement->id))
        ->toBe(User::HIGHEST_TECH_LEVEL);
});

it('laisse un technicien a son propre niveau, sans elargissement', function () {
    $departement = Department::factory()->create();

    $technicien = User::factory()->withRole($departement, DepartmentRole::Technician, 2)->create();

    expect($technicien->effectiveTechLevel($departement->id))->toBe(2);
});

it('ne donne aucun niveau a un simple utilisateur', function () {
    $departement = Department::factory()->create();

    $utilisateur = User::factory()->withRole($departement, DepartmentRole::User)->create();

    expect($utilisateur->effectiveTechLevel($departement->id))->toBeNull();
});

it('distingue les droits d un meme utilisateur selon le departement', function () {
    $it = Department::factory()->create();
    $surete = Department::factory()->create();

    $utilisateur = User::factory()
        ->withRole($it, DepartmentRole::Technician, 2)
        ->withRole($surete, DepartmentRole::User)
        ->create();

    expect($utilisateur->canHandleIn($it->id))->toBeTrue()
        ->and($utilisateur->techLevelIn($it->id))->toBe(2)
        ->and($utilisateur->canHandleIn($surete->id))->toBeFalse()
        ->and($utilisateur->techLevelIn($surete->id))->toBeNull();
});

it('reconnait l appartenance a plusieurs departements', function () {
    $premier = Department::factory()->create();
    $second = Department::factory()->create();

    $utilisateur = User::factory()->withRole($premier, DepartmentRole::User)->create();

    expect($utilisateur->isMemberOf($premier->id))->toBeTrue()
        ->and($utilisateur->isMemberOf($second->id))->toBeFalse();

    $utilisateur->departments()->attach($second, ['role' => 'user', 'tech_level' => null]);

    expect($utilisateur->refreshAuthorisations()->isMemberOf($second->id))->toBeTrue();
});

it('donne a l admin general les pouvoirs de directeur partout, sans affectation', function () {
    $departement = Department::factory()->create();

    $admin = User::factory()->superAdmin()->create();

    expect($admin->departments()->count())->toBe(0)
        ->and($admin->roleIn($departement->id))->toBe(DepartmentRole::Director)
        ->and($admin->isMemberOf($departement->id))->toBeTrue()
        ->and($admin->canHandleIn($departement->id))->toBeTrue()
        ->and($admin->canValidateIn($departement->id))->toBeTrue()
        ->and($admin->canManageDepartment($departement->id))->toBeTrue()
        ->and($admin->effectiveTechLevel($departement->id))->toBe(User::HIGHEST_TECH_LEVEL);
});

it('accepte plusieurs roles attendus', function () {
    $departement = Department::factory()->create();

    $directeur = User::factory()->withRole($departement, DepartmentRole::Director)->create();

    expect($directeur->hasRoleIn($departement->id, DepartmentRole::Technician, DepartmentRole::Director))->toBeTrue()
        ->and($directeur->hasRoleIn($departement->id, DepartmentRole::Technician))->toBeFalse()
        ->and($directeur->hasRoleIn($departement->id, DepartmentRole::User, DepartmentRole::Technician))->toBeFalse();
});

it('charge tous les droits en une seule requete', function () {
    $departementA = Department::factory()->create();
    $departementB = Department::factory()->create();

    $utilisateur = User::factory()
        ->withRole($departementA, DepartmentRole::Technician, 1)
        ->withRole($departementB, DepartmentRole::Director)
        ->create();

    // Instance neuve : la factory a deja rempli le cache via `refreshAuthorisations`,
    // on veut donc mesurer une premiere lecture, pas la seconde.
    $utilisateur = User::find($utilisateur->id);

    // Sans ce cache, une grille de neuf cartes declencherait neuf requetes de plus.
    $requetes = 0;
    DB::listen(function () use (&$requetes) {
        $requetes++;
    });

    $utilisateur->loadAuthorisations();
    $utilisateur->roleIn($departementA->id);
    $utilisateur->roleIn($departementB->id);
    $utilisateur->canHandleIn($departementA->id);

    expect($requetes)->toBe(1);
});

it('ne recharge pas les droits si on les a deja charges', function () {
    $departement = Department::factory()->create();

    $utilisateur = User::factory()->withRole($departement, DepartmentRole::Technician, 1)->create();

    $requetes = 0;
    DB::listen(function () use (&$requetes) {
        $requetes++;
    });

    $utilisateur->loadAuthorisations();
    $utilisateur->loadAuthorisations();

    expect($requetes)->toBe(0);
});

it('ne voit que ses departements, sauf pour l admin general', function () {
    $visible = Department::factory()->create();
    $autre = Department::factory()->create();

    $utilisateur = User::factory()->withRole($visible, DepartmentRole::User)->create();
    $admin = User::factory()->superAdmin()->create();

    expect(Department::visibleTo($utilisateur)->pluck('id')->all())->toBe([$visible->id])
        ->and(Department::visibleTo($admin)->pluck('id')->all())
        ->toEqualCanonicalizing([$visible->id, $autre->id]);
});
