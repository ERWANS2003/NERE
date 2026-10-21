<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\DepartmentService;
use App\Models\User;
use Database\Seeders\DepartmentFormSeeder;
use Database\Seeders\DepartmentServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function departmentServicePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Service de test',
        'description' => 'Description du service',
        'icon' => 'wrench',
        'link_type' => 'page',
        'link_url' => '/intranet/tickets',
        'is_active' => '1',
    ], $overrides);
}

function createDepartmentService(Department $department, array $overrides = []): DepartmentService
{
    return DepartmentService::create(array_merge([
        'department_id' => $department->id,
        'name' => 'Service existant',
        'description' => 'Description',
        'link_type' => 'page',
        'link_url' => '/intranet/tickets',
        'order' => 4,
        'is_active' => true,
    ], $overrides));
}

it('allows department directors to manage services only in their department', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $service = createDepartmentService($department);
    $otherService = createDepartmentService($otherDepartment, ['name' => 'Autre service']);
    $director = User::factory()->withRole($department, DepartmentRole::Director)->create();

    $this->actingAs($director)
        ->get(route('intranet.departments.manage.services.index', $department))
        ->assertOk()
        ->assertSee($service->name);

    $this->get(route('intranet.departments.manage.services.index', $otherDepartment))
        ->assertForbidden();

    $this->put(route('intranet.departments.manage.services.update', [$department, $otherService]), departmentServicePayload())
        ->assertNotFound();

    expect($otherService->fresh()->name)->toBe('Autre service');
});

it('allows system administrators but denies service management to regular users', function () {
    $department = Department::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('intranet.departments.manage.services.index', $department))
        ->assertForbidden();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('intranet.departments.manage.services.index', $department))
        ->assertOk();
});

it('validates same-department published forms and safe destinations', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $director = User::factory()->withRole($department, DepartmentRole::Director)->create();
    $form = DepartmentForm::create([
        'department_id' => $department->id,
        'name' => 'Formulaire publié',
        'form_schema' => ['fields' => []],
        'is_active' => true,
        'order' => 0,
    ]);
    $otherForm = DepartmentForm::create([
        'department_id' => $otherDepartment->id,
        'name' => 'Formulaire externe au département',
        'form_schema' => ['fields' => []],
        'is_active' => true,
        'order' => 0,
    ]);

    $this->actingAs($director)
        ->post(route('intranet.departments.manage.services.store', $department), departmentServicePayload([
            'link_type' => 'form',
            'form_id' => $otherForm->id,
        ]))
        ->assertSessionHasErrors('form_id');

    $this->post(route('intranet.departments.manage.services.store', $department), departmentServicePayload([
        'link_type' => 'form',
        'form_id' => $form->id,
    ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $service = DepartmentService::where('department_id', $department->id)
        ->where('name', 'Service de test')
        ->firstOrFail();
    expect($service->link_url)->toBe(route('intranet.forms.show', $form, absolute: false));

    $this->post(route('intranet.departments.manage.services.store', $department), departmentServicePayload([
        'name' => 'Destination externe non sécurisée',
        'link_type' => 'external',
        'link_url' => 'http://example.com',
    ]))
        ->assertSessionHasErrors('link_url');

    $this->post(route('intranet.departments.manage.services.store', $department), departmentServicePayload([
        'name' => 'Chemin non autorisé',
        'link_url' => '/admin',
    ]))
        ->assertSessionHasErrors('link_url');

    $this->post(route('intranet.departments.manage.services.store', $department), departmentServicePayload([
        'name' => 'Destination sécurisée',
        'link_type' => 'external',
        'link_url' => 'https://example.com/help',
    ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(DepartmentService::where('name', 'Destination sécurisée')->firstOrFail()->destinationUrl())
        ->toBe('https://example.com/help');
});

it('requires a destination for active services and deactivates services through DELETE', function () {
    $department = Department::factory()->create();
    $director = User::factory()->withRole($department, DepartmentRole::Director)->create();
    $service = createDepartmentService($department);

    $this->actingAs($director)
        ->post(route('intranet.departments.manage.services.store', $department), departmentServicePayload([
            'name' => 'Service sans destination',
            'link_url' => '',
        ]))
        ->assertSessionHasErrors('link_url');

    $this->put(
        route('intranet.departments.manage.services.update', [$department, $service]),
        departmentServicePayload(['name' => 'Service modifié'])
    )->assertRedirect();

    expect($service->fresh()->order)->toBe(4);

    $this->delete(route('intranet.departments.manage.services.destroy', [$department, $service]))
        ->assertRedirect(route('intranet.departments.manage.services.index', $department))
        ->assertSessionHas('success');

    expect($service->fresh()->is_active)->toBeFalse();
});

it('does not let seeders overwrite configured services or form schemas', function () {
    $it = Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);
    $service = createDepartmentService($it, [
        'name' => 'Support IT',
        'description' => 'Description personnalisée',
        'icon' => 'custom-icon',
        'link_type' => 'external',
        'link_url' => 'https://support.example.com',
        'order' => 9,
        'is_active' => false,
    ]);
    $form = DepartmentForm::create([
        'department_id' => $it->id,
        'name' => "Demande d'accès à une application",
        'description' => 'Formulaire personnalisé',
        'form_schema' => ['fields' => [[
            'key' => 'request',
            'label' => 'Demande personnalisée',
            'type' => 'text',
            'required' => true,
        ]]],
        'is_active' => false,
        'order' => 7,
    ]);

    app(DepartmentServiceSeeder::class)->run();
    app(DepartmentFormSeeder::class)->run();

    expect($service->fresh()->only(['description', 'icon', 'link_type', 'link_url', 'order', 'is_active']))
        ->toBe([
            'description' => 'Description personnalisée',
            'icon' => 'custom-icon',
            'link_type' => 'external',
            'link_url' => 'https://support.example.com',
            'order' => 9,
            'is_active' => false,
        ])
        ->and($form->fresh()->only(['description', 'form_schema', 'is_active', 'order']))
        ->toBe([
            'description' => 'Formulaire personnalisé',
            'form_schema' => $form->form_schema,
            'is_active' => false,
            'order' => 7,
        ]);
});

it('never exposes HTTP or off-site paths as service destinations', function () {
    $department = Department::factory()->create();

    expect(createDepartmentService($department, [
        'link_type' => 'external',
        'link_url' => 'http://example.com',
    ])->destinationUrl())->toBeNull()
        ->and(createDepartmentService($department, [
            'name' => 'Chemin externe',
            'link_type' => 'page',
            'link_url' => '//example.com',
        ])->destinationUrl())->toBeNull();
});
