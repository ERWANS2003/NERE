<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['nom' => 'Administrateur']);
        $this->roleDsi = Role::firstOrCreate(['slug' => 'dsi'], ['nom' => 'DSI']);
        $this->roleDirecteur = Role::firstOrCreate(['slug' => 'directeur_departement'], ['nom' => 'Directeur']);
        $this->roleTech = Role::firstOrCreate(['slug' => 'technicien'], ['nom' => 'Technicien']);
        $this->roleDemandeur = Role::firstOrCreate(['slug' => 'demandeur'], ['nom' => 'Demandeur']);

        $this->dept = Departement::firstOrCreate(['code' => 'REP_TEST'], ['nom' => 'Département Rapport Test', 'actif' => true]);

        $this->adminUser = User::create(['name' => 'Admin R', 'email' => 'admin.r@nere.bf', 'password' => bcrypt('p'), 'role_id' => $this->roleAdmin->id, 'actif' => true]);
        $this->dsiUser = User::create(['name' => 'DSI R', 'email' => 'dsi.r@nere.bf', 'password' => bcrypt('p'), 'role_id' => $this->roleDsi->id, 'actif' => true]);
        $this->directeurUser = User::create(['name' => 'Directeur R', 'email' => 'directeur.r@nere.bf', 'password' => bcrypt('p'), 'role_id' => $this->roleDirecteur->id, 'departement_id' => $this->dept->id, 'actif' => true]);
        $this->techUser = User::create(['name' => 'Tech R', 'email' => 'tech.r@nere.bf', 'password' => bcrypt('p'), 'role_id' => $this->roleTech->id, 'actif' => true]);
        $this->demandeurUser = User::create(['name' => 'Demandeur R', 'email' => 'demandeur.r@nere.bf', 'password' => bcrypt('p'), 'role_id' => $this->roleDemandeur->id, 'actif' => true]);
    }

    public function test_les_demandeurs_et_techniciens_n_ont_pas_acces_aux_rapports()
    {
        $responseTech = $this->actingAs($this->techUser)->get(route('reports.index'));
        $responseTech->assertStatus(403);

        $responseDemandeur = $this->actingAs($this->demandeurUser)->get(route('reports.index'));
        $responseDemandeur->assertStatus(403);
    }

    public function test_les_admins_dsi_et_directeurs_ont_acces_aux_rapports()
    {
        $this->actingAs($this->adminUser)->get(route('reports.index'))->assertStatus(200);
        $this->actingAs($this->dsiUser)->get(route('reports.index'))->assertStatus(200);
        $this->actingAs($this->directeurUser)->get(route('reports.index'))->assertStatus(200);
    }
}
