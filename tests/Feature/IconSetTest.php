<?php

use App\Models\User;
use App\Support\Icons;

it('résout les noms Lucide et les anciens noms Font Awesome', function () {
    expect(Icons::resolve('pickaxe'))->toBe('pickaxe')
        ->and(Icons::resolve('edit'))->toBe('pencil')
        ->and(Icons::resolve('fa-users'))->toBe('users')
        ->and(Icons::resolve('fas fa-cog'))->toBe('settings')
        ->and(Icons::inner('house'))->toContain('<path');
});

it('retombe sur l’icône neutre pour un nom inconnu ou dangereux', function (?string $name) {
    expect(Icons::resolve($name))->toBe(Icons::FALLBACK);
})->with(['inconnue', '../../.env', 'house"><script>', null, '']);

it('ne charge plus aucune bibliothèque d’icônes depuis Internet', function () {
    $admin = User::factory()->superAdmin()->create();

    foreach ([route('admin.dashboard'), route('intranet.dashboard')] as $url) {
        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertDontSee('font-awesome', false)
            ->assertDontSee('class="fas ', false)
            ->assertSee('favicon.ico', false);
    }
});

it('fournit un favicon non vide', function () {
    expect(filesize(public_path('favicon.ico')))->toBeGreaterThan(0);
});
