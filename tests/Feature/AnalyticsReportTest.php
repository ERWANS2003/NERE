<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Models\Ticket;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;

function reportForm(Department $department, string $name = 'Demande de matériel'): DepartmentForm
{
    return DepartmentForm::create([
        'department_id' => $department->id,
        'name' => $name,
        'form_schema' => ['fields' => []],
        'is_active' => true,
    ]);
}

function reportSubmission(DepartmentForm $form, string $status, $createdAt, $reviewedAt = null, ?User $reviewer = null): FormSubmission
{
    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'user_id' => User::factory()->create()->id,
        'data' => ['details' => 'Test'],
        'status' => $status,
        'reviewed_by' => $reviewer?->id,
        'reviewed_at' => $reviewedAt,
    ]);
    $submission->forceFill(['created_at' => $createdAt])->saveQuietly();

    return $submission;
}

function reportTicket(Department $department, array $attributes): Ticket
{
    static $number = 900;
    $number++;

    $ticket = Ticket::create([
        'number' => 'IT-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
        'department_id' => $department->id,
        'requester_id' => User::factory()->create()->id,
        'subject' => 'Incident',
        'description' => 'Description',
        'category' => $attributes['category'] ?? 'network',
        'priority' => $attributes['priority'] ?? 'normal',
        'status' => $attributes['status'] ?? 'new',
    ]);
    $ticket->forceFill($attributes)->saveQuietly();

    return $ticket->refresh();
}

function sectionKpi(array $section, string $label): array
{
    return collect($section['kpis'])->firstWhere('label', $label);
}

it('measures request volume, processing delays, backlog age and top forms', function () {
    $department = Department::factory()->create(['name' => 'Département Alpha']);
    $admin = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->create(['name' => 'Awa Ouédraogo']);
    $form = reportForm($department);

    reportSubmission($form, 'approved', now()->subDays(3), now()->subDays(3)->addHours(2), $reviewer);
    reportSubmission($form, 'approved', now()->subDays(2), now()->subDays(2)->addHours(4), $reviewer);
    reportSubmission($form, 'rejected', now()->subDays(2), now()->subDays(2)->addHours(10), $reviewer);
    reportSubmission($form, 'pending', now()->subDays(10));
    reportSubmission($form, 'pending', now()->subDays(40)); // période précédente, toujours en attente

    $sections = $this->actingAs($admin)
        ->get(route('intranet.analytics', ['periode' => 30]))
        ->assertOk()
        ->assertSee('Formulaires les plus sollicités')
        ->viewData('sections');

    $requests = $sections['demandes'];

    expect(sectionKpi($requests, 'Demandes reçues')['brut'])->toBe(4)
        ->and(sectionKpi($requests, 'Demandes reçues')['variation'])->toMatchArray(['sens' => 'hausse', 'pourcentage' => 300])
        ->and(sectionKpi($requests, 'Délai médian de traitement')['valeur'])->toBe('4 h')
        ->and(sectionKpi($requests, 'Taux d’approbation')['valeur'])->toBe('67 %')
        ->and(sectionKpi($requests, 'En attente (stock)')['brut'])->toBe(2)
        ->and(sectionKpi($requests, 'En attente depuis plus de 7 j')['brut'])->toBe(2)
        ->and($requests['anciennete']['data'])->toBe([0, 0, 0, 1, 1])
        ->and(array_sum(array_map('array_sum', $requests['heatmap']['cellules'])))->toBe(4)
        ->and($requests['formulaires'][0])->toMatchArray(['nom' => 'Demande de matériel', 'volume' => 4, 'approbation' => 67, 'enAttente' => 1])
        ->and($requests['agents'][0])->toMatchArray(['nom' => 'Awa Ouédraogo', 'traitees' => 3, 'approuvees' => 2])
        ->and(array_sum($requests['tendance']['actuelle']))->toBe(4)
        ->and(collect($sections['synthese']['alertes'])->pluck('texte')->implode(' '))->toContain('2 demande(s) attendent une décision depuis plus de 7 jours');
});

it('computes IT support SLA compliance, technician workload and unassigned alerts', function () {
    $it = Department::factory()->create(['name' => 'Département IT']);
    $admin = User::factory()->superAdmin()->create();
    $technician = User::factory()->create(['name' => 'Issouf Kaboré']);

    // Réponse et résolution dans les temps.
    reportTicket($it, [
        'assignee_id' => $technician->id, 'status' => 'resolved', 'priority' => 'high',
        'created_at' => now()->subDays(5),
        'first_response_due_at' => now()->subDays(5)->addHours(1), 'first_responded_at' => now()->subDays(5)->addMinutes(30),
        'resolution_due_at' => now()->subDays(5)->addHours(8), 'resolved_at' => now()->subDays(5)->addHours(6),
    ]);
    // Résolution en retard.
    reportTicket($it, [
        'assignee_id' => $technician->id, 'status' => 'resolved',
        'created_at' => now()->subDays(4),
        'first_response_due_at' => now()->subDays(4)->addHours(4), 'first_responded_at' => now()->subDays(4)->addHours(1),
        'resolution_due_at' => now()->subDays(4)->addHours(16), 'resolved_at' => now()->subDays(3),
    ]);
    // Ouvert, non assigné, urgent, échéance de réponse dépassée.
    reportTicket($it, [
        'status' => 'new', 'priority' => 'urgent', 'category' => 'access',
        'created_at' => now()->subDays(2),
        'first_response_due_at' => now()->subDays(2)->addMinutes(15),
        'resolution_due_at' => now()->subDays(2)->addHours(4),
    ]);

    $sections = $this->actingAs($admin)
        ->get(route('intranet.analytics', ['periode' => 30]))
        ->assertOk()
        ->assertSee('Charge des techniciens')
        ->viewData('sections');

    $support = $sections['support'];

    expect(sectionKpi($support, 'Tickets créés')['brut'])->toBe(3)
        ->and(sectionKpi($support, 'Tickets résolus')['brut'])->toBe(2)
        ->and(sectionKpi($support, 'Tickets ouverts')['brut'])->toBe(1)
        ->and(sectionKpi($support, 'SLA 1re réponse respecté')['valeur'])->toBe('67 %')
        ->and(sectionKpi($support, 'SLA résolution respecté')['valeur'])->toBe('33 %')
        ->and(sectionKpi($support, 'SLA dépassés (période)')['brut'])->toBe(3)
        ->and($support['sla'])->toMatchArray(['respectes' => [2, 1], 'depasses' => [1, 2]])
        ->and($support['techniciens'])->toContain(
            ['nom' => 'Issouf Kaboré', 'ouverts' => 0, 'urgents' => 0, 'resolus' => 2, 'mttr' => 15.0],
            ['nom' => 'Non assignés', 'ouverts' => 1, 'urgents' => 1, 'resolus' => 0, 'mttr' => null],
        )
        ->and(collect($sections['synthese']['alertes'])->pluck('niveau'))->toContain('critique');
});

it('reports time off days, decisions and current absences', function () {
    $department = Department::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $employee = User::factory()->create(['name' => 'Mariam Zongo']);
    $employee->departments()->attach($department, ['role' => DepartmentRole::User]);
    $policy = TimeOffPolicy::create([
        'name' => 'Congé annuel', 'type' => 'vacation', 'annual_allocation' => 30,
        'carry_over_limit' => 5, 'requires_approval' => true, 'advance_notice_days' => 0, 'is_active' => true,
    ]);

    $current = TimeOffRequest::create([
        'user_id' => $employee->id, 'policy_id' => $policy->id,
        'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDays(2)->toDateString(),
        'total_days' => 4, 'status' => 'approved', 'approved_at' => now()->subDays(3),
    ]);
    $current->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();
    TimeOffRequest::create([
        'user_id' => $employee->id, 'policy_id' => $policy->id,
        'start_date' => now()->addDays(20)->toDateString(), 'end_date' => now()->addDays(21)->toDateString(),
        'total_days' => 2, 'status' => 'pending',
    ]);

    $timeOff = $this->actingAs($admin)
        ->get(route('intranet.analytics', ['periode' => 30]))
        ->assertOk()
        ->viewData('sections')['conges'];

    expect(sectionKpi($timeOff, 'Demandes de congé')['brut'])->toBe(2)
        ->and(sectionKpi($timeOff, 'Jours approuvés')['valeur'])->toBe('4 j')
        ->and(sectionKpi($timeOff, 'Absents aujourd’hui')['brut'])->toBe(1)
        ->and(sectionKpi($timeOff, 'En attente de décision')['brut'])->toBe(1)
        ->and(sectionKpi($timeOff, 'Délai médian de décision')['valeur'])->toBe('2 j')
        ->and($timeOff['joursParType'])->toBe(['labels' => ['Congé annuel'], 'data' => [4.0]])
        ->and($timeOff['aVenir'][0]['nom'])->toBe('Mariam Zongo');
});

it('lets administrators narrow every section to one department', function () {
    $alpha = Department::factory()->create(['name' => 'Département Alpha']);
    $bravo = Department::factory()->create(['name' => 'Département Bravo']);
    $admin = User::factory()->superAdmin()->create();
    reportSubmission(reportForm($alpha, 'Formulaire Alpha'), 'pending', now()->subDay());
    reportSubmission(reportForm($bravo, 'Formulaire Bravo'), 'pending', now()->subDay());

    $response = $this->actingAs($admin)
        ->get(route('intranet.analytics', ['periode' => 30, 'departement' => $alpha->id]))
        ->assertOk()
        ->assertViewHas('scopeLabel', 'Département : Département Alpha')
        ->assertSee(route('intranet.analytics.export.csv', ['periode' => 30, 'departement' => $alpha->id]));

    expect(sectionKpi($response->viewData('sections')['demandes'], 'Demandes reçues')['brut'])->toBe(1)
        ->and($response->viewData('sections')['demandes']['formulaires'][0]['nom'])->toBe('Formulaire Alpha')
        ->and($response->viewData('charts')['parDepartement']['labels'])->toBe(['Département Alpha']);

    $this->get(route('intranet.analytics.export.json', ['periode' => 30, 'departement' => $alpha->id]))
        ->assertOk()
        ->assertJsonPath('scope', 'Département : Département Alpha')
        ->assertJsonPath('sections.demandes.formulaires.0.nom', 'Formulaire Alpha');
});

it('keeps department heads inside their departments and hides company-only sections', function () {
    $managed = Department::factory()->create(['name' => 'Département contrôlé']);
    $other = Department::factory()->create(['name' => 'Département privé']);
    $head = User::factory()->withRole($managed, DepartmentRole::Director)->create();
    reportSubmission(reportForm($other, 'Formulaire privé'), 'pending', now()->subDay());
    reportTicket($other, ['created_at' => now()->subDay()]);

    $response = $this->actingAs($head)
        ->get(route('intranet.analytics', ['periode' => 30, 'departement' => $other->id]))
        ->assertOk()
        ->assertDontSee('Formulaire privé')
        ->assertDontSee($other->name);

    $sections = $response->viewData('sections');

    expect($sections)->not->toHaveKeys(['support', 'contenus'])
        ->and(sectionKpi($sections['demandes'], 'Demandes reçues')['brut'])->toBe(0);
});
