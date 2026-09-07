<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['nom' => 'Administrateur']);
        $this->roleTech = Role::firstOrCreate(['slug' => 'technicien'], ['nom' => 'Technicien']);
        $this->dept = Departement::firstOrCreate(['code' => 'SEARCH_TEST'], ['nom' => 'Service Recherche Test', 'actif' => true]);

        $this->admin = User::create([
            'name' => 'Admin Super',
            'email' => 'admin.search@nere-mining.bf',
            'password' => bcrypt('password'),
            'role_id' => $this->roleAdmin->id,
            'departement_id' => $this->dept->id,
            'actif' => true,
        ]);

        User::create([
            'name' => 'Kaboré Salif',
            'email' => 'salif.kabore@nere-mining.bf',
            'matricule' => 'MAT-101',
            'poste' => 'Analyste',
            'password' => bcrypt('password'),
            'role_id' => $this->roleTech->id,
            'departement_id' => $this->dept->id,
            'actif' => true,
        ]);

        User::create([
            'name' => 'Traoré Fatou',
            'email' => 'fatou.traore@nere-mining.bf',
            'matricule' => 'MAT-102',
            'poste' => 'Superviseur',
            'password' => bcrypt('password'),
            'role_id' => $this->roleTech->id,
            'departement_id' => $this->dept->id,
            'actif' => false,
        ]);
    }

    public function test_recherche_utilisateur_par_nom_email_matricule()
    {
        // Recherche par nom
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['q' => 'Kaboré']));
        $response->assertStatus(200);
        $response->assertSee('Kaboré Salif');
        $response->assertDontSee('Traoré Fatou');

        // Recherche par matricule
        $responseMatricule = $this->actingAs($this->admin)->get(route('admin.users.index', ['q' => 'MAT-102']));
        $responseMatricule->assertSee('Traoré Fatou');
        $responseMatricule->assertDontSee('Kaboré Salif');
    }

    public function test_filtrage_et_pagination_des_utilisateurs()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'role_id' => $this->roleTech->id,
            'actif' => '1',
            'per_page' => 10,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Kaboré Salif');
        $response->assertDontSee('Traoré Fatou'); // Car inactif
    }
}
