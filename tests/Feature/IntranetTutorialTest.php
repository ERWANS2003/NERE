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
        ->assertSee('intranet-quick-actions', false)
        ->assertSee('intranet-services-section', false)
        ->assertSee('intranet-search-trigger', false)
        ->assertSee('intranet-tour-spotlight', false)
        ->assertSee('Ne plus afficher le guide')
        ->assertSee('Revoir le tutoriel')
        ->assertSee(chr(92).'u0022autoOpen'.chr(92).'u0022:true', false);

    $this->getJson(route('intranet.help.guide', 'decouverte'))
        ->assertOk()
        ->assertJsonPath('steps.0.title', 'Bonjour Awa, bienvenue sur votre intranet');

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

it('adapte les guides au rôle de chaque utilisateur', function () {
    $ids = fn (User $user) => array_keys(\App\Support\IntranetGuides::for($user));

    $it = Department::factory()->create(['code' => 'IT', 'is_active' => true]);
    $employee = User::factory()->create();
    $head = User::factory()->withRole(Department::factory()->create(), DepartmentRole::Director)->create();
    $technician = User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
    $admin = User::factory()->superAdmin()->create();

    expect($ids($employee))->toContain('decouverte', 'conges', 'demande', 'ticket', 'annuaire', 'notifications')
        ->not->toContain('approbations', 'annonce', 'traitement', 'tickets-support', 'menu-gestion')
        ->and($ids($head))->toContain('approbations', 'annonce', 'traitement')
        ->not->toContain('tickets-support')
        ->and($ids($technician))->toContain('tickets-support', 'traitement')
        ->not->toContain('annonce')
        ->and($ids($admin))->toContain('approbations', 'annonce', 'traitement', 'tickets-support', 'menu-gestion');

    // Chaque étape vise une page réelle ; chaque guide commence par une adresse.
    foreach (\App\Support\IntranetGuides::for($admin) as $guide) {
        expect($guide['steps'][0]['url'] ?? null)->not->toBeNull("Guide {$guide['id']} sans adresse de départ");
        foreach ($guide['steps'] as $step) {
            foreach ((array) $step['route'] as $route) {
                expect(\Illuminate\Support\Facades\Route::has($route))->toBeTrue("Route inconnue {$route}");
            }
        }
    }
});

it('sert les étapes d’un guide seulement s’il correspond au rôle', function () {
    $employee = User::factory()->create();

    $this->actingAs($employee)->getJson(route('intranet.help.guide', 'conges'))
        ->assertOk()
        ->assertJsonPath('steps.0.route', 'intranet.time-off.index');
    $this->actingAs($employee)->getJson(route('intranet.help.guide', 'approbations'))->assertNotFound();
});

it('affiche le centre d’aide avec les guides du rôle et leur version écrite', function () {
    $employee = User::factory()->create();

    $this->actingAs($employee)->get(route('intranet.help'))
        ->assertOk()
        ->assertSee('Centre d’aide')
        ->assertSee('Demander un congé')
        ->assertSee('Le type de congé')
        ->assertSee('Questions fréquentes')
        ->assertDontSee('Valider les congés de mon équipe');

    $this->get(route('intranet.time-off.create'))
        ->assertOk()
        ->assertSee('data-tour="conges-dates"', false)
        ->assertDontSee('Le type de congé');
});
