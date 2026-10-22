<?php

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;
use Database\Seeders\ResourceSeeder;

it('seeds useful intranet links idempotently', function () {
    $user = User::factory()->create();

    $seeder = new ResourceSeeder;
    $seeder->run();
    $seeder->run();

    $resources = Resource::query()->where('file_type', 'link')->get();

    expect($resources)->toHaveCount(4)
        ->and($resources->pluck('title')->all())->toContain(
            'Catalogue des formulaires de service',
            'Base de connaissances',
            'Créer une demande de support informatique',
            'Annuaire des départements',
        );

    $this->actingAs($user)
        ->get(route('intranet.resources'))
        ->assertOk()
        ->assertSee('Catalogue des formulaires de service')
        ->assertSee('Base de connaissances')
        ->assertSee('Lien interne');
});

it('opens seeded internal links from the resource detail page', function () {
    $user = User::factory()->create();
    $category = ResourceCategory::create([
        'name' => 'Accès rapides',
        'slug' => 'quick-access',
        'order' => 1,
    ]);
    $resource = Resource::create([
        'title' => 'Catalogue des formulaires',
        'category_id' => $category->id,
        'file_type' => 'link',
        'file_path' => '/intranet/forms',
        'uploaded_by' => $user->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('intranet.resources.show', $resource))
        ->assertOk()
        ->assertSee('Ouvrir');

    $this->get(route('intranet.resources.download', $resource))
        ->assertRedirect('/intranet/forms');
});

it('does not redirect resource links outside the intranet', function () {
    $user = User::factory()->create();
    $category = ResourceCategory::create([
        'name' => 'Accès rapides',
        'slug' => 'quick-access',
        'order' => 1,
    ]);
    $resource = Resource::create([
        'title' => 'Lien invalide',
        'category_id' => $category->id,
        'file_type' => 'link',
        'file_path' => 'https://example.com',
        'uploaded_by' => $user->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('intranet.resources.download', $resource))
        ->assertNotFound();
});
