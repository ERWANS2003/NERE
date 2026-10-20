<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Models\User;
use App\Notifications\SubmissionReviewed;
use Illuminate\Support\Facades\Notification;

function createSubmission(Department $department, User $requester, string $name = 'Demande de service'): FormSubmission
{
    $form = DepartmentForm::create([
        'department_id' => $department->id,
        'name' => $name,
        'description' => 'Demande de service départemental',
        'form_schema' => ['fields' => []],
        'is_active' => true,
        'order' => 1,
    ]);

    return FormSubmission::create([
        'form_id' => $form->id,
        'user_id' => $requester->id,
        'data' => ['details' => 'Besoin de traitement'],
        'status' => 'pending',
    ]);
}

it('shows department submissions only to the assigned department team', function () {
    $department = Department::factory()->create(['name' => 'Ressources humaines']);
    $otherDepartment = Department::factory()->create(['name' => 'Informatique']);
    $requester = User::factory()->create();
    $teamUser = User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $visible = createSubmission($department, $requester, 'Attestation de travail');
    createSubmission($otherDepartment, $requester, 'Réinitialisation informatique');

    $this->actingAs($teamUser)
        ->get(route('intranet.submissions.queue'))
        ->assertOk()
        ->assertSee('Demandes à traiter')
        ->assertSee('Attestation de travail')
        ->assertDontSee('Réinitialisation informatique')
        ->assertSee(route('intranet.submissions.show', $visible), false);

    $this->get(route('intranet.submissions.show', $visible))
        ->assertOk()
        ->assertSee('Prendre en charge')
        ->assertDontSee('Approuver la demande');
});

it('allows a technician to claim a request but reserves approval for the department head', function () {
    $department = Department::factory()->create();
    $requester = User::factory()->create();
    $technician = User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $otherTechnician = User::factory()->withRole($department, DepartmentRole::Technician, 2)->create();
    $submission = createSubmission($department, $requester);

    $this->actingAs($technician)
        ->post(route('intranet.submissions.take', $submission))
        ->assertRedirect(route('intranet.submissions.show', $submission))
        ->assertSessionHas('success');

    expect($submission->fresh()->assigned_to)->toBe($technician->id)
        ->and($submission->fresh()->assigned_at)->not->toBeNull();

    $this->actingAs($otherTechnician)
        ->post(route('intranet.submissions.take', $submission))
        ->assertSessionHas('error');

    $this->actingAs($technician)
        ->put(route('intranet.submissions.update', $submission), [
            'status' => 'approved',
            'review_comment' => 'Terminé',
        ])
        ->assertForbidden();

    expect($submission->fresh()->status)->toBe('pending');
});

it('lets the department head decide on a request and notifies its requester', function () {
    Notification::fake();
    $department = Department::factory()->create();
    $requester = User::factory()->create();
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();
    $submission = createSubmission($department, $requester);

    $this->actingAs($head)
        ->get(route('intranet.submissions.queue'))
        ->assertOk()
        ->assertSee('Demande de service');

    $this->get(route('intranet.submissions.show', $submission))
        ->assertOk()
        ->assertSee('Approuver la demande')
        ->assertSee('Rejeter la demande');

    $this->put(route('intranet.submissions.update', $submission), [
        'status' => 'approved',
        'review_comment' => 'Votre demande est approuvée.',
    ])->assertRedirect(route('intranet.submissions.show', $submission));

    expect($submission->fresh()->status)->toBe('approved')
        ->and($submission->fresh()->reviewed_by)->toBe($head->id);

    Notification::assertSentTo($requester, SubmissionReviewed::class);
});

it('denies the processing queue to ordinary users and blocks cross-department requests', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $requester = User::factory()->create();
    $technician = User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $foreignSubmission = createSubmission($otherDepartment, $requester);

    $this->actingAs(User::factory()->create())
        ->get(route('intranet.submissions.queue'))
        ->assertForbidden();

    $this->actingAs($technician)
        ->get(route('intranet.submissions.show', $foreignSubmission))
        ->assertForbidden();

    $this->actingAs($technician)
        ->post(route('intranet.submissions.take', $foreignSubmission))
        ->assertForbidden();
});
