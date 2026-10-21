<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    foreach (['pdf', 'xlsx', 'csv', 'json'] as $format) {
        $this->get(route('intranet.analytics.export.'.$format))
            ->assertForbidden();
    }

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
        ->assertDontSee($otherDepartment->name)
        ->assertSee(route('intranet.analytics.export.xlsx', ['periode' => 30]))
        ->assertSee(route('intranet.analytics.export.csv', ['periode' => 30]))
        ->assertSee(route('intranet.analytics.export.json', ['periode' => 30]));

    $kpis = $response->viewData('kpis');
    $charts = $response->viewData('charts');

    expect($kpis['demandes']['valeur'])->toBe(2)
        ->and($kpis['attente']['valeur'])->toBe(1)
        ->and($kpis['traitement']['valeur'])->toBe('50 %')
        ->and($charts['parDepartement']['labels'])->toBe([$managedDepartment->name])
        ->and($charts['parStatut']['data'])->toBe([1, 0, 1])
        ->and($charts['effectifs']['labels'])->toBe([$managedDepartment->name])
        ->and($charts['effectifs']['data'])->toBe([2]);

    $this->get(route('intranet.analytics.export.csv', ['periode' => 30]))
        ->assertOk()
        ->assertSee($managedDepartment->name)
        ->assertDontSee($otherDepartment->name);

    $this->get(route('intranet.analytics.export.json', ['periode' => 30]))
        ->assertOk()
        ->assertJsonPath('charts.parDepartement.labels', [$managedDepartment->name]);
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
        ->assertSee('data-auto-refresh', false)
        ->assertSee(route('intranet.analytics.export.pdf', ['periode' => 30]))
        ->assertSee(route('intranet.analytics.export.xlsx', ['periode' => 30]))
        ->assertSee(route('intranet.analytics.export.csv', ['periode' => 30]))
        ->assertSee(route('intranet.analytics.export.json', ['periode' => 30]))
        ->assertSee($firstDepartment->name)
        ->assertSee($secondDepartment->name);

    $charts = $response->viewData('charts');
    $overview = $response->viewData('overview');

    expect($charts['parDepartement']['labels'])->toContain($firstDepartment->name, $secondDepartment->name)
        ->and($charts['effectifs']['labels'])->toContain($firstDepartment->name, $secondDepartment->name)
        ->and($overview['demandes'])->toBe(2)
        ->and($overview['departements'])->toBe(2);
});

it('exports analytics as a PDF for authorized users', function () {
    $department = Department::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    createAnalyticsSubmission($department);

    $response = $this->actingAs($admin)
        ->get(route('intranet.analytics.export.pdf', ['periode' => 30]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition');

    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
});

it('exports scoped analytics as CSV, JSON, and Excel', function () {
    $department = Department::factory()->create(['name' => 'Département Alpha']);
    $admin = User::factory()->superAdmin()->create();
    createAnalyticsSubmission($department);

    $this->actingAs($admin)
        ->get(route('intranet.analytics.export.csv', ['periode' => 30]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertHeader('content-disposition')
        ->assertSee('Demandes reçues')
        ->assertSee('Département Alpha');

    $this->get(route('intranet.analytics.export.json', ['periode' => 30]))
        ->assertOk()
        ->assertHeader('content-type', 'application/json')
        ->assertHeader('content-disposition')
        ->assertJsonPath('period_days', 30)
        ->assertJsonPath('kpis.demandes.valeur', 1)
        ->assertJsonPath('charts.parDepartement.labels.0', 'Département Alpha');

    $response = $this->get(route('intranet.analytics.export.xlsx', ['periode' => 30]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertHeader('content-disposition');

    $xlsx = $response->streamedContent();
    expect(substr($xlsx, 0, 2))->toBe('PK');

    $path = tempnam(sys_get_temp_dir(), 'analytics-xlsx-');
    expect($path)->not->toBeFalse();

    try {
        expect(file_put_contents($path, $xlsx))->toBe(strlen($xlsx));
        $workbook = IOFactory::load($path);
        $chartSheet = $workbook->getSheetByName('Graphiques');

        expect($workbook->getSheetNames())->toBe(['Indicateurs', 'Graphiques'])
            ->and($workbook->getSheetByName('Indicateurs')->getCell('C5')->getValue())->toBe(1)
            ->and($chartSheet->getCell('A10')->getValue())->toBe('Demandes par département')
            ->and($chartSheet->getCell('B10')->getValue())->toBe('Département Alpha')
            ->and($chartSheet->getCell('C10')->getValue())->toBe(1);

        $workbook->disconnectWorksheets();
    } finally {
        unlink($path);
    }
});
