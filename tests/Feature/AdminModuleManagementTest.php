<?php

use App\Models\AppSetting;
use App\Models\Department;
use App\Models\User;
use App\Support\IntranetModuleRegistry;

it('enables all managed modules by default', function () {
    $modules = app(IntranetModuleRegistry::class)->all();

    expect($modules)->toHaveCount(5)
        ->and(collect($modules)->every(fn (array $module) => $module['enabled']))->toBeTrue();
});

it('lets only system administrators update module availability', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.modules.index'))
        ->assertOk()
        ->assertSee('Activation des modules')
        ->assertSee('Base de connaissances');

    $this->actingAs($user)
        ->put(route('admin.modules.update', 'knowledge_base'), ['enabled' => '0'])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('admin.modules.update', 'knowledge_base'), ['enabled' => '0'])
        ->assertRedirect(route('admin.modules.index'))
        ->assertSessionHas('success');

    expect(AppSetting::value('intranet_module_knowledge_base_enabled'))->toBe('0');
});

it('blocks disabled module routes without preventing access to administration', function () {
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);

    $routes = [
        'time_off' => 'intranet.time-off.index',
        'resources' => 'intranet.resources',
        'knowledge_base' => 'intranet.knowledge-base.index',
        'forms' => 'intranet.forms.index',
        'tickets' => 'intranet.tickets.create',
    ];

    foreach ($routes as $module => $route) {
        app(IntranetModuleRegistry::class)->setEnabled($module, false);
        $this->get(route($route))->assertNotFound();
        if ($module === 'resources') {
            $this->get(route('intranet.forms.index'))
                ->assertOk()
                ->assertDontSee(route('intranet.resources'), false);
        }
        app(IntranetModuleRegistry::class)->setEnabled($module, true);
        expect($this->get(route($route))->status())->toBe(200, "Module [{$module}] should be enabled.");
    }

    $this->get(route('admin.modules.index'))->assertOk();
});

it('hides department services when the forms module is disabled', function () {
    $admin = User::factory()->superAdmin()->create();
    $department = Department::factory()->create(['code' => 'OPS']);
    app(IntranetModuleRegistry::class)->setEnabled('forms', false);

    $this->actingAs($admin)
        ->get(route('intranet.departments.show', 'ops'))
        ->assertOk()
        ->assertSee('Équipe')
        ->assertDontSee('Gérer les services')
        ->assertDontSee('Formulaires');
});

it('rejects unknown modules and invalid persisted module state', function () {
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);

    $this->put(route('admin.modules.update', 'unknown'), ['enabled' => '1'])->assertNotFound();
    $this->put(route('admin.modules.update', 'forms'), ['enabled' => 'yes'])->assertSessionHasErrors('enabled');

    AppSetting::saveValues(['intranet_module_forms_enabled' => 'unexpected']);

    expect(fn () => app(IntranetModuleRegistry::class)->enabled('forms'))
        ->toThrow(UnexpectedValueException::class);
});
