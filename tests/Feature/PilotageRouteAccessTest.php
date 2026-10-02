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

/**
 * Les contrôleurs de pilotage (templates, kanban, SLA, actifs) ne vérifient
 * aucun rôle : la protection vit uniquement dans le middleware de route.
 * Ces tests verrouillent le comportement, sans figer les URI une par une.
 */
class PilotageRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTES_PILOTAGE = [
        'templates.index',
        'kanban.index',
        'sla.index',
        'assets.index',
    ];

    private User $admin;

    private User $dsi;

    private User $directeur;

    private User $technicien;

    private User $demandeur;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Role::create(['nom' => 'Administrateur', 'slug' => Role::SLUG_ADMIN]);
        Role::create(['nom' => 'DSI', 'slug' => Role::SLUG_DSI]);
        Role::create(['nom' => 'Directeur', 'slug' => Role::SLUG_DIRECTEUR]);
        Role::create(['nom' => 'Technicien', 'slug' => Role::SLUG_TECHNICIEN]);
        Role::create(['nom' => 'Demandeur', 'slug' => Role::SLUG_DEMANDEUR]);

        $this->admin = $this->utilisateur('Pilotage Admin', Role::SLUG_ADMIN);
        $this->dsi = $this->utilisateur('Pilotage DSI', Role::SLUG_DSI);
        $this->directeur = $this->utilisateur('Pilotage Directeur', Role::SLUG_DIRECTEUR);
        $this->technicien = $this->utilisateur('Pilotage Technicien', Role::SLUG_TECHNICIEN);
        $this->demandeur = $this->utilisateur('Pilotage Demandeur', Role::SLUG_DEMANDEUR);

        $this->ticket = $this->ticketDePilotage();
    }

    public function test_un_demandeur_est_bloque_sur_les_modules_de_pilotage()
    {
        foreach (self::ROUTES_PILOTAGE as $nom) {
            $this->actingAs($this->demandeur)
                ->get(route($nom))
                ->assertStatus(403, "Le demandeur ne devrait pas accéder à {$nom}.");
        }
    }

    public function test_un_utilisateur_sans_role_est_bloque_sur_les_modules_de_pilotage()
    {
        $sansRole = User::create([
            'name' => 'Sans Rôle',
            'email' => 'sans.role@nere-mining.bf',
            'password' => 'password',
            'actif' => true,
        ]);

        foreach (self::ROUTES_PILOTAGE as $nom) {
            $this->actingAs($sansRole)
                ->get(route($nom))
                ->assertStatus(403, "Un utilisateur sans rôle ne devrait pas accéder à {$nom}.");
        }
    }

    public function test_le_role_technicien_accede_aux_modules_de_pilotage()
    {
        foreach (self::ROUTES_PILOTAGE as $nom) {
            $this->actingAs($this->technicien)
                ->get(route($nom))
                ->assertStatus(200, "Le technicien devrait accéder à {$nom}.");
        }
    }

    public function test_pilotage_est_autorise_pour_admin_dsi_et_directeur()
    {
        foreach ([$this->admin, $this->dsi, $this->directeur] as $utilisateur) {
            foreach (self::ROUTES_PILOTAGE as $nom) {
                $this->actingAs($utilisateur)
                    ->get(route($nom))
                    ->assertStatus(200, "{$utilisateur->name} devrait accéder à {$nom}.");
            }
        }
    }

    public function test_un_demandeur_ne_peut_pas_creer_un_template()
    {
        $this->actingAs($this->demandeur)
            ->post(route('templates.store'), ['nom' => 'Interdit', 'categorie_id' => 1])
            ->assertStatus(403);
    }

    public function test_un_demandeur_ne_peut_pas_deplacer_une_tuile_kanban()
    {
        $this->actingAs($this->demandeur)
            ->post(route('kanban.move', ['ticket' => $this->ticket->id]), [
                'statut' => 'en_cours',
                'position' => 1,
            ])
            ->assertStatus(403);
    }

    public function test_un_demandeur_ne_peut_pas_gerer_les_actifs()
    {
        $this->actingAs($this->demandeur)
            ->post(route('assets.store'), ['nom' => 'Actif interdit'])
            ->assertStatus(403);
    }

    public function test_un_visiteur_non_authentifie_est_redirige()
    {
        foreach (self::ROUTES_PILOTAGE as $nom) {
            $this->get(route($nom))->assertRedirect(route('login'));
        }
    }

    private function ticketDePilotage(): Ticket
    {
        $departement = Departement::create(['nom' => 'Pilotage', 'code' => 'PIL', 'actif' => true]);
        $equipe = Team::create(['nom' => 'Équipe Pilotage', 'departement_id' => $departement->id]);
        $categorie = TicketCategory::create(['nom' => 'Catégorie Pilotage', 'team_id' => $equipe->id, 'actif' => true]);
        $statut = TicketStatus::create(['nom' => 'Nouveau', 'slug' => 'nouveau', 'ordre' => 1, 'couleur' => '#ffffff']);
        $priorite = TicketPriority::create(['nom' => 'Moyenne', 'slug' => 'moyenne', 'niveau' => 2, 'couleur' => '#ffffff']);

        return Ticket::create([
            'reference' => 'TICK-PILOTAGE-001',
            'titre' => 'Ticket de test pilotage',
            'description' => 'Vérification du middleware de rôle',
            'user_id' => $this->demandeur->id,
            'departement_id' => $departement->id,
            'ticket_category_id' => $categorie->id,
            'ticket_status_id' => $statut->id,
            'ticket_priority_id' => $priorite->id,
        ]);
    }

    private function utilisateur(string $nom, string $slugRole): User
    {
        return User::create([
            'name' => $nom,
            'email' => str($nom)->slug() . '@nere-mining.bf',
            'password' => 'password',
            'role_id' => Role::where('slug', $slugRole)->value('id'),
            'actif' => true,
        ]);
    }
}
