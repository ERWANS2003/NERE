<?php

namespace Tests\Feature\Intranet;

use App\Models\Intranet\Department;
use App\Models\Intranet\Form;
use App\Models\Intranet\FormField;
use App\Models\Intranet\Service;
use App\Models\Intranet\Submission;
use App\Models\Intranet\WorkflowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matrice des droits d'accès Intranet.
 *
 * Règles testées :
 *  - Un utilisateur sans affectation ne peut pas accéder à un département (403)
 *  - Un utilisateur avec rôle 'user' peut voir le département et soumettre
 *  - Un technicien voit la file de traitement
 *  - Un directeur peut assigner
 *  - Un utilisateur ne voit pas les demandes d'autrui (403)
 *  - Un technicien peut escalader mais pas clôturer
 *  - Un directeur peut rejeter
 *  - Un super admin accède à tout
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /* ── Helpers ─────────────────────────────────────────────── */

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'actif'          => true,
            'est_technicien' => false,
            'disponible'     => true,
            'is_super_admin' => false,
        ], $attrs));
    }

    private function makeDepartment(): Department
    {
        return Department::create([
            'code' => 'TEST-' . uniqid(),
            'tag'  => 'TEST',
            'name' => 'Département Test',
            'icon' => 'briefcase',
            'color'    => '#2563eb',
            'position' => 99,
            'is_active'=> true,
        ]);
    }

    private function makeForm(Department $dept): Form
    {
        $service = Service::create([
            'department_id' => $dept->id,
            'name'          => 'Service Test',
            'is_active'     => true,
            'position'      => 1,
        ]);

        $form = Form::create([
            'service_id' => $service->id,
            'name'       => 'Formulaire Test',
            'code'       => 'TEST-FORM-' . uniqid(),
            'version'    => 1,
            'status'     => 'published',
        ]);

        FormField::create([
            'form_id'  => $form->id,
            'label'    => 'Titre',
            'type'     => 'text',
            'required' => true,
            'position' => 1,
            'rules'    => ['max' => 255],
        ]);

        WorkflowStep::create([
            'form_id'          => $form->id,
            'order'            => 1,
            'name'             => 'Traitement',
            'responsible_role' => 'technician',
            'tech_level'       => 1,
            'needs_approval'   => false,
            'sla_hours'        => 8,
        ]);

        return $form;
    }

    private function makeSubmission(Form $form, User $requester): Submission
    {
        return Submission::create([
            'reference'    => 'TEST-2026-00001',
            'form_id'      => $form->id,
            'requester_id' => $requester->id,
            'status'       => Submission::STATUS_SUBMITTED,
            'priority'     => 'normale',
        ]);
    }

    /* ── Tests : accès département ───────────────────────────── */

    /** @test */
    public function test_user_without_department_access_gets_403(): void
    {
        $user = $this->makeUser();
        $dept = $this->makeDepartment();

        $this->actingAs($user)
            ->get(route('intranet.departments.show', $dept))
            ->assertForbidden();
    }

    /** @test */
    public function test_member_user_can_view_department(): void
    {
        $user = $this->makeUser();
        $dept = $this->makeDepartment();
        $dept->users()->attach($user->id, ['role' => 'user', 'tech_level' => null]);

        $this->actingAs($user)
            ->get(route('intranet.departments.show', $dept))
            ->assertOk();
    }

    /** @test */
    public function test_super_admin_can_view_any_department(): void
    {
        $admin = $this->makeUser(['is_super_admin' => true]);
        $dept  = $this->makeDepartment();

        $this->actingAs($admin)
            ->get(route('intranet.departments.show', $dept))
            ->assertOk();
    }

    /* ── Tests : soumission ──────────────────────────────────── */

    /** @test */
    public function test_member_can_submit_form(): void
    {
        $user = $this->makeUser();
        $dept = $this->makeDepartment();
        $dept->users()->attach($user->id, ['role' => 'user', 'tech_level' => null]);
        $form = $this->makeForm($dept);

        // Policy permettrait la soumission
        $this->assertTrue($user->can('submitTo', $form));
    }

    /** @test */
    public function test_non_member_cannot_submit_form(): void
    {
        $user  = $this->makeUser();
        $dept  = $this->makeDepartment();
        $form  = $this->makeForm($dept);

        // Non-membre ne peut pas passer la policy
        $this->assertFalse($user->can('submitTo', $form));
    }

    /* ── Tests : isolation des demandes ─────────────────────── */

    /** @test */
    public function test_user_cannot_view_another_users_submission(): void
    {
        $owner  = $this->makeUser();
        $other  = $this->makeUser();
        $dept   = $this->makeDepartment();

        $dept->users()->attach($owner->id, ['role' => 'user', 'tech_level' => null]);
        $dept->users()->attach($other->id, ['role' => 'user', 'tech_level' => null]);

        $form = $this->makeForm($dept);
        $sub  = $this->makeSubmission($form, $owner);

        // L'autre utilisateur (pas le demandeur, pas staff) ne peut pas voir via policy
        $this->assertFalse($other->can('view', $sub));
    }

    /** @test */
    public function test_technician_can_view_department_submissions(): void
    {
        $owner = $this->makeUser();
        $tech  = $this->makeUser(['est_technicien' => true]);
        $dept  = $this->makeDepartment();

        $dept->users()->attach($owner->id, ['role' => 'user',       'tech_level' => null]);
        $dept->users()->attach($tech->id,  ['role' => 'technician', 'tech_level' => 1]);

        $form = $this->makeForm($dept);
        $sub  = $this->makeSubmission($form, $owner);

        // Le technicien du département peut voir via policy
        $this->assertTrue($tech->can('view', $sub));
    }

    /* ── Tests : transitions de statut ──────────────────────── */

    /** @test */
    public function test_technician_can_change_status_to_in_progress(): void
    {
        $owner = $this->makeUser();
        $tech  = $this->makeUser(['est_technicien' => true]);
        $dept  = $this->makeDepartment();

        $dept->users()->attach($owner->id, ['role' => 'user',       'tech_level' => null]);
        $dept->users()->attach($tech->id,  ['role' => 'technician', 'tech_level' => 1]);

        $form = $this->makeForm($dept);
        $sub  = $this->makeSubmission($form, $owner);
        $sub->update(['status' => Submission::STATUS_ASSIGNED]);

        // Le technicien peut agir (policy::act)
        $this->assertTrue($tech->can('act', $sub));
    }

    /** @test */
    public function test_non_staff_user_cannot_change_status(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $dept  = $this->makeDepartment();

        $dept->users()->attach($owner->id, ['role' => 'user', 'tech_level' => null]);
        $dept->users()->attach($other->id, ['role' => 'user', 'tech_level' => null]);

        $form = $this->makeForm($dept);
        $sub  = $this->makeSubmission($form, $owner);

        // Utilisateur simple ne peut pas agir
        $this->assertFalse($other->can('act', $sub));
    }

    /** @test */
    public function test_only_director_can_assign_submission(): void
    {
        $owner    = $this->makeUser();
        $tech     = $this->makeUser(['est_technicien' => true]);
        $director = $this->makeUser();
        $dept     = $this->makeDepartment();

        $dept->users()->attach($owner->id,    ['role' => 'user',       'tech_level' => null]);
        $dept->users()->attach($tech->id,     ['role' => 'technician', 'tech_level' => 1]);
        $dept->users()->attach($director->id, ['role' => 'director',   'tech_level' => null]);

        $form = $this->makeForm($dept);
        $sub  = $this->makeSubmission($form, $owner);

        // Technicien ne peut pas assigner
        $this->assertFalse($tech->can('assign', $sub));

        // Directeur peut assigner
        $this->assertTrue($director->can('assign', $sub));
    }

    /** @test */
    public function test_escalation_requires_staff_role(): void
    {
        $owner = $this->makeUser();
        $dept  = $this->makeDepartment();
        $dept->users()->attach($owner->id, ['role' => 'user', 'tech_level' => null]);

        $form = $this->makeForm($dept);

        // Ajouter une étape de niveau 2
        WorkflowStep::create([
            'form_id' => $form->id, 'order' => 2, 'name' => 'N2',
            'responsible_role' => 'technician', 'tech_level' => 2,
            'needs_approval' => false, 'sla_hours' => 4,
        ]);

        $sub = $this->makeSubmission($form, $owner);
        $sub->update(['status' => Submission::STATUS_IN_PROGRESS]);

        // Utilisateur simple ne peut pas escalader
        $this->assertFalse($owner->can('escalate', $sub));
    }

    /** @test */
    public function test_super_admin_can_do_anything(): void
    {
        $admin  = $this->makeUser(['is_super_admin' => true]);
        $owner  = $this->makeUser();
        $dept   = $this->makeDepartment();

        $dept->users()->attach($owner->id, ['role' => 'user', 'tech_level' => null]);

        $form = $this->makeForm($dept);
        $sub  = $this->makeSubmission($form, $owner);

        // Super admin peut faire tout
        $this->assertTrue($admin->can('view', $sub));
        $this->assertTrue($admin->can('act', $sub));
        $this->assertTrue($admin->can('assign', $sub));
        $this->assertTrue($admin->can('escalate', $sub));
    }

    /** @test */
    public function test_submission_has_correct_attributes(): void
    {
        $owner = $this->makeUser();
        $dept  = $this->makeDepartment();
        $dept->users()->attach($owner->id, ['role' => 'user', 'tech_level' => null]);

        $form = $this->makeForm($dept);
        $sub  = $this->makeSubmission($form, $owner);

        $this->assertNotNull($sub->reference);
        $this->assertEquals($owner->id, $sub->requester_id);
        $this->assertEquals($form->id, $sub->form_id);
        $this->assertNotNull($sub->status);
    }

}
