<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Models\User;

function makeDynamicForm(Department $department, array $fields, bool $active = true): DepartmentForm
{
    return DepartmentForm::create([
        'department_id' => $department->id,
        'name' => 'Demande de test',
        'description' => 'Formulaire de test',
        'form_schema' => ['fields' => $fields],
        'is_active' => $active,
        'order' => 0,
    ]);
}

function validFormPayload(Department $department): array
{
    return [
        'department_id' => $department->id,
        'name' => 'Demande d’intervention',
        'description' => 'Formulaire métier',
        'order' => 1,
        'is_active' => '1',
        'fields' => [
            [
                'key' => 'details',
                'label' => 'Description',
                'type' => 'textarea',
                'required' => '1',
                'help_text' => 'Expliquez votre besoin.',
                'options' => [],
            ],
            [
                'key' => 'priority',
                'label' => 'Priorité',
                'type' => 'select',
                'required' => '0',
                'help_text' => '',
                'options' => [
                    ['value' => 'normal', 'label' => 'Normale'],
                    ['value' => 'urgent', 'label' => 'Urgente'],
                ],
            ],
        ],
    ];
}

it('lets department heads manage forms only in their own department', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();

    $this->actingAs($head)
        ->get(route('intranet.forms.create'))
        ->assertOk()
        ->assertSee($department->name);

    $this->post(route('intranet.forms.store'), validFormPayload($department))
        ->assertRedirect()
        ->assertSessionHas('success');

    $created = DepartmentForm::where('name', 'Demande d’intervention')->firstOrFail();

    expect($created->form_schema['fields'][1]['options'])->toBe([
        ['value' => 'normal', 'label' => 'Normale'],
        ['value' => 'urgent', 'label' => 'Urgente'],
    ]);

    $this->get(route('intranet.forms.edit', $created))->assertOk();

    $this->get(route('intranet.forms.edit', makeDynamicForm($otherDepartment, [])))
        ->assertForbidden();

    $payload = validFormPayload($otherDepartment);
    $this->post(route('intranet.forms.store'), $payload)->assertForbidden();
});

it('lets system administrators manage forms and denies form creation to regular users', function () {
    $department = Department::factory()->create();
    $form = makeDynamicForm($department, [], active: false);

    $this->actingAs(User::factory()->create())
        ->get(route('intranet.forms.create'))
        ->assertForbidden();

    $this->get(route('intranet.forms.index'))
        ->assertOk()
        ->assertDontSee($form->name);

    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin)
        ->get(route('intranet.forms.index'))
        ->assertOk()
        ->assertSee($form->name)
        ->assertSee('Créer un formulaire');

    $this->get(route('intranet.forms.edit', $form))->assertOk();
});

it('validates select options and keeps only schema fields in submitted data', function () {
    $department = Department::factory()->create();
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();
    $payload = validFormPayload($department);
    $payload['fields'][1]['options'] = [];

    $this->actingAs($head)
        ->from(route('intranet.forms.create'))
        ->post(route('intranet.forms.store'), $payload)
        ->assertSessionHasErrors('fields.1.options');

    $form = makeDynamicForm($department, [
        [
            'key' => 'details',
            'label' => 'Description',
            'type' => 'textarea',
            'required' => true,
            'options' => [],
        ],
        [
            'key' => 'priority',
            'label' => 'Priorité',
            'type' => 'select',
            'required' => true,
            'options' => [
                ['value' => 'normal', 'label' => 'Normale'],
                ['value' => 'urgent', 'label' => 'Urgente'],
            ],
        ],
    ]);
    $requester = User::factory()->create();

    $this->actingAs($requester)
        ->post(route('intranet.submissions.store', $form), [
            'data' => ['priority' => 'unknown'],
        ])
        ->assertSessionHasErrors(['data.details', 'data.priority']);

    $this->post(route('intranet.submissions.store', $form), [
        'data' => [
            'details' => 'Intervention nécessaire',
            'priority' => 'urgent',
            'is_admin' => '1',
        ],
    ])->assertRedirect();
    expect(FormSubmission::latest('id')->firstOrFail()->data)->toBe([
        'details' => 'Intervention nécessaire',
        'priority' => 'urgent',
    ]);

    $this->post(route('intranet.submissions.store', $form), [
        'data' => [
            'details' => 'Intervention nécessaire',
            'priority' => 'urgent',
        ],
    ])->assertRedirect();

    $submission = FormSubmission::latest('id')->firstOrFail();
    expect($submission->data)->toBe([
        'details' => 'Intervention nécessaire',
        'priority' => 'urgent',
    ]);
});

it('archives a form that has submissions instead of deleting its history', function () {
    $department = Department::factory()->create();
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();
    $form = makeDynamicForm($department, []);
    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'user_id' => User::factory()->create()->id,
        'data' => ['details' => 'Historique à conserver'],
        'status' => 'pending',
    ]);

    $this->actingAs($head)
        ->delete(route('intranet.forms.destroy', $form))
        ->assertRedirect(route('intranet.forms.index'))
        ->assertSessionHas('success');

    expect($form->fresh()->is_active)->toBeFalse()
        ->and($submission->fresh())->not->toBeNull();
});

it('provides a usable generic field for legacy forms without a schema', function () {
    $department = Department::factory()->create();
    $form = makeDynamicForm($department, []);

    $this->actingAs(User::factory()->create())
        ->get(route('intranet.forms.show', $form))
        ->assertOk()
        ->assertSee('Décrivez votre demande');

    $this->post(route('intranet.submissions.store', $form), [
        'data' => ['details' => 'Demande décrite'],
    ])->assertRedirect();

    expect(FormSubmission::firstOrFail()->data)->toBe(['details' => 'Demande décrite']);
});
