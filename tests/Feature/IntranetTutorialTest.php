<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use App\Support\IntranetTutorial;

it('affiche le tutoriel aux nouveaux utilisateurs et permet de le terminer', function () {
    $user = User::factory()->create(['name' => 'Awa Ouédraogo']);

    $this->actingAs($user)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('intranetTutorial(', false)
        ->assertSee('Bonjour Awa, bienvenue sur votre intranet')
        ->assertSee('intranet-quick-actions', false)
        ->assertSee('intranet-services-section', false)
        ->assertSee('intranet-search-trigger', false)
        ->assertSee('intranet-tour-spotlight', false)
        ->assertSee('Ne plus afficher le guide')
        ->assertSee('Revoir le tutoriel')
        ->assertSee(chr(92).'u0022autoOpen'.chr(92).'u0022:true', false);

    $this->post(route('intranet.tutorial.complete'))
        ->assertRedirect()
        ->assertSessionHas('status', 'Tutoriel terminé.');

    expect($user->fresh()->tutorial_completed_at)->not->toBeNull();

    $this->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee(chr(92).'u0022autoOpen'.chr(92).'u0022:false', false);
});

it('valide le tutoriel sans rechargement quand la requête attend du JSON', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('intranet.tutorial.complete'))
        ->assertOk()
        ->assertJsonStructure(['completed_at']);

    expect($user->fresh()->tutorial_completed_at)->not->toBeNull();
});

it('adapte les étapes aux accès de l’utilisateur', function () {
    $ids = fn (User $user) => array_column(IntranetTutorial::stepsFor($user), 'id');

    $employee = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $head = User::factory()->withRole(Department::factory()->create(), DepartmentRole::Director)->create();

    expect($ids($employee))->not->toContain('pilotage')
        ->and($ids($employee))->toContain('bienvenue', 'services', 'recherche', 'profil', 'demarrage')
        ->and($ids($admin))->toContain('pilotage')
        ->and($ids($head))->toContain('pilotage');

    $adminPilotage = collect(IntranetTutorial::stepsFor($admin))->firstWhere('id', 'pilotage');
    expect($adminPilotage['description'])->toContain('Administration');

    $finalStep = collect(IntranetTutorial::stepsFor($employee))->last();
    expect($finalStep['checklist'])->not->toBeEmpty()
        ->and($finalStep['checklist'][0]['url'])->toBe(route('profil.edit'));
});

it('permet de relancer le tutoriel depuis le menu du profil', function () {
    $user = User::factory()->create([
        'tutorial_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Revoir le tutoriel')
        ->assertSee('open-intranet-tutorial');
});

it('réserve la validation du tutoriel aux utilisateurs connectés', function () {
    $this->post(route('intranet.tutorial.complete'))
        ->assertRedirect('/connexion');
});
