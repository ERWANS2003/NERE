<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

it('provides a categorized permission catalogue on role forms', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.roles.create'))
        ->assertOk()
        ->assertSee('Intranet')
        ->assertSee('Support informatique')
        ->assertSee('Formulaires et demandes')
        ->assertSee('Administration système')
        ->assertSee('Gérer les utilisateurs')
        ->assertSee('users.manage')
        ->assertSee('Tout sélectionner');

    expect(Permission::query()->count())->toBeGreaterThanOrEqual(20);
});

it('keeps the permission catalogue idempotent', function () {
    $permissionCount = Permission::query()->count();

    $this->seed(PermissionSeeder::class);

    expect(Permission::query()->count())->toBe($permissionCount);
});

it('updates a role with no selected permissions', function () {
    $admin = User::factory()->superAdmin()->create();
    $role = Role::create([
        'nom' => 'Gestionnaire',
        'slug' => 'gestionnaire',
    ]);
    $permission = Permission::query()->where('slug', 'users.manage')->firstOrFail();
    $role->permissions()->attach($permission);

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $role), [
            'nom' => $role->nom,
            'slug' => $role->slug,
            'description' => '',
        ])
        ->assertRedirect(route('admin.roles.show', $role));

    expect($role->fresh()->permissions)->toBeEmpty();
});

it('rejects role slugs that do not match the displayed format', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->from(route('admin.roles.create'))
        ->post(route('admin.roles.store'), [
            'nom' => 'Gestionnaire',
            'slug' => 'Gestionnaire_RH',
        ])
        ->assertSessionHasErrors('slug');
});
