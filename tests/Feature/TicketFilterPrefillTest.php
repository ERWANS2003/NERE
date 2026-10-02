<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketFilterPrefillTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected TicketStatus $statut;

    protected TicketCategory $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['nom' => 'Admin', 'slug' => 'admin']);
        $role->permissions()->create(['nom' => 'Voir les tickets', 'slug' => 'tickets.view', 'module' => 'tickets']);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'actif' => true,
        ]);

        $this->statut = TicketStatus::create([
            'nom' => 'Nouveau', 'slug' => 'nouveau',
            'couleur' => '#000', 'ordre' => 0, 'est_final' => false,
        ]);

        $this->categorie = TicketCategory::create([
            'nom' => 'Demande IT', 'slug' => 'it-test', 'actif' => true,
        ]);
    }

    protected function creerTicket(string $reference, bool $horsSla): Ticket
    {
        return Ticket::create([
            'reference' => $reference,
            'titre' => 'Ticket de test',
            'description' => 'x',
            'type' => 'incident',
            'user_id' => $this->admin->id,
            'ticket_category_id' => $this->categorie->id,
            'ticket_status_id' => $this->statut->id,
            'sla_depasse' => $horsSla,
        ]);
    }

    public function test_sla_depasse_filter_is_applied(): void
    {
        $this->creerTicket('INC-001', true);
        $this->creerTicket('INC-002', false);

        $response = $this->actingAs($this->admin)
            ->get(route('tickets.index', ['sla_depasse' => 1]));

        $response->assertOk();
        $response->assertSee('INC-001');
        $response->assertDontSee('INC-002');
    }

    public function test_type_query_parameter_preselects_incident(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('tickets.create', ['type' => 'incident']));

        $response->assertOk();
        $response->assertSee('value="incident" selected', false);
    }

    public function test_department_resolves_from_code(): void
    {
        $dept = Departement::create(['nom' => 'Qualité & Contrôle', 'code' => 'QUAL-CONT', 'actif' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('tickets.create', ['department' => 'qual_cont']));

        $response->assertOk();
        $this->assertSame($dept->id, $response->viewData('selectedDepartment'));
    }

    public function test_department_resolves_from_partial_name(): void
    {
        // « finance » doit résoudre vers un service dont le nom contient ce mot-clé,
        // quel que soit le service déjà présent en base.
        $response = $this->actingAs($this->admin)
            ->get(route('tickets.create', ['department' => 'finance']));

        $response->assertOk();

        $id = $response->viewData('selectedDepartment');
        $this->assertNotNull($id, 'Le service doit être résolu.');

        $dept = Departement::find($id);
        $this->assertStringContainsString(
            'financ',
            Str::lower($dept->nom . ' ' . $dept->code)
        );
    }

    public function test_department_resolves_from_id(): void
    {
        $dept = Departement::create(['nom' => 'Méthodes & Planification', 'code' => 'METH-PLAN', 'actif' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('tickets.create', ['department' => (string) $dept->id]));

        $response->assertOk();
        $this->assertSame($dept->id, $response->viewData('selectedDepartment'));
    }
}
