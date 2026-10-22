<?php

use App\Models\MenuCampaign;
use App\Models\MenuRegistration;
use App\Models\Role;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

function createOpenMenuCampaign(array $attributes = []): MenuCampaign
{
    $campaign = MenuCampaign::create([
        'title' => 'Menu de la semaine',
        'link_token' => str_repeat('m', 64),
        'starts_on' => '2026-10-19',
        'ends_on' => '2026-10-23',
        'status' => 'open',
        ...$attributes,
    ]);

    foreach (range(0, 4) as $offset) {
        $campaign->days()->create([
            'date' => now()->parse('2026-10-19')->addDays($offset)->toDateString(),
            'menu_one' => 'Menu un du jour '.($offset + 1),
            'menu_two' => 'Menu deux du jour '.($offset + 1),
            'dessert' => 'Fruit du jour '.($offset + 1),
        ]);
    }

    return $campaign;
}

function createMenuManager(array $attributes = []): User
{
    $manager = User::factory()->create([
        'guid' => 'menu-manager-guid',
        'domain' => 'default',
        ...$attributes,
    ]);
    $manager->roles()->attach(Role::where('slug', 'gestionnaire-menu')->firstOrFail());

    return $manager;
}

it('requires an Active Directory-authenticated session and denies employees menu management', function () {
    $campaign = createOpenMenuCampaign();
    $closedCampaign = createOpenMenuCampaign([
        'title' => 'Semaine clôturée',
        'link_token' => str_repeat('c', 64),
        'status' => 'closed',
    ]);
    $employee = User::factory()->create([
        'guid' => 'employee-guid',
        'domain' => 'default',
    ]);

    $this->actingAs($employee)
        ->withSession(['auth_provider' => 'local'])
        ->get(route('menu.portal'))
        ->assertForbidden();

    $this->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.portal'))
        ->assertOk()
        ->assertSee('Menus de la semaine')
        ->assertSee($campaign->title)
        ->assertDontSee($closedCampaign->title)
        ->assertSee(route('menu.registration.show', $campaign->link_token));

    $this->actingAs($employee)
        ->withSession(['auth_provider' => 'local'])
        ->get(route('menu.registration.show', $campaign->link_token))
        ->assertForbidden();

    $this->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.registration.show', $campaign->link_token))
        ->assertOk()
        ->assertSee($employee->name);

    $this->get(route('menu.manager.campaigns'))->assertForbidden();
    $this->get(route('menu.manager.export', $campaign))->assertForbidden();

    $manager = createMenuManager();
    $this->actingAs($manager)
        ->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.manager.campaigns'))
        ->assertOk()
        ->assertSee('Semaines de menus');
});

it('marks local password sessions so they cannot use the AD-only menu module', function () {
    $employee = User::factory()->create([
        'guid' => 'local-session-guid',
        'domain' => 'default',
        'password' => 'Password123!',
    ]);
    $campaign = createOpenMenuCampaign();

    $this->post(route('login'), [
        'identifiant' => $employee->email,
        'password' => 'Password123!',
    ])->assertRedirect(route('intranet.dashboard'));

    expect(session('auth_provider'))->toBe('local');

    $this->get(route('menu.registration.show', $campaign->link_token))->assertForbidden();
});

it('creates a shareable menu week with weekday choices and a fixed dessert per day', function () {
    $manager = createMenuManager();
    $this->actingAs($manager)->withSession(['auth_provider' => 'active_directory']);

    $this->get(route('menu.manager.create'))
        ->assertOk()
        ->assertSee('Préparer les menus');

    $days = collect(range(0, 4))->mapWithKeys(fn (int $offset) => [
        $offset => [
            'date' => now()->startOfWeek()->addWeek()->addDays($offset)->toDateString(),
            'menu_one' => 'Plat poisson '.$offset,
            'menu_two' => 'Plat viande '.$offset,
            'dessert' => 'Dessert '.$offset,
        ],
    ])->all();

    $this->post(route('menu.manager.store'), [
        'title' => 'Menu du personnel du 19 au 23 octobre',
        'registration_closes_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        'status' => 'open',
        'days' => $days,
    ])->assertRedirect(route('menu.manager.campaigns'))
        ->assertSessionHas('success');

    $campaign = MenuCampaign::with('days')->firstOrFail();
    expect($campaign->status)->toBe('open')
        ->and(strlen($campaign->link_token))->toBe(64)
        ->and($campaign->days)->toHaveCount(5)
        ->and($campaign->days->first()->menu_one)->toBe('Plat poisson 0')
        ->and($campaign->days->first()->dessert)->toBe('Dessert 0');

    $this->get(route('menu.manager.campaigns'))
        ->assertOk()
        ->assertSee(route('menu.registration.show', $campaign->link_token));
});

it('saves optional choices independently by day without exposing other employees', function () {
    $campaign = createOpenMenuCampaign();
    $firstDay = $campaign->days()->orderBy('date')->firstOrFail();
    $secondDay = $campaign->days()->orderBy('date')->skip(1)->firstOrFail();
    $employee = User::factory()->create([
        'guid' => 'employee-one-guid',
        'domain' => 'default',
    ]);

    $this->actingAs($employee)->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.registration.show', $campaign->link_token))
        ->assertOk()
        ->assertSee($employee->name)
        ->assertSee('Votre département')
        ->assertSee('Menu 1')
        ->assertSee('Dessert : Fruit du jour 1');

    $this->post(route('menu.registration.store', $campaign->link_token), [
        'department' => 'Finances',
        'choices' => [
            $firstDay->id => '2',
            $secondDay->id => '',
        ],
    ])->assertRedirect(route('menu.registration.show', $campaign->link_token))
        ->assertSessionHas('success');

    $registration = MenuRegistration::with('choices')->firstOrFail();
    expect($registration->employee_name)->toBe($employee->name)
        ->and($registration->employee_identifier)->toBe('default|employee-one-guid')
        ->and($registration->department)->toBe('Finances')
        ->and($registration->choices)->toHaveCount(1)
        ->and($registration->choices->first()->selected_menu)->toBe(2);

    $otherEmployee = User::factory()->create([
        'guid' => 'employee-two-guid',
        'domain' => 'default',
    ]);
    $this->actingAs($otherEmployee)
        ->withSession(['auth_provider' => 'active_directory'])
        ->post(route('menu.registration.store', $campaign->link_token), [
            'department' => 'Administration',
            'choices' => [$firstDay->id => '1'],
        ])->assertRedirect();

    $this->get(route('menu.registration.show', $campaign->link_token))
        ->assertOk()
        ->assertDontSee($employee->name)
        ->assertSee($otherEmployee->name);

    $this->actingAs($employee)
        ->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.registration.show', $campaign->link_token))
        ->assertOk()
        ->assertSee('Finances')
        ->assertSee('value="2" checked', false);
});

it('rejects menu choices from other campaigns and prevents registrations after closure', function () {
    $campaign = createOpenMenuCampaign();
    $otherCampaign = createOpenMenuCampaign([
        'title' => 'Autre semaine',
        'link_token' => str_repeat('n', 64),
    ]);
    $foreignDay = $otherCampaign->days()->firstOrFail();
    $employee = User::factory()->create([
        'guid' => 'employee-guid',
        'domain' => 'default',
    ]);

    $this->actingAs($employee)->withSession(['auth_provider' => 'active_directory'])
        ->post(route('menu.registration.store', $campaign->link_token), [
            'department' => 'Finances',
            'choices' => [$foreignDay->id => '1'],
        ])->assertSessionHasErrors('choices');

    $campaign->update(['status' => 'closed']);
    $this->post(route('menu.registration.store', $campaign->link_token), [
        'department' => 'Finances',
        'choices' => [],
    ])->assertStatus(409);

    $draft = createOpenMenuCampaign([
        'title' => 'Brouillon privé',
        'link_token' => str_repeat('d', 64),
        'status' => 'draft',
    ]);
    $this->get(route('menu.registration.show', $draft->link_token))->assertNotFound();
});

it('closes an open campaign only for a menu manager', function () {
    $campaign = createOpenMenuCampaign();
    $manager = createMenuManager();

    $this->actingAs($manager)->withSession(['auth_provider' => 'active_directory'])
        ->post(route('menu.manager.close', $campaign))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($campaign->fresh()->status)->toBe('closed');
});

it('exports daily menus grouped by department with totals and two unexpected meals', function () {
    $campaign = createOpenMenuCampaign();
    $monday = $campaign->days()->orderBy('date')->firstOrFail();
    $financeEmployee = User::factory()->create([
        'name' => 'Awa Finance',
        'guid' => 'finance-guid',
        'domain' => 'default',
    ]);
    $adminEmployee = User::factory()->create([
        'name' => 'Bala Administration',
        'guid' => 'admin-guid',
        'domain' => 'default',
    ]);
    $financeRegistration = $campaign->registrations()->create([
        'user_id' => $financeEmployee->id,
        'employee_name' => $financeEmployee->name,
        'employee_identifier' => 'default|finance-guid',
        'department' => 'Finances',
    ]);
    $adminRegistration = $campaign->registrations()->create([
        'user_id' => $adminEmployee->id,
        'employee_name' => $adminEmployee->name,
        'employee_identifier' => 'default|admin-guid',
        'department' => 'Administration',
    ]);
    $financeRegistration->choices()->create([
        'menu_day_id' => $monday->id,
        'selected_menu' => 2,
    ]);
    $adminRegistration->choices()->create([
        'menu_day_id' => $monday->id,
        'selected_menu' => 1,
    ]);

    $employee = User::factory()->create([
        'guid' => 'ordinary-employee-guid',
        'domain' => 'default',
    ]);
    $this->actingAs($employee)
        ->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.manager.export', $campaign))
        ->assertForbidden();

    $manager = createMenuManager();
    $response = $this->actingAs($manager)
        ->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.manager.export', $campaign))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertHeader('content-disposition');

    $xlsx = $response->streamedContent();
    expect(substr($xlsx, 0, 2))->toBe('PK');
    $path = tempnam(sys_get_temp_dir(), 'menu-xlsx-');
    expect($path)->not->toBeFalse();

    try {
        expect(file_put_contents($path, $xlsx))->toBe(strlen($xlsx));
        $workbook = IOFactory::load($path);
        $daily = $workbook->getSheetByName('2026-10-19');
        $summary = $workbook->getSheetByName('Récapitulatif');

        expect($workbook->getSheetCount())->toBe(6)
            ->and($daily)->not->toBeNull()
            ->and($daily->getCell('A8')->getValue())->toBe('Administration')
            ->and($daily->getCell('B9')->getValue())->toBe('Bala Administration')
            ->and($daily->getCell('A10')->getValue())->toBe('Finances')
            ->and($daily->getCell('B11')->getValue())->toBe('Awa Finance')
            ->and($daily->getCell('C9')->getValue())->toBe($monday->menu_one)
            ->and($daily->getCell('C11')->getValue())->toBe($monday->menu_two)
            ->and($daily->getCell('D9')->getValue())->toBe($monday->dessert)
            ->and($daily->getCell('D17')->getValue())->toBe(2)
            ->and($daily->getCell('D18')->getValue())->toBe(2)
            ->and($daily->getCell('D19')->getValue())->toBe(4)
            ->and($daily->getCell('D20')->getValue())->toBe(4)
            ->and($summary->getCell('F4')->getValue())->toBe(2)
            ->and($summary->getCell('F5')->getValue())->toBe(2)
            ->and($summary->getCell('F6')->getValue())->toBe(4)
            ->and($summary->getCell('F7')->getValue())->toBe(4)
            ->and($summary->getCell('F8')->getValue())->toBe(1)
            ->and($summary->getCell('F9')->getValue())->toBe(1)
            ->and($summary->getCell('F10')->getValue())->toBe(2)
            ->and($summary->getCell('F11')->getValue())->toBe(2);

        $workbook->disconnectWorksheets();
    } finally {
        unlink($path);
    }
});
