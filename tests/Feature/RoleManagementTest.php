<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['nom' => 'Administrateur']);
        $this->admin = User::create([
            'name' => 'Admin Role Test',
            'email' => 'admin.roles@nere-mining.bf',
            'password' => bcrypt('password'),
            'role_id' => $this->roleAdmin->id,
            'actif' => true,
        ]);

        $this->perm1 = Permission::firstOrCreate(['slug' => 'test.perm1'], ['nom' => 'Perm 1', 'module' => 'test']);
        $this->perm2 = Permission::firstOrCreate(['slug' => 'test.perm2'], ['nom' => 'Perm 2', 'module' => 'test']);
    }

    public function test_admin_peut_lister_les_roles()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.roles.index'));
        $response->assertStatus(200);
        $response->assertSee('Administrateur');
    }

    public function test_admin_peut_creer_un_role_avec_permissions()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'nom' => 'Superviseur Logistique',
            'slug' => 'superviseur_logistique',
            'description' => 'Superviseur de la logistique',
            'permissions' => [$this->perm1->id, $this->perm2->id],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', [
            'slug' => 'superviseur_logistique',
            'nom' => 'Superviseur Logistique',
        ]);

        $role = Role::where('slug', 'superviseur_logistique')->first();
        $this->assertEquals(2, $role->permissions()->count());
    }

    public function test_admin_peut_modifier_un_role()
    {
        $role = Role::create(['nom' => 'Chef de Projet', 'slug' => 'chef_projet']);

        $response = $this->actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'nom' => 'Chef de Projet IT',
            'slug' => 'chef_projet_it',
            'description' => 'Gestion de projets informatique',
            'permissions' => [$this->perm1->id],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'slug' => 'chef_projet_it',
            'nom' => 'Chef de Projet IT',
        ]);
    }
}
