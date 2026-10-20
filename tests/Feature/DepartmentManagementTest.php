<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\User;

it('lets department heads view all departments but only edit their own', function () {
    $ownedDepartment = Department::factory()->create(['name' => 'Département du chef']);
    $otherDepartment = Department::factory()->create(['name' => 'Autre département']);
    $head = User::factory()->withRole($ownedDepartment, DepartmentRole::Director)->create();

    $this->actingAs($head)
        ->get(route('intranet.departments.manage.index'))
        ->assertOk()
        ->assertSee('Département du chef')
        ->assertSee('Autre département')
        ->assertDontSee('Nouveau département')
        ->assertDontSee('Supprimer le département')
        ->assertDontSee(route('admin.users.index'));

    $this->get(route('intranet.departments.manage.show', $otherDepartment))
        ->assertOk()
        ->assertSee('Autre département');

    $this->get(route('intranet.departments.manage.edit', $ownedDepartment))
        ->assertOk();

    $this->get(route('intranet.departments.manage.edit', $otherDepartment))
        ->assertForbidden();

    $this->put(route('intranet.departments.manage.update', $ownedDepartment), [
        'name' => 'Département du chef modifié',
        'is_active' => '1',
        'allow_ticket_creation' => '1',
    ])->assertRedirect(route('intranet.departments.manage.show', $ownedDepartment));

    expect($ownedDepartment->fresh()->name)->toBe('Département du chef modifié');

    $this->put(route('intranet.departments.manage.update', $otherDepartment), [
        'name' => 'Modification non autorisée',
        'is_active' => '1',
        'allow_ticket_creation' => '1',
    ])->assertForbidden();
});

it('reserves department creation and deletion for system administrators', function () {
    $department = Department::factory()->create();
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();

    $this->actingAs($head)
        ->get(route('intranet.departments.manage.create'))
        ->assertForbidden();

    $this->post(route('intranet.departments.manage.store'), [
        'name' => 'Création interdite',
        'code' => 'DENY',
    ])->assertForbidden();

    $this->actingAs($head)
        ->delete(route('intranet.departments.manage.destroy', $department))
        ->assertForbidden();
});

it('keeps department management unavailable to regular users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('intranet.departments.manage.index'))
        ->assertForbidden();
});

it('preserves full department management for system administrators', function () {
    $department = Department::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('intranet.departments.manage.create'))
        ->assertOk();

    $this->get(route('intranet.departments.manage.index'))
        ->assertOk()
        ->assertSee('function deleteDepartment', false);

    $this->get(route('intranet.departments.manage.edit', $department))
        ->assertOk();

    $this->delete(route('intranet.departments.manage.destroy', $department))
        ->assertRedirect(route('intranet.departments.manage.index'));

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});
