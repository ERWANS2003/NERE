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

it('allows local and Active Directory employees to use menus but denies employees menu management', function () {
    $campaign = createOpenMenuCampaign();
    $closedCampaign = createOpenMenuCampaign([
        'title' => 'Semaine clôturée',
        'link_token' => str_repeat('c', 64),
        'status' => 'closed',
    ]);
    $employee = User::factory()->create([
        'menu_eligible' => true,
        'guid' => 'employee-guid',
        'domain' => 'default',
    ]);

    $this->actingAs($employee)
        ->withSession(['auth_provider' => 'local'])
        ->get(route('menu.portal'))
        ->assertOk()
        ->assertSee($campaign->title);

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
        ->assertOk()
        ->assertSee($employee->name);

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

it('allows local password sessions to register independently for the menu', function () {
    $employee = User::factory()->create([
        'menu_eligible' => true,
        'guid' => null,
        'domain' => null,
        'password' => 'Password123!',
    ]);
    $campaign = createOpenMenuCampaign();

    $this->post(route('login'), [
        'identifiant' => $employee->email,
        'password' => 'Password123!',
    ])->assertRedirect(route('intranet.dashboard'));

    expect(session('auth_provider'))->toBe('local');

    $this->get(route('menu.registration.show', $campaign->link_token))
        ->assertOk()
        ->assertSee($employee->name);

    $firstDay = $campaign->days()->orderBy('date')->firstOrFail();
    $this->post(route('menu.registration.store', $campaign->link_token), [
        'department' => 'Finances',
        'choices' => [$firstDay->id => '1'],
    ])->assertRedirect(route('menu.registration.show', $campaign->link_token))
        ->assertSessionHas('success');

    $registration = MenuRegistration::query()->firstOrFail();
    expect($registration->employee_identifier)->toBe('local|'.$employee->id)
        ->and($registration->user_id)->toBe($employee->id);

    $secondEmployee = User::factory()->create(['menu_eligible' => true]);
    $this->actingAs($secondEmployee)
        ->withSession(['auth_provider' => 'local'])
        ->post(route('menu.registration.store', $campaign->link_token), [
            'department' => 'Finances',
            'choices' => [$firstDay->id => '2'],
        ])->assertRedirect(route('menu.registration.show', $campaign->link_token))
        ->assertSessionHas('success');

    expect(MenuRegistration::query()->count())->toBe(2)
        ->and(MenuRegistration::query()->orderBy('id')->pluck('employee_identifier')->all())
        ->toBe(['local|'.$employee->id, 'local|'.$secondEmployee->id]);
});

it('shows the menu management shortcut only to menu managers and intranet administrators', function () {
    $employee = User::factory()->create();
    $this->actingAs($employee)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertDontSee(route('menu.manager.campaigns'), false);

    $manager = createMenuManager();
    $this->actingAs($manager)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Menu du personnel')
        ->assertSee(route('menu.manager.campaigns'), false);

    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee(route('menu.manager.campaigns'), false);
});

it('creates a complete Monday-to-Friday campaign by selecting one ISO week', function () {
    $manager = createMenuManager();
    $this->actingAs($manager)->withSession(['auth_provider' => 'active_directory']);

    $this->get(route('menu.manager.create'))
        ->assertOk()
        ->assertSee('Préparer les menus')
        ->assertSee('type="week"', false);

    $days = collect(range(0, 4))->mapWithKeys(fn (int $offset) => [
        $offset => [
            'menu_one' => 'Plat poisson '.$offset,
            'menu_two' => 'Plat viande '.$offset,
            'dessert' => 'Dessert '.$offset,
        ],
    ])->all();

    $this->post(route('menu.manager.store'), [
        'week' => now()->startOfWeek()->addWeek()->format('o-\WW'),
        'registration_closes_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        'status' => 'open',
        'days' => $days,
    ])->assertRedirect(route('menu.manager.campaigns'))
        ->assertSessionHas('success');

    $campaign = MenuCampaign::with('days')->firstOrFail();
    expect($campaign->status)->toBe('open')
        ->and(strlen($campaign->link_token))->toBe(64)
        ->and($campaign->starts_on->format('N'))->toBe('1')
        ->and($campaign->starts_on->diffInDays($campaign->ends_on))->toBe(4.0)
        ->and($campaign->title)->toStartWith('Menus du ')
        ->and($campaign->days)->toHaveCount(5)
        ->and($campaign->days->first()->menu_one)->toBe('Plat poisson 0')
        ->and($campaign->days->first()->date->format('N'))->toBe('1')
        ->and($campaign->days->last()->date->format('N'))->toBe('5')
        ->and($campaign->days->first()->dessert)->toBe('Dessert 0');

    $this->get(route('menu.manager.campaigns'))
        ->assertOk()
        ->assertSee(route('menu.registration.show', $campaign->link_token));
});

it('rejects invalid ISO weeks and incomplete weekday menus', function () {
    $manager = createMenuManager();
    $this->actingAs($manager)->withSession(['auth_provider' => 'active_directory']);

    $days = collect(range(0, 4))->map(fn (): array => [
        'menu_one' => 'Plat 1',
        'menu_two' => 'Plat 2',
        'dessert' => 'Fruit',
    ])->all();
    $payload = [
        'week' => '2026-W99',
        'status' => 'open',
        'days' => $days,
    ];

    $this->post(route('menu.manager.store'), $payload)
        ->assertSessionHasErrors('week');

    unset($payload['days'][4]);
    $payload['week'] = now()->startOfWeek()->format('o-\WW');
    $this->post(route('menu.manager.store'), $payload)
        ->assertSessionHasErrors('days');

    expect(MenuCampaign::query()->count())->toBe(0);
});

it('saves optional choices independently by day without exposing other employees', function () {
    $campaign = createOpenMenuCampaign();
    $firstDay = $campaign->days()->orderBy('date')->firstOrFail();
    $secondDay = $campaign->days()->orderBy('date')->skip(1)->firstOrFail();
    $employee = User::factory()->create([
        'menu_eligible' => true,
        'guid' => 'employee-one-guid',
        'domain' => 'default',
    ]);

    $this->actingAs($employee)->withSession(['auth_provider' => 'active_directory'])
        ->get(route('menu.registration.show', $campaign->link_token))
        ->assertOk()
        ->assertSee($employee->name)
        ->assertSee('Votre département')
        ->assertSee('Menu 1')
        ->assertSee('Dessert fixe : Fruit du jour 1');

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
        'menu_eligible' => true,
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
        'menu_eligible' => true,
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
        'menu_eligible' => true,
        'name' => 'Awa Finance',
        'guid' => 'finance-guid',
        'domain' => 'default',
    ]);
    $adminEmployee = User::factory()->create([
        'menu_eligible' => true,
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
    $campaign->registrations()->create([
        'user_id' => User::factory()->create([
            'name' => 'Celine Administration',
            'guid' => 'no-choice-guid',
            'domain' => 'default',
        ])->id,
        'employee_name' => 'Celine Administration',
        'employee_identifier' => 'default|no-choice-guid',
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
        'menu_eligible' => true,
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
        $daily = $workbook->getSheet(0);
        $expectedSheetTitle = mb_convert_case(
            $monday->date->locale('fr')->translatedFormat('l d M Y'),
            MB_CASE_TITLE,
            'UTF-8',
        );

        expect($workbook->getSheetCount())->toBe(5)
            ->and($daily)->not->toBeNull()
            ->and($daily->getTitle())->toBe($expectedSheetTitle)
            ->and($daily->getCell('B1')->getValue())->toContain('Lundi')
            ->and($daily->getCell('B2')->getValue())->toBe("N° D'ORDRE")
            ->and($daily->getCell('C2')->getValue())->toBe('NOM ET PRÉNOM(S)')
            ->and($daily->getCell('D2')->getValue())->toBe('OBSERVATIONS')
            ->and($daily->getCell('C3')->getValue())->toBe('Administration')
            ->and($daily->getCell('D3')->getValue())->toBe(2)
            ->and($daily->getCell('B4')->getValue())->toBe('1')
            ->and($daily->getCell('C4')->getValue())->toBe('Bala Administration')
            ->and($daily->getCell('D4')->getValue())->toBe($monday->menu_one)
            ->and($daily->getCell('C5')->getValue())->toBe('Celine Administration')
            ->and($daily->getCell('D5')->getValue())->toBeNull()
            ->and($daily->getCell('C6')->getValue())->toBe('Finances')
            ->and($daily->getCell('C7')->getValue())->toBe('Awa Finance')
            ->and($daily->getCell('D7')->getValue())->toBe($monday->menu_two)
            ->and($daily->getCell('D4')->getStyle()->getFill()->getStartColor()->getARGB())->toBe('FFFFFF00')
            ->and($daily->getCell('D7')->getStyle()->getFill()->getStartColor()->getARGB())->toBe('FF92D050')
            ->and($daily->getCell('B9')->getValue())->toBe('TOTAL = 3')
            ->and($daily->getCell('C9')->getValue())->toBe('PLATS = 2')
            ->and($daily->getCell('B10')->getValue())->toBe('MENU 1 : 1')
            ->and($daily->getCell('B11')->getValue())->toBe('MENU 2 : 1')
            ->and($daily->getCell('B12')->getValue())->toBe('IMPRÉVUS : 02 PLATS')
            ->and($daily->getCell('D13')->getValue())->toBe(2)
            ->and($daily->getCell('D14')->getValue())->toBe(2)
            ->and($daily->getCell('B15')->getValue())->toBe('TOTAL À COMMANDER')
            ->and($daily->getCell('D15')->getValue())->toBe(4)
            ->and($daily->getCell('D2')->getValue())->not->toContain('Dessert')
            ->and($workbook->getSheetByName('Récapitulatif'))->toBeNull();

        $workbook->disconnectWorksheets();
    } finally {
        unlink($path);
    }
});

it('limits menu choices to employees ticked by an administrator', function () {
    $campaign = createOpenMenuCampaign();
    $employee = User::factory()->create();

    $this->actingAs($employee)->get(route('menu.portal'))->assertForbidden();
    $this->actingAs($employee)->get(route('menu.registration.show', $campaign->link_token))->assertForbidden();
    $this->actingAs($employee)->post(route('menu.registration.store', $campaign->link_token), [
        'department' => 'Finance',
        'choices' => [],
    ])->assertForbidden();
    $this->actingAs($employee)->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertDontSee('intranet-menu-campaigns', false);

    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin)->put(route('admin.users.update', $employee), [
        'name' => $employee->name,
        'email' => $employee->email,
        'matricule' => $employee->matricule,
        'actif' => '1',
        'menu_eligible' => '1',
    ])->assertRedirect(route('admin.users.show', $employee));

    expect($employee->fresh()->menu_eligible)->toBeTrue();

    $this->actingAs($employee->fresh())->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('intranet-menu-campaigns', false)
        ->assertSee($campaign->title)
        ->assertSee(route('menu.registration.show', $campaign->link_token))
        ->assertSee('Choisir mes plats');
    $this->actingAs($employee->fresh())->get(route('menu.registration.show', $campaign->link_token))->assertOk();
});

it('shows only published menus open for registration on the dashboard', function () {
    $open = createOpenMenuCampaign(['title' => 'Semaine ouverte']);
    $draft = createOpenMenuCampaign(['title' => 'Semaine brouillon', 'status' => 'draft', 'link_token' => str_repeat('d', 64)]);
    $closed = createOpenMenuCampaign(['title' => 'Semaine close', 'registration_closes_at' => now()->subHour(), 'link_token' => str_repeat('c', 64)]);
    $employee = User::factory()->create(['menu_eligible' => true]);

    $this->actingAs($employee)->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Semaine ouverte')
        ->assertDontSee('Semaine brouillon')
        ->assertDontSee('Semaine close');
});
