<?php

use App\Models\MenuCampaign;
use App\Models\MenuChoice;
use App\Models\MenuDay;
use App\Models\MenuRegistration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleProfileSeeder;
use Illuminate\Database\QueryException;

it('adds a dedicated menu permission and an assignable manager role', function () {
    $this->seed([PermissionSeeder::class, RoleProfileSeeder::class]);

    $permission = Permission::query()->where('slug', 'menu.manage')->firstOrFail();
    $manager = Role::query()->where('slug', 'gestionnaire-menu')->firstOrFail();
    $systemAdmin = Role::query()->where('slug', 'administrateur-systeme')->firstOrFail();
    $employee = Role::query()->where('slug', 'employe')->firstOrFail();
    $groups = PermissionCatalog::group(Permission::all());

    expect($permission->nom)->toBe('Gérer les menus et inscriptions')
        ->and($groups->has('Gestion des menus'))->toBeTrue()
        ->and($manager->permissions->pluck('slug')->all())->toBe(['menu.manage'])
        ->and($systemAdmin->permissions->pluck('slug'))->toContain('menu.manage')
        ->and($employee->permissions->pluck('slug'))->not->toContain('menu.manage');

    $menuManager = User::factory()->create();
    $menuManager->roles()->attach($manager);
    $regularEmployee = User::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    expect($menuManager->hasPermission('menu.manage'))->toBeTrue()
        ->and($regularEmployee->hasPermission('menu.manage'))->toBeFalse()
        ->and($superAdmin->hasPermission('menu.manage'))->toBeTrue();
});

it('relates campaigns, weekdays, registrations and daily menu choices', function () {
    $this->seed([PermissionSeeder::class, RoleProfileSeeder::class]);
    $creator = User::factory()->superAdmin()->create();
    $employee = User::factory()->create([
        'guid' => 'menu-test-guid',
        'domain' => 'default',
    ]);

    $campaign = MenuCampaign::create([
        'title' => 'Menus du 19 au 23 octobre',
        'link_token' => str_repeat('a', 64),
        'starts_on' => '2026-10-19',
        'ends_on' => '2026-10-23',
        'registration_closes_at' => '2026-10-18 18:00:00',
        'status' => 'open',
        'created_by' => $creator->id,
    ]);

    $monday = $campaign->days()->create([
        'date' => '2026-10-19',
        'menu_one' => 'Haricot au couscous de maïs + poisson',
        'menu_two' => 'Pomme forestière + merguez sautée',
        'dessert' => 'Jus',
    ]);
    $registration = $campaign->registrations()->create([
        'user_id' => $employee->id,
        'employee_name' => $employee->name,
        'employee_identifier' => $employee->guid,
        'department' => 'Finances',
    ]);
    $choice = $registration->choices()->create([
        'menu_day_id' => $monday->id,
        'selected_menu' => 2,
    ]);

    expect($campaign->fresh()->days)->toHaveCount(1)
        ->and($campaign->fresh()->registrations)->toHaveCount(1)
        ->and($campaign->creator->is($creator))->toBeTrue()
        ->and($monday->fresh()->choices)->toHaveCount(1)
        ->and($registration->fresh()->user->is($employee))->toBeTrue()
        ->and($registration->fresh()->choices->first()->day->menu_two)->toBe('Pomme forestière + merguez sautée')
        ->and($choice->selected_menu)->toBe(2)
        ->and($campaign->starts_on->toDateString())->toBe('2026-10-19');

    $this->assertDatabaseHas('menu_registrations', [
        'campaign_id' => $campaign->id,
        'employee_identifier' => 'menu-test-guid',
        'department' => 'Finances',
    ]);
});

it('enforces one menu per campaign day, one registration per employee and one choice per day', function () {
    $campaign = MenuCampaign::create([
        'title' => 'Semaine de test',
        'link_token' => str_repeat('b', 64),
        'starts_on' => '2026-10-19',
        'ends_on' => '2026-10-23',
        'status' => 'draft',
    ]);
    $day = $campaign->days()->create([
        'date' => '2026-10-19',
        'menu_one' => 'Menu un',
        'menu_two' => 'Menu deux',
        'dessert' => 'Fruit',
    ]);
    $registration = $campaign->registrations()->create([
        'employee_name' => 'Employé de test',
        'employee_identifier' => 'AD-0001',
        'department' => 'Administration',
    ]);
    $registration->choices()->create([
        'menu_day_id' => $day->id,
        'selected_menu' => 1,
    ]);

    expect(fn () => MenuDay::create([
        'campaign_id' => $campaign->id,
        'date' => '2026-10-19',
        'menu_one' => 'Autre menu un',
        'menu_two' => 'Autre menu deux',
        'dessert' => 'Fruit',
    ]))->toThrow(QueryException::class)
        ->and(fn () => MenuRegistration::create([
            'campaign_id' => $campaign->id,
            'employee_name' => 'Autre nom',
            'employee_identifier' => 'AD-0001',
            'department' => 'Administration',
        ]))->toThrow(QueryException::class)
        ->and(fn () => MenuChoice::create([
            'registration_id' => $registration->id,
            'menu_day_id' => $day->id,
            'selected_menu' => 2,
        ]))->toThrow(QueryException::class);
});

it('cascades campaign removal to menus, registrations and choices', function () {
    $campaign = MenuCampaign::create([
        'title' => 'Campagne à supprimer',
        'link_token' => str_repeat('c', 64),
        'starts_on' => '2026-10-19',
        'ends_on' => '2026-10-23',
        'status' => 'draft',
    ]);
    $day = $campaign->days()->create([
        'date' => '2026-10-19',
        'menu_one' => 'Menu un',
        'menu_two' => 'Menu deux',
        'dessert' => 'Fruit',
    ]);
    $registration = $campaign->registrations()->create([
        'employee_name' => 'Employé de test',
        'employee_identifier' => 'AD-0002',
        'department' => 'Administration',
    ]);
    $registration->choices()->create([
        'menu_day_id' => $day->id,
        'selected_menu' => 1,
    ]);

    $campaign->delete();

    $this->assertDatabaseMissing('menu_days', ['id' => $day->id])
        ->assertDatabaseMissing('menu_registrations', ['id' => $registration->id])
        ->assertDatabaseMissing('menu_choices', ['registration_id' => $registration->id]);
});
