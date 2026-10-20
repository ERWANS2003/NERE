<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleProfileSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

it('creates reusable role profiles with permissions matched to their responsibilities', function () {
    $this->seed(RoleProfileSeeder::class);

    $employee = Role::query()->where('slug', 'employe')->firstOrFail();
    $technician = Role::query()->where('slug', 'technicien-departement')->firstOrFail();
    $departmentHead = Role::query()->where('slug', 'chef-departement')->firstOrFail();
    $systemAdmin = Role::query()->where('slug', 'administrateur-systeme')->firstOrFail();

    expect($employee->permissions->pluck('slug')->all())
        ->toContain('tickets.create', 'submissions.view')
        ->not->toContain('users.manage', 'submissions.review')
        ->and($technician->permissions->pluck('slug')->all())
        ->toContain('tickets.update')
        ->not->toContain('submissions.review', 'roles.manage')
        ->and($departmentHead->permissions->pluck('slug')->all())
        ->toContain('analytics.view', 'submissions.review', 'calendar.manage')
        ->not->toContain('users.manage', 'roles.manage')
        ->and($systemAdmin->permissions->count())
        ->toBe(Permission::query()->count());

    $employee->permissions()->attach(Permission::query()->where('slug', 'users.manage')->firstOrFail());
    $this->seed(RoleProfileSeeder::class);

    expect($employee->fresh()->permissions->pluck('slug')->all())->toContain('users.manage');
});

it('adds the missing permission description column before populating the catalogue', function () {
    Schema::table('permissions', function (Blueprint $table): void {
        $table->dropColumn('description');
    });

    $migration = require database_path('migrations/2026_10_20_101500_populate_permission_catalog.php');
    $migration->up();

    expect(Schema::hasColumn('permissions', 'description'))->toBeTrue()
        ->and(Permission::query()->count())->toBeGreaterThanOrEqual(20);
});

it('adds missing permission pivot timestamps required by the role list', function () {
    Schema::table('permission_role', function (Blueprint $table): void {
        $table->dropColumn(['created_at', 'updated_at']);
    });

    $admin = User::factory()->superAdmin()->create();
    $role = Role::create([
        'nom' => 'Gestionnaire sans timestamps',
        'slug' => 'gestionnaire-sans-timestamps',
    ]);
    $permission = Permission::query()->firstOrFail();

    DB::table('permission_role')->insert([
        'permission_id' => $permission->id,
        'role_id' => $role->id,
    ]);

    $migration = require database_path('migrations/2026_10_20_115500_add_missing_timestamps_to_permission_role_table.php');
    $migration->up();

    expect(Schema::hasColumn('permission_role', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('permission_role', 'updated_at'))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Gestionnaire sans timestamps')
        ->assertSee('1 permission(s)');
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
