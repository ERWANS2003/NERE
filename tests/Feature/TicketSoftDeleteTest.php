<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Role;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['nom' => 'Administrateur']);
        $this->roleDemandeur = Role::firstOrCreate(['slug' => 'demandeur'], ['nom' => 'Demandeur']);
        $this->dept = Departement::firstOrCreate(['code' => 'IT_SOFT_TEST'], ['nom' => 'Informatique Soft Test', 'actif' => true]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.soft@nere-mining.bf',
            'password' => bcrypt('password'),
            'role_id' => $this->roleAdmin->id,
            'departement_id' => $this->dept->id,
            'actif' => true,
        ]);

        $team = Team::firstOrCreate(['nom' => 'Team Soft Test'], ['departement_id' => $this->dept->id]);
        $this->category = TicketCategory::firstOrCreate(['nom' => 'Catégorie Soft Test'], ['team_id' => $team->id, 'actif' => true]);
        $this->statut = TicketStatus::firstOrCreate(['slug' => 'nouveau'], ['nom' => 'Nouveau', 'ordre' => 1, 'couleur' => '#ffffff']);
        $this->priorite = TicketPriority::firstOrCreate(['slug' => 'moyenne'], ['nom' => 'Moyenne', 'niveau' => 2, 'couleur' => '#ffffff']);

        $this->ticket = Ticket::create([
            'reference' => 'TICK-TEST-001',
            'titre' => 'Ticket de test soft delete',
            'description' => 'Description du ticket',
            'user_id' => $this->admin->id,
            'departement_id' => $this->dept->id,
            'ticket_category_id' => $this->category->id,
            'ticket_status_id' => $this->statut->id,
            'ticket_priority_id' => $this->priorite->id,
        ]);
    }

    public function test_un_ticket_peut_etre_soft_delete()
    {
        $response = $this->actingAs($this->admin)->delete(route('tickets.destroy', $this->ticket));

        $response->assertRedirect(route('tickets.index'));
        $this->assertSoftDeleted('tickets', ['id' => $this->ticket->id]);
    }

    public function test_un_ticket_soft_delete_peut_etre_restaure_par_admin()
    {
        $this->ticket->delete();
        $this->assertSoftDeleted('tickets', ['id' => $this->ticket->id]);

        $response = $this->actingAs($this->admin)->post(route('tickets.restore', $this->ticket->id));

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'deleted_at' => null,
        ]);
    }

    public function test_filtrage_avec_archives_et_per_page()
    {
        $this->ticket->delete();

        // Par défaut, pas visible dans la liste normale
        $response1 = $this->actingAs($this->admin)->get(route('tickets.index'));
        $response1->assertDontSee('TICK-TEST-001');

        // Visible avec le filtre archives=uniquement
        $response2 = $this->actingAs($this->admin)->get(route('tickets.index', ['archives' => 'uniquement', 'per_page' => 10]));
        $response2->assertSee('TICK-TEST-001');
    }
}
