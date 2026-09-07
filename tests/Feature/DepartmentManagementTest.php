<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $roleAdmin;
    protected $roleDirecteur;
    protected $roleTechnicien;
    protected $roleDemandeur;
    protected $deptRH;
    protected $deptIT;
    protected $teamRH;
    protected $directeurRH;
    protected $userSimple;

    protected function setUp(): void
    {
        parent::setUp();

        // Rôles
        $this->roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['nom' => 'Administrateur']);
        $this->roleDirecteur = Role::firstOrCreate(['slug' => 'directeur_departement'], ['nom' => 'Directeur de Département']);
        $this->roleTechnicien = Role::firstOrCreate(['slug' => 'technicien'], ['nom' => 'Technicien']);
        $this->roleDemandeur = Role::firstOrCreate(['slug' => 'demandeur'], ['nom' => 'Demandeur']);

        // Départements
        $this->deptRH = Departement::firstOrCreate(['code' => 'RH_TEST'], ['nom' => 'Ressources Humaines Test', 'actif' => true]);
        $this->deptIT = Departement::firstOrCreate(['code' => 'IT_TEST'], ['nom' => 'Informatique Test', 'actif' => true]);

        // Équipes
        $this->teamRH = Team::create(['nom' => 'Équipe Paie', 'departement_id' => $this->deptRH->id]);

        // Directeur RH
        $this->directeurRH = User::create([
            'name' => 'Awa Traoré',
            'email' => 'awa.traore@nere-mining.bf',
            'password' => bcrypt('password'),
            'role_id' => $this->roleDirecteur->id,
            'departement_id' => $this->deptRH->id,
            'actif' => true,
        ]);
        $this->deptRH->update(['directeur_id' => $this->directeurRH->id]);

        // Demandeur ordinaire
        $this->userSimple = User::create([
            'name' => 'Paul Kaboré',
            'email' => 'paul.kabore@nere-mining.bf',
            'password' => bcrypt('password'),
            'role_id' => $this->roleDemandeur->id,
            'departement_id' => $this->deptRH->id,
            'actif' => true,
        ]);
    }

    public function test_le_directeur_peut_acceder_a_mon_departement()
    {
        $response = $this->actingAs($this->directeurRH)->get(route('department.index'));

        $response->assertStatus(200);
        $response->assertSee('Ressources Humaines Test');
    }

    public function test_le_dsi_est_considere_comme_directeur_de_departement()
    {
        $roleDsi = Role::firstOrCreate(['slug' => 'dsi'], ['nom' => 'DSI']);
        $dsiUser = User::create([
            'name' => 'Moussa DSI',
            'email' => 'moussa.dsi@nere-mining.bf',
            'password' => bcrypt('password'),
            'role_id' => $roleDsi->id,
            'departement_id' => $this->deptIT->id,
            'actif' => true,
        ]);

        $this->assertTrue($dsiUser->isDirecteur());

        $response = $this->actingAs($dsiUser)->get(route('department.index'));
        $response->assertStatus(200);
        $response->assertSee('Informatique Test');
    }

    public function test_un_utilisateur_simple_ne_peut_pas_acceder_au_departement()
    {
        $response = $this->actingAs($this->userSimple)->get(route('department.index'));

        $response->assertStatus(403);
    }

    public function test_le_directeur_peut_creer_un_membre_pour_son_departement()
    {
        $response = $this->actingAs($this->directeurRH)->post(route('department.members.store'), [
            'name' => 'Moussa Ouédraogo',
            'email' => 'moussa.ouedraogo@nere-mining.bf',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleTechnicien->id,
            'poste' => 'Gestionnaire Paie',
            'est_technicien' => 1,
            'team_id' => $this->teamRH->id,
        ]);

        $response->assertRedirect(route('department.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'moussa.ouedraogo@nere-mining.bf',
            'departement_id' => $this->deptRH->id,
            'poste' => 'Gestionnaire Paie',
        ]);

        $newMember = User::where('email', 'moussa.ouedraogo@nere-mining.bf')->first();
        $this->assertTrue($this->teamRH->techniciens()->where('user_id', $newMember->id)->exists());
    }

    public function test_le_directeur_peut_ajouter_un_membre_existant_a_son_equipe()
    {
        $response = $this->actingAs($this->directeurRH)->post(route('department.teams.members.add', $this->teamRH), [
            'user_id' => $this->userSimple->id,
            'chef_equipe' => 0,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($this->teamRH->techniciens()->where('user_id', $this->userSimple->id)->exists());
    }

    public function test_le_directeur_ne_peut_pas_ajouter_un_membre_d_un_autre_departement()
    {
        $userIT = User::create([
            'name' => 'Jean IT',
            'email' => 'jean.it@nere-mining.bf',
            'password' => bcrypt('password'),
            'role_id' => $this->roleTechnicien->id,
            'departement_id' => $this->deptIT->id,
            'actif' => true,
        ]);

        $response = $this->actingAs($this->directeurRH)->post(route('department.teams.members.add', $this->teamRH), [
            'user_id' => $userIT->id,
        ]);

        $response->assertSessionHasErrors(['user_id']);
        $this->assertFalse($this->teamRH->techniciens()->where('user_id', $userIT->id)->exists());
    }
}
