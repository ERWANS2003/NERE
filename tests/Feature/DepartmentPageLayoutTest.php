<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentService;
use App\Models\User;

function addService(Department $department, string $name, int $order): DepartmentService
{
    return DepartmentService::create([
        'department_id' => $department->id,
        'name' => $name,
        'description' => 'Description de '.$name,
        'link_type' => 'page',
        'link_url' => '/intranet/tickets',
        'order' => $order,
        'is_active' => true,
    ]);
}

it('highlights a single service with guidance and contacts', function () {
    $department = Department::factory()->create(['code' => 'IT', 'is_active' => true, 'phone' => '+226 25 00 00 00', 'location' => 'Bâtiment administratif']);
    addService($department, 'Support IT', 1);
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create(['name' => 'Chef Informatique']);

    $this->actingAs($head)->get(route('intranet.departments.show', 'it'))
        ->assertOk()
        ->assertSee('Le service du département')
        ->assertSee('Ouvrir le service')
        ->assertSee('Comment ça marche')
        ->assertSee('Suivre mes tickets')
        ->assertSee('Contacter le département')
        ->assertSee('Chef Informatique')
        ->assertSee('+226 25 00 00 00')
        ->assertSee('Voir toute l’équipe');
});

it('keeps the grid when a department offers several services', function () {
    $department = Department::factory()->create(['code' => 'RH', 'is_active' => true]);
    foreach (['Attestation', 'Formations', 'Avance sur salaire'] as $i => $name) {
        addService($department, $name, $i);
    }

    $this->actingAs(User::factory()->create())->get(route('intranet.departments.show', 'rh'))
        ->assertOk()
        ->assertSee('Avance sur salaire')
        ->assertDontSee('Comment ça marche')
        ->assertDontSee('Ouvrir le service');
});
