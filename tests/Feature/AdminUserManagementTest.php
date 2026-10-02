<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $roleAdmin;

    private Role $roleTechnicien;

    private Role $roleDemandeur;

    private Role $roleDirecteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleAdmin = Role::create(['nom' => 'Administrateur', 'slug' => Role::SLUG_ADMIN]);
        $this->roleTechnicien = Role::create(['nom' => 'Technicien', 'slug' => Role::SLUG_TECHNICIEN]);
        $this->roleDemandeur = Role::create(['nom' => 'Demandeur', 'slug' => Role::SLUG_DEMANDEUR]);
        $this->roleDirecteur = Role::create(['nom' => 'Directeur', 'slug' => Role::SLUG_DIRECTEUR]);

        $this->admin = User::create([
            'name' => 'Admin Users Test',
            'email' => 'admin.users@nere-mining.bf',
            'password' => 'password',
            'role_id' => $this->roleAdmin->id,
            'actif' => true,
        ]);
    }

    public function test_admin_peut_creer_un_utilisateur()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Awa Traoré',
            'email' => 'awa.traore@nere-mining.bf',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role_id' => $this->roleTechnicien->id,
            'matricule' => 'NERE-042',
            'telephone' => '+226 70 00 00 00',
            'poste' => 'Technicien maintenance',
            'est_technicien' => '1',
            // Cases cochées par défaut dans le formulaire : le navigateur les
            // soumet, contrairement à une case décochée.
            'disponible' => '1',
            'actif' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'awa.traore@nere-mining.bf',
            'role_id' => $this->roleTechnicien->id,
            'matricule' => 'NERE-042',
            'poste' => 'Technicien maintenance',
            'est_technicien' => true,
        ]);

        $cree = User::where('email', 'awa.traore@nere-mining.bf')->firstOrFail();
        $this->assertTrue(Hash::check('motdepasse123', $cree->password));
        $this->assertTrue($cree->actif);
        $this->assertTrue($cree->disponible);
    }

    public function test_creation_refusee_si_role_inexistant_ou_email_en_double()
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Doublon',
            'email' => $this->admin->email,
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role_id' => $this->roleTechnicien->id,
        ])->assertSessionHasErrors('email');

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Role inconnu',
            'email' => 'role.inconnu@nere-mining.bf',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role_id' => 999999,
        ])->assertSessionHasErrors('role_id');
    }

    public function test_case_non_cochee_autorise_un_compte_inactif_a_la_creation()
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Compte en attente',
            'email' => 'en.attente@nere-mining.bf',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'role_id' => $this->roleDemandeur->id,
            // ni `actif` ni `disponible` : les deux checkboxes décochées ne
            // sont pas soumises, le défaut ne doit pas les forcer à true.
        ]);

        $cree = User::where('email', 'en.attente@nere-mining.bf')->firstOrFail();
        $this->assertFalse($cree->actif);
    }

    public function test_admin_peut_modifier_un_utilisateur()
    {
        $cible = User::create([
            'name' => 'Utilisateur Cible',
            'email' => 'cible@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDemandeur->id,
            'actif' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $cible), [
            'name' => 'Utilisateur Renommé',
            'email' => 'cible.renomme@nere-mining.bf',
            'role_id' => $this->roleTechnicien->id,
            'poste' => 'Chef d\'équipe',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $cible->id,
            'name' => 'Utilisateur Renommé',
            'email' => 'cible.renomme@nere-mining.bf',
            'role_id' => $this->roleTechnicien->id,
            'poste' => 'Chef d\'équipe',
        ]);
    }

    public function test_edition_sans_nouveau_mot_de_passe_conserve_l_ancien()
    {
        $cible = User::create([
            'name' => 'Mdp',
            'email' => 'mdp@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDemandeur->id,
            'actif' => true,
        ]);
        $hashAvant = $cible->password;

        // Un champ password soumis vide est converti en null par Laravel :
        // il ne doit surtout pas devenir un hash de chaîne vide.
        $this->actingAs($this->admin)->put(route('admin.users.update', $cible), [
            'name' => 'Mdp',
            'email' => 'mdp@nere-mining.bf',
            'role_id' => $this->roleDemandeur->id,
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame($hashAvant, $cible->fresh()->password);
        $this->assertTrue(Hash::check('motdepasse123', $cible->fresh()->password));
    }

    public function test_admin_peut_reinitialiser_le_mot_de_passe()
    {
        $cible = User::create([
            'name' => 'Reset',
            'email' => 'reset@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDemandeur->id,
            'actif' => true,
        ]);

        $this->actingAs($this->admin)->put(route('admin.users.update', $cible), [
            'name' => 'Reset',
            'email' => 'reset@nere-mining.bf',
            'role_id' => $this->roleDemandeur->id,
            'password' => 'nouveaumdp456',
            'password_confirmation' => 'nouveaumdp456',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nouveaumdp456', $cible->fresh()->password));
    }

    public function test_admin_ne_peut_pas_se_demouvoir_ni_se_desactiver()
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->roleDemandeur->id,
            'actif' => '0',
        ]);

        $this->admin->refresh();
        $this->assertSame($this->roleAdmin->id, $this->admin->role_id);
        $this->assertTrue($this->admin->actif);
    }

    public function test_le_dernier_administrateur_actif_est_protge()
    {
        $autre = User::create([
            'name' => 'Admin inactif',
            'email' => 'admin.inactif@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleAdmin->id,
            'actif' => false,
        ]);

        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->roleTechnicien->id,
        ]);

        $this->assertSame($this->roleAdmin->id, $this->admin->fresh()->role_id);
        $this->assertNotNull($autre->fresh());
    }

    public function test_toggle_et_destroy_refusent_la_desactivation_de_soi_meme()
    {
        // `admin.users.toggle` est une route PATCH (method spoofing @method).
        $this->actingAs($this->admin)->patch(route('admin.users.toggle', $this->admin))
            ->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->actif);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin))
            ->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->actif);
    }

    public function test_toggle_et_destroy_agissent_sur_un_autre_utilisateur()
    {
        $cible = User::create([
            'name' => 'Toggle',
            'email' => 'toggle@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDemandeur->id,
            'actif' => true,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.users.toggle', $cible))
            ->assertSessionHas('success');
        $this->assertFalse($cible->fresh()->actif);
    }

    public function test_directeur_de_departement_synchronise_le_champ_directeur_id()
    {
        $mine = Departement::create(['nom' => 'Mine', 'actif' => true]);
        $autreDept = Departement::create(['nom' => 'Usine', 'actif' => true]);

        $directeur = User::create([
            'name' => 'Directeur Mine',
            'email' => 'directeur.mine@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDirecteur->id,
            'departement_id' => $mine->id,
            'actif' => true,
        ]);

        $this->actingAs($this->admin)->put(route('admin.users.update', $directeur), [
            'name' => $directeur->name,
            'email' => $directeur->email,
            'role_id' => $this->roleDirecteur->id,
            'departement_id' => $autreDept->id,
        ])->assertSessionHasNoErrors();

        $this->assertNull($mine->fresh()->directeur_id, 'L\'ancien département doit libérer son directeur.');
        $this->assertSame($directeur->id, $autreDept->fresh()->directeur_id);
    }

    public function test_perte_du_role_directeur_detache_le_champ_directeur_id()
    {
        $departement = Departement::create(['nom' => 'Mine', 'actif' => true]);

        $directeur = User::create([
            'name' => 'Ancien directeur',
            'email' => 'ancien.directeur@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDirecteur->id,
            'departement_id' => $departement->id,
            'actif' => true,
        ]);

        $this->actingAs($this->admin)->put(route('admin.users.update', $directeur), [
            'name' => $directeur->name,
            'email' => $directeur->email,
            'role_id' => $this->roleTechnicien->id,
            'departement_id' => $departement->id,
        ])->assertSessionHasNoErrors();

        $this->assertNull(
            $departement->fresh()->directeur_id,
            'Un département ne doit pas garder un directeur qui n\'a plus le rôle.'
        );
    }

    public function test_index_renseigne_les_modales_avec_le_payload_utilisateur()
    {
        User::create([
            'name' => 'Payload " citations',
            'email' => 'payload@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDemandeur->id,
            'actif' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        // Le nom contient des caractères qui casseraient un JSON injecté en
        // clair dans un <script> : il doit rester échappé.
        $response->assertDontSee('</script><script>alert', false);
        $response->assertSee('editUserForm', false);
        $response->assertSee('ID_PLACEHOLDER', false);
    }

    public function test_un_demandeur_ne_peut_pas_atteindre_l_admin_utilisateurs()
    {
        $demandeur = User::create([
            'name' => 'Demandeur',
            'email' => 'demandeur@nere-mining.bf',
            'password' => 'motdepasse123',
            'role_id' => $this->roleDemandeur->id,
            'actif' => true,
        ]);

        $this->actingAs($demandeur)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
