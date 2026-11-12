<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Models\User;

function suppliesForm(): DepartmentForm
{
    return DepartmentForm::create([
        'department_id' => Department::factory()->create(['is_active' => true])->id,
        'name' => 'Fournitures de bureau',
        'form_schema' => ['fields' => [
            ['key' => 'justification', 'label' => 'Service et justification', 'type' => 'textarea', 'required' => true, 'options' => []],
            ['key' => 'fournitures', 'label' => 'Fournitures demandées', 'type' => 'items', 'required' => true, 'options' => [
                ['value' => 'Ramette de papier A4', 'label' => 'Ramette de papier A4'],
                ['value' => 'Stylo à bille bleu', 'label' => 'Stylo à bille bleu'],
            ]],
        ]],
        'is_active' => true,
        'order' => 0,
    ]);
}

it('accepts several items with their quantity in one request', function () {
    $form = suppliesForm();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('intranet.forms.show', $form))
        ->assertOk()
        ->assertSeeInOrder(['Service et justification', 'Fournitures demandées'])
        ->assertSee('Ajouter un article')
        ->assertSee('<option value="Ramette de papier A4">', false);

    $this->post(route('intranet.submissions.store', $form), ['data' => [
        'justification' => 'Service Finances : rentrée des dossiers.',
        'fournitures' => [
            ['article' => 'Ramette de papier A4', 'quantite' => '5'],
            ['article' => '  ', 'quantite' => '1'],
            ['article' => 'Classeur à levier', 'quantite' => '12'],
        ],
    ]])->assertRedirect();

    $submission = FormSubmission::latest('id')->firstOrFail();
    expect($submission->data['fournitures'])->toBe([
        ['article' => 'Ramette de papier A4', 'quantite' => '5'],
        ['article' => 'Classeur à levier', 'quantite' => '12'],
    ]);

    $this->get(route('intranet.submissions.show', $submission))
        ->assertOk()
        ->assertSeeInOrder(['Article', 'Quantité', 'Ramette de papier A4', '5', 'Classeur à levier', '12']);
});

it('rejects an empty list or an invalid quantity', function () {
    $form = suppliesForm();
    $this->actingAs(User::factory()->create());

    $this->post(route('intranet.submissions.store', $form), ['data' => [
        'justification' => 'Besoin',
        'fournitures' => [['article' => '', 'quantite' => '1']],
    ]])->assertSessionHasErrors('data.fournitures');

    $this->post(route('intranet.submissions.store', $form), ['data' => [
        'justification' => 'Besoin',
        'fournitures' => [['article' => 'Stylo', 'quantite' => '0']],
    ]])->assertSessionHasErrors('data.fournitures.0.quantite');

    expect(FormSubmission::count())->toBe(0);
});

it('lets form editors create an item list field with proposed articles', function () {
    $department = Department::factory()->create(['is_active' => true]);
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();

    $this->actingAs($head)->post(route('intranet.forms.store'), [
        'department_id' => $department->id,
        'name' => 'Commande',
        'is_active' => '1',
        'fields' => [[
            'key' => 'articles', 'label' => 'Articles', 'type' => 'items', 'required' => '1', 'help_text' => '',
            'options' => [['value' => 'Agrafes', 'label' => 'Agrafes']],
        ]],
    ])->assertRedirect();

    $field = DepartmentForm::where('name', 'Commande')->firstOrFail()->form_schema['fields'][0];
    expect($field['type'])->toBe('items')
        ->and($field['options'])->toBe([['value' => 'Agrafes', 'label' => 'Agrafes']]);
});
