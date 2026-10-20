<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Models\User;

function createAnalyticsSubmission(Department $department, string $status = 'pending'): FormSubmission
{
    $form = DepartmentForm::create([
        'department_id' => $department->id,
        'name' => 'Demande '.$department->name,
        'form_schema' => ['fields' => []],
        'is_active' => true,
    ]);

    return FormSubmission::create([
        'form_id' => $form->id,
        'user_id' => User::factory()->create()->id,
        'data' => ['details' => 'Test'],
        'status' => $status,
        'reviewed_at' => $status === 'pending' ? null : now(),
    ]);
}

it('restricts analytics access to system administrators and department heads', function () {
    $ordinaryUser = User::factory()->create();
    $this->actingAs($ordinaryUser)
        ->get(route('intranet.analytics'))
        ->assertForbidden();

    $this->get(route('intranet.dashboard'))->assertDontSee(route('intranet.analytics'));

    $this->actingAs(User::factory()->withRole(
        Department::factory()->create(),
        DepartmentRole::Technician,
        1
    )->create())
        ->get(route('intranet.analytics'))
        ->assertForbidden();
});

it('scopes every department head metric and chart to departments they lead', function () {
    $managedDepartment = Department::factory()->create(['name' => 'Département contrôlé']);
    $otherDepartment = Department::factory()->create(['name' => 'Département privé']);
    $head = User::factory()->withRole($managedDepartment, DepartmentRole::Director)->create();
    $member = User::factory()->create();
    $member->departments()->attach($managedDepartment, ['role' => DepartmentRole::User]);
    $privateMember = User::factory()->create();
    $privateMember->departments()->attach($otherDepartment, ['role' => DepartmentRole::User]);

    createAnalyticsSubmission($managedDepartment);
    createAnalyticsSubmission($managedDepartment, 'approved');
    createAnalyticsSubmission($otherDepartment);
    createAnalyticsSubmission($otherDepartment, 'rejected');

    $response = $this->actingAs($head)
        ->get(route('intranet.analytics', ['periode' => 30]))
        ->assertOk()
        ->assertViewHas('isAdmin', false)
        ->assertViewHas('overview', null)
        ->assertSee('Statistiques de : '.$managedDepartment->name)
        ->assertDontSee($otherDepartment->name);

    $kpis = $response->viewData('kpis');
    $charts = $response->viewData('charts');

    expect($kpis['demandes']['valeur'])->toBe(2)
        ->and($kpis['attente']['valeur'])->toBe(1)
        ->and($kpis['traitement']['valeur'])->toBe('50 %')
        ->and($charts['parDepartement']['labels'])->toBe([$managedDepartment->name])
        ->and($charts['parStatut']['data'])->toBe([1, 0, 1])
        ->and($charts['effectifs']['labels'])->toBe([$managedDepartment->name])
        ->and($charts['effectifs']['data'])->toBe([2]);
});

it('shows company-wide summaries and all department analytics to system administrators', function () {
    $firstDepartment = Department::factory()->create(['name' => 'Département Alpha']);
    $secondDepartment = Department::factory()->create(['name' => 'Département Bravo']);
    $admin = User::factory()->superAdmin()->create();

    createAnalyticsSubmission($firstDepartment);
    createAnalyticsSubmission($secondDepartment, 'approved');

    $response = $this->actingAs($admin)
        ->get(route('intranet.analytics'))
        ->assertOk()
        ->assertViewHas('isAdmin', true)
        ->assertSee('Vue d’ensemble de l’entreprise')
        ->assertSee($firstDepartment->name)
        ->assertSee($secondDepartment->name);

    $charts = $response->viewData('charts');
    $overview = $response->viewData('overview');

    expect($charts['parDepartement']['labels'])->toContain($firstDepartment->name, $secondDepartment->name)
        ->and($charts['effectifs']['labels'])->toContain($firstDepartment->name, $secondDepartment->name)
        ->and($overview['demandes'])->toBe(2)
        ->and($overview['departements'])->toBe(2);
});
