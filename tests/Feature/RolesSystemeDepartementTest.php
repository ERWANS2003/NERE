<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\Role;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;

/*
 * Les roles systeme « Chef de departement » et « Technicien de departement »
 * elevent le role de l'utilisateur dans chacun de ses departements.
 */

function membreAvecRoleSysteme(Department $departement, ?string $roleSysteme, string $rolePivot = 'user'): User
{
    $utilisateur = User::factory()->create();
    $utilisateur->departments()->attach($departement->id, [
        'role' => $rolePivot,
        'tech_level' => $rolePivot === 'technician' ? 2 : null,
    ]);

    if ($roleSysteme !== null) {
        $utilisateur->roles()->attach(Role::where('slug', $roleSysteme)->firstOrFail());
    }

    return $utilisateur->refresh();
}

function demandeCongeEnAttente(User $demandeur): TimeOffRequest
{
    $politique = TimeOffPolicy::firstOrCreate(['type' => 'vacation'], [
        'name' => 'Congé annuel',
        'annual_allocation' => 30,
        'carry_over_limit' => 5,
        'requires_approval' => true,
        'advance_notice_days' => 7,
        'is_active' => true,
    ]);

    return TimeOffRequest::create([
        'user_id' => $demandeur->id,
        'policy_id' => $politique->id,
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(12)->toDateString(),
        'total_days' => 3,
        'status' => 'pending',
    ]);
}

it('fait d un chef de departement systeme le directeur de ses departements', function () {
    $departement = Department::factory()->create();
    $autre = Department::factory()->create();
    $chef = membreAvecRoleSysteme($departement, 'chef-departement');

    expect($chef->roleIn($departement->id))->toBe(DepartmentRole::Director)
        ->and($chef->roleIn($autre->id))->toBeNull()
        ->and($chef->isDepartmentHead())->toBeTrue()
        ->and($chef->canValidateIn($departement->id))->toBeTrue()
        ->and($chef->directedDepartments()->modelKeys())->toBe([$departement->id]);

    $this->actingAs($chef)->get(route('intranet.analytics'))->assertOk();
    $this->actingAs($chef)->get(route('intranet.departments.manage.edit', $departement))->assertOk();
    $this->actingAs($chef)->get(route('intranet.departments.manage.edit', $autre))->assertForbidden();
});

it('fait d un technicien systeme un technicien de niveau 1 de ses departements', function () {
    $departement = Department::factory()->create();
    $technicien = membreAvecRoleSysteme($departement, 'technicien-departement');

    expect($technicien->roleIn($departement->id))->toBe(DepartmentRole::Technician)
        ->and($technicien->effectiveTechLevel($departement->id))->toBe(1)
        ->and($technicien->canHandleIn($departement->id))->toBeTrue()
        ->and($technicien->canValidateIn($departement->id))->toBeFalse()
        ->and($technicien->handledDepartmentIds())->toBe([$departement->id]);

    $this->actingAs($technicien)->get(route('intranet.submissions.queue'))->assertOk();
    $this->actingAs($technicien)->get(route('intranet.analytics'))->assertForbidden();
});

it('ne retrograde jamais le role du pivot', function () {
    $departement = Department::factory()->create();
    $directeur = membreAvecRoleSysteme($departement, 'technicien-departement', 'director');
    $technicienN2 = membreAvecRoleSysteme($departement, 'technicien-departement', 'technician');

    expect($directeur->roleIn($departement->id))->toBe(DepartmentRole::Director)
        ->and($technicienN2->effectiveTechLevel($departement->id))->toBe(2);
});

it('laisse un employe sans role systeme simple membre', function () {
    $departement = Department::factory()->create();
    $employe = membreAvecRoleSysteme($departement, 'employe');

    expect($employe->roleIn($departement->id))->toBe(DepartmentRole::User)
        ->and($employe->hasDepartmentProcessingAccess())->toBeFalse();

    $this->actingAs($employe)->get(route('intranet.submissions.queue'))->assertForbidden();
});

it('compte les roles systeme parmi les agents qui traitent un departement', function () {
    $departement = Department::factory()->create();
    $technicien = membreAvecRoleSysteme($departement, 'technicien-departement');
    $technicienPivot = membreAvecRoleSysteme($departement, null, 'technician');
    $employe = membreAvecRoleSysteme($departement, 'employe');
    $horsDepartement = membreAvecRoleSysteme(Department::factory()->create(), 'technicien-departement');

    $agents = User::query()->handlersOf($departement->id)->pluck('id');

    expect($agents)->toContain($technicien->id, $technicienPivot->id)
        ->not->toContain($employe->id)
        ->not->toContain($horsDepartement->id);
});

it('montre l equipe avec le role effectif', function () {
    $departement = Department::factory()->create();
    $chef = membreAvecRoleSysteme($departement, 'chef-departement');
    $chef->update(['name' => 'Awa Chef Systeme']);

    $this->actingAs($chef)
        ->get(route('intranet.departments.show', $departement->code))
        ->assertOk()
        ->assertSeeInOrder(['Awa Chef Systeme', 'Directeur']);
});

it('laisse le chef de departement traiter les conges des membres de son departement', function () {
    $departement = Department::factory()->create();
    $chef = membreAvecRoleSysteme($departement, 'chef-departement');
    $membre = membreAvecRoleSysteme($departement, 'employe');
    $demande = demandeCongeEnAttente($membre);
    $demandeExterne = demandeCongeEnAttente(membreAvecRoleSysteme(Department::factory()->create(), 'employe'));
    $demandePropre = demandeCongeEnAttente($chef);

    $this->actingAs($chef)
        ->get(route('intranet.time-off.approvals'))
        ->assertOk()
        ->assertSee($membre->name)
        ->assertDontSee($demandeExterne->user->name);

    $this->actingAs($chef)
        ->put(route('intranet.time-off.approvals.update', $demandeExterne), ['decision' => 'reject'])
        ->assertForbidden();
    $this->actingAs($chef)
        ->put(route('intranet.time-off.approvals.update', $demandePropre), ['decision' => 'reject'])
        ->assertForbidden();
    $this->actingAs($chef)
        ->put(route('intranet.time-off.approvals.update', $demande), ['decision' => 'reject'])
        ->assertRedirect(route('intranet.time-off.approvals'));

    expect($demande->fresh()->status)->toBe('denied');
});

it('refuse l ecran d approbation a un employe sans responsabilite', function () {
    $employe = membreAvecRoleSysteme(Department::factory()->create(), 'employe');

    $this->actingAs($employe)->get(route('intranet.time-off.approvals'))->assertForbidden();
});

it('affiche dans le menu les liens adaptes au role', function () {
    $departement = Department::factory()->create();
    $chef = membreAvecRoleSysteme($departement, 'chef-departement');
    $employe = membreAvecRoleSysteme($departement, 'employe');

    $this->actingAs($chef)->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('intranet-time-off-link', false)
        ->assertSee('intranet-department-management-link', false)
        ->assertSee('intranet-time-off-approvals-link', false)
        ->assertSee('intranet-queue-link', false);

    $this->actingAs($employe)->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('intranet-time-off-link', false)
        ->assertDontSee('intranet-department-management-link', false)
        ->assertDontSee('intranet-time-off-approvals-link', false)
        ->assertDontSee('intranet-queue-link', false);
});
