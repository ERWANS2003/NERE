<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('updates department memberships with required roles and preserves existing roles', function () {
    $existingDepartment = Department::factory()->create();
    $newDepartment = Department::factory()->create();
    $user = User::factory()->create();
    $user->departments()->attach($existingDepartment, [
        'role' => DepartmentRole::Director->value,
        'tech_level' => null,
    ]);

    $systemRole = Role::create([
        'nom' => 'Gestionnaire',
        'slug' => 'gestionnaire',
    ]);
    $user->roles()->attach($systemRole);

    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'matricule' => $user->matricule,
            'actif' => '1',
            'departments_present' => '1',
            'departments' => [$existingDepartment->id, $newDepartment->id],
            'roles_present' => '1',
            'roles' => [$systemRole->id],
        ])
        ->assertRedirect(route('admin.users.show', $user));

    expect($user->fresh()->departments()->findOrFail($existingDepartment->id)->pivot->role)
        ->toBe(DepartmentRole::Director->value)
        ->and($user->fresh()->departments()->findOrFail($newDepartment->id)->pivot->role)
        ->toBe(DepartmentRole::User->value)
        ->and($user->fresh()->roles()->pluck('roles.id')->all())
        ->toBe([$systemRole->id]);

    expect(DB::table('department_user')
        ->where('user_id', $user->id)
        ->count())->toBe(2);
});

it('clears department and system role assignments when all checkboxes are unchecked', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create();
    $user->departments()->attach($department, [
        'role' => DepartmentRole::User->value,
        'tech_level' => null,
    ]);

    $systemRole = Role::create([
        'nom' => 'Gestionnaire',
        'slug' => 'gestionnaire',
    ]);
    $user->roles()->attach($systemRole);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'matricule' => $user->matricule,
            'actif' => '1',
            'departments_present' => '1',
            'roles_present' => '1',
        ])
        ->assertRedirect(route('admin.users.show', $user));

    expect(DB::table('department_user')->where('user_id', $user->id)->count())->toBe(0)
        ->and($user->fresh()->roles()->count())->toBe(0);
});

it('creates department memberships with the default user role', function () {
    $department = Department::factory()->create();

    $response = $this->actingAs(User::factory()->superAdmin()->create())
        ->post(route('admin.users.store'), [
            'name' => 'Nouvel utilisateur',
            'email' => 'nouvel.utilisateur@example.test',
            'matricule' => 'NERE-NEW-USER',
            'password' => 'NereMining!2026',
            'password_confirmation' => 'NereMining!2026',
            'actif' => '1',
            'departments_present' => '1',
            'departments' => [$department->id],
            'roles_present' => '1',
        ]);

    $user = User::where('email', 'nouvel.utilisateur@example.test')->firstOrFail();

    $response->assertRedirect(route('admin.users.show', $user));

    expect($user->departments()->findOrFail($department->id)->pivot->role)
        ->toBe(DepartmentRole::User->value);
});
