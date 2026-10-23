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

it('edits department membership roles and technician levels from the user edit page', function () {
    $department = Department::factory()->create(['code' => 'IT']);
    $user = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $user))
        ->assertOk()
        ->assertSee('Rôle dans le département')
        ->assertSee('Niveau technicien')
        ->assertSee('name="department_roles['.$department->id.']"', false)
        ->assertSee('name="department_levels['.$department->id.']"', false);

    $this->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'matricule' => $user->matricule,
        'actif' => '1',
        'departments_present' => '1',
        'departments' => [$department->id],
        'department_roles' => [$department->id => DepartmentRole::Technician->value],
    ])->assertSessionHasErrors('department_levels.'.$department->id);

    $this->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'matricule' => $user->matricule,
        'actif' => '1',
        'departments_present' => '1',
        'departments' => [$department->id],
        'department_roles' => [$department->id => DepartmentRole::Technician->value],
        'department_levels' => [$department->id => 2],
    ])->assertRedirect(route('admin.users.show', $user));

    $membership = $user->fresh()->departments()->findOrFail($department->id)->pivot;

    expect($membership->role)->toBe(DepartmentRole::Technician->value)
        ->and((int) $membership->tech_level)->toBe(2);
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

it('updates the employee directory title when an administrator changes the post', function () {
    $employee = User::factory()->create([
        'poste' => 'Ancien poste',
        'position_title' => 'Ancien intitulé organisationnel',
    ]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->put(route('admin.users.update', $employee), [
            'name' => $employee->name,
            'email' => $employee->email,
            'matricule' => $employee->matricule,
            'poste' => 'Responsable des opérations',
            'actif' => '1',
        ])
        ->assertRedirect(route('admin.users.show', $employee));

    $employee->refresh();

    expect($employee->poste)->toBe('Responsable des opérations')
        ->and($employee->position_title)->toBe('Responsable des opérations')
        ->and($employee->job_title)->toBe('Responsable des opérations');

    $this->get(route('intranet.directory'))
        ->assertOk()
        ->assertSee('Responsable des opérations');
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

it('shows department assignments on the user page and edits them from the edit page', function () {
    $existingDepartment = Department::factory()->create();
    $newDepartment = Department::factory()->create();
    $inactiveDepartment = Department::factory()->inactive()->create();
    $employee = User::factory()->create();
    $employee->departments()->attach($existingDepartment, [
        'role' => DepartmentRole::Technician->value,
        'tech_level' => 2,
    ]);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.show', $employee))
        ->assertOk()
        ->assertSee('Modifier les départements')
        ->assertDontSee('Affecter à un département')
        ->assertSee($existingDepartment->name);

    $this->get(route('admin.users.edit', $employee))
        ->assertOk()
        ->assertSee($newDepartment->name)
        ->assertDontSee($inactiveDepartment->name);

    $this->post(route('admin.users.departments.assign', $employee), [
        'department_id' => $newDepartment->id,
        'role' => DepartmentRole::Director->value,
    ])
        ->assertRedirect(route('admin.users.show', $employee))
        ->assertSessionHas('success');

    expect($employee->fresh()->departments()->findOrFail($existingDepartment->id)->pivot->role)
        ->toBe(DepartmentRole::Technician->value)
        ->and($employee->fresh()->departments()->findOrFail($existingDepartment->id)->pivot->tech_level)
        ->toBe(2)
        ->and($employee->fresh()->departments()->findOrFail($newDepartment->id)->pivot->role)
        ->toBe(DepartmentRole::Director->value);
});

it('validates the legacy quick-assignment endpoint technician level', function () {
    $department = Department::factory()->create(['code' => 'IT']);
    $employee = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $employee))
        ->assertOk()
        ->assertSee('Niveau technicien')
        ->assertSee('Obligatoire pour un technicien.');

    $this->from(route('admin.users.show', $employee))
        ->post(route('admin.users.departments.assign', $employee), [
            'department_id' => $department->id,
            'role' => DepartmentRole::Technician->value,
        ])
        ->assertRedirect(route('admin.users.show', $employee))
        ->assertSessionHasErrors('tech_level');

    $this->post(route('admin.users.departments.assign', $employee), [
        'department_id' => $department->id,
        'role' => DepartmentRole::Technician->value,
        'tech_level' => 2,
    ])
        ->assertRedirect(route('admin.users.show', $employee))
        ->assertSessionHas('success');

    expect($employee->fresh()->departments()->findOrFail($department->id)->pivot->role)
        ->toBe(DepartmentRole::Technician->value)
        ->and((int) $employee->fresh()->departments()->findOrFail($department->id)->pivot->tech_level)
        ->toBe(2);
});

it('validates quick department assignments and prevents duplicate memberships', function () {
    $department = Department::factory()->create();
    $inactiveDepartment = Department::factory()->inactive()->create();
    $employee = User::factory()->create();
    $employee->departments()->attach($department, ['role' => DepartmentRole::User->value]);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->from(route('admin.users.show', $employee))
        ->post(route('admin.users.departments.assign', $employee), [
            'department_id' => $department->id,
            'role' => DepartmentRole::Technician->value,
            'tech_level' => 1,
        ])
        ->assertRedirect(route('admin.users.show', $employee))
        ->assertSessionHasErrors('department_id');

    $this->post(route('admin.users.departments.assign', $employee), [
        'department_id' => $inactiveDepartment->id,
        'role' => DepartmentRole::User->value,
    ])->assertSessionHasErrors('department_id');

    $this->post(route('admin.users.departments.assign', $employee), [
        'department_id' => $inactiveDepartment->id,
        'role' => 'admin',
    ])->assertSessionHasErrors('role');
});

it('keeps department assignments restricted to system administrators', function () {
    $employee = User::factory()->create();
    $department = Department::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.users.departments.assign', $employee), [
            'department_id' => $department->id,
            'role' => DepartmentRole::User->value,
        ])
        ->assertForbidden();

    expect($employee->fresh()->departments)->toBeEmpty();
});
