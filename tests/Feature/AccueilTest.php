<?php

use App\Enums\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Page d'accueil et fiche de service.
 *
 * L'enjeu de ces tests est l'absence de fuite : un agent ne doit pas decouvrir
 * l'existence d'un service auquel il n'est pas affecte, ni par la liste de
 * l'accueil, ni par le code d'erreur d'une URL devinee. C'est pourquoi le 404
 * est explicitement verifie sur une route hors perimetre.
 */

it('exige une session ouverte', function () {
    $this->get('/accueil')->assertRedirect('/connexion');
});

it('n affiche que les services de l agent', function () {
    $informatique = Department::factory()->create(['name' => 'Informatique']);
    $hse = Department::factory()->create(['name' => 'HSE']);

    // Ce service existe bien en base mais l'agent n'y est pas affecte : il ne
    // doit apparaitre ni dans la liste ni via son URL.
    $direction = Department::factory()->create(['name' => 'Direction generale']);

    $utilisateur = User::factory()
        ->withRole($informatique, DepartmentRole::User)
        ->withRole($hse, DepartmentRole::Technician, 1)
        ->create();

    $this->actingAs($utilisateur)
        ->get('/accueil')
        ->assertOk()
        ->assertSee('Informatique')
        ->assertSee('HSE')
        ->assertDontSee('Direction generale');

    expect(Department::count())->toBe(3)
        ->and($direction->exists)->toBeTrue();

    $this->actingAs($utilisateur)
        ->get('/departements/'.$direction->code)
        ->assertNotFound();
});

it('masque un service desactive', function () {
    $service = Department::factory()->inactive()->create(['name' => 'Service arrete']);

    $utilisateur = User::factory()->withRole($service, DepartmentRole::User)->create();

    $this->actingAs($utilisateur)
        ->get('/accueil')
        ->assertOk()
        ->assertDontSee('Service arrete');
});

it('montre tous les services a l administrateur general', function () {
    Department::factory()->create(['name' => 'Informatique']);
    Department::factory()->create(['name' => 'HSE']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get('/accueil')
        ->assertOk()
        ->assertSee('Informatique')
        ->assertSee('HSE');
});

it('explique l attente quand aucun service n est affecte', function () {
    $this->actingAs(User::factory()->create())
        ->get('/accueil')
        ->assertOk()
        ->assertSee('Aucun service ne vous est encore affecté');
});

it('annonce le role de l agent sur chaque service', function () {
    $informatique = Department::factory()->create();

    $utilisateur = User::factory()
        ->withRole($informatique, DepartmentRole::Technician, 2)
        ->create();

    $this->actingAs($utilisateur)
        ->get('/accueil')
        ->assertOk()
        ->assertSee('Technicien');
});

it('ouvre la fiche d un service autorise', function () {
    $informatique = Department::factory()->create([
        'code' => 'IT',
        'name' => 'Informatique',
        'tag' => 'Informatique',
        'description' => 'Ordinateurs, reseau et logiciels.',
    ]);

    $utilisateur = User::factory()->withRole($informatique, DepartmentRole::User)->create();

    $this->actingAs($utilisateur)
        ->get('/departements/IT')
        ->assertOk()
        ->assertSee('Informatique')
        ->assertSee('Ordinateurs, reseau et logiciels.')
        ->assertSee('Utilisateur');
});

it('renvoie 404 sur un service auquel l agent n a pas acces', function () {
    // Ni 403 ni 200 : un 403 confirmerait au demandeur que le service existe.
    $hse = Department::factory()->create(['code' => 'HSE', 'name' => 'HSE']);

    $this->actingAs(User::factory()->create())
        ->get('/departements/'.strtolower($hse->code))
        ->assertNotFound();
});

it('renvoie 404 sur un service desactive', function () {
    $service = Department::factory()->inactive()->create(['code' => 'OLD']);

    $utilisateur = User::factory()->superAdmin()->create();

    $this->actingAs($utilisateur)
        ->get('/departements/OLD')
        ->assertNotFound();
});

it('refuse un code de service hors alphabet', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get('/departements/1-union-select')
        ->assertNotFound();
});

it('refuse un code de service trop long', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get('/departements/'.str_repeat('A', 17))
        ->assertNotFound();
});

it('reprend le code metier dans l url du service', function () {
    $service = Department::factory()->create(['code' => 'FIN']);

    $utilisateur = User::factory()->superAdmin()->create();

    $this->actingAs($utilisateur)
        ->get('/accueil')
        ->assertOk()
        ->assertSee(route('departement.show', ['code' => 'FIN']), escape: false);
});

/*
 * Referentiel
 */

it('installe les six services du portail', function () {
    $this->seed(DepartmentsSeeder::class);

    expect(Department::count())->toBe(6)
        ->and(Department::pluck('code')->all())
        ->toContain('IT', 'RH', 'HSE', 'MNT', 'FIN', 'LOG');
});

it('donne a chaque service une description accentuee exploitable', function () {
    $this->seed(DepartmentsSeeder::class);

    $avecAccent = 0;

    foreach (Department::all() as $service) {
        expect($service->description)->not->toBeEmpty()
            // Une description peut legitimement ne porter aucun accent : ce qu'on
            // verifie, c'est qu'aucune n'a ete abimee par un aller-retour en
            // latin1, et qu'au moins une accent est bien preserve de bout en bout.
            ->and(mb_check_encoding($service->description, 'UTF-8'))->toBeTrue()
            // Les deux aiguilles sont ecrites en echappement : une source qui
            // porterait reellement ces octets ressemblerait a du contenu abime.
            ->and($service->description)->not->toContain("\u{00C3}", "\u{00C2}");

        if (Str::ascii($service->description) !== $service->description) {
            $avecAccent++;
        }
    }

    expect($avecAccent)->toBeGreaterThan(0);
});

it('ne republie pas un service desactive par l administration', function () {
    $this->seed(DepartmentsSeeder::class);

    // L'administration a coupe le service et renomme l'entite.
    Department::where('code', 'IT')->update([
        'is_active' => false,
        'name' => 'Informatique (site principal)',
    ]);

    $this->seed(DepartmentsSeeder::class);

    $service = Department::where('code', 'IT')->first();

    // Le libelle suit le referentiel, mais le service reste coupe.
    expect($service->is_active)->toBeFalse()
        ->and($service->name)->toBe('Informatique');
});

it('conserve un accent de couleur choisi par l administration', function () {
    $this->seed(DepartmentsSeeder::class);

    $personnel = '#123456';
    Department::where('code', 'RH')->update(['color' => $personnel]);

    $this->seed(DepartmentsSeeder::class);

    expect(Department::where('code', 'RH')->value('color'))->toBe($personnel);
});

it('conserve un accent de couleur invalide sans le laisser passer en HTML', function () {
    $service = Department::factory()->create(['color' => '"><script>alert(1)</script>']);

    expect($service->accentColor())->toBe(Department::ACCENT_DEFAUT)
        ->and($service->accentColor())->toMatch('/^#[0-9a-fA-F]{6}$/');
});

it('reprend un accent valide', function () {
    $service = Department::factory()->create(['color' => '#2f6f9f']);

    expect($service->accentColor())->toBe('#2f6f9f');
});

it('applique un accent par defaut quand le service n en a pas', function () {
    expect(Department::factory()->create(['color' => null])->accentColor())
        ->toBe(Department::ACCENT_DEFAUT);
});

it('ordonne les services selon la position puis le nom', function () {
    Department::factory()->create(['name' => 'Zulu', 'position' => 20]);
    Department::factory()->create(['name' => 'Alpha', 'position' => 20]);
    Department::factory()->create(['name' => 'Premier', 'position' => 10]);

    $ordre = Department::query()->ordered()->pluck('name')->all();

    expect($ordre)->toBe(['Premier', 'Alpha', 'Zulu']);
});

it('n attache aucun service en base lors du premier demarrage', function () {
    // Le seeder ne doit rien ecrire dans le pivot : les affectations viennent
    // des RH, pas du deploiement.
    $this->seed(DepartmentsSeeder::class);

    expect(DB::table('department_user')->count())->toBe(0);
});
