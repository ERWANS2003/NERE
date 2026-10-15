<?php

use App\Models\MealLocation;
use App\Models\User;
use App\Models\WeeklyMenu;
use App\Models\WeeklyMenuChoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function createWeeklyMenuForTest(MealLocation $location, User $creator, string $status = WeeklyMenu::PUBLISHED): WeeklyMenu
{
    $weekStart = CarbonImmutable::now()->startOfWeek()->addWeek();
    $menu = WeeklyMenu::create([
        'meal_location_id' => $location->id,
        'created_by' => $creator->id,
        'week_start' => $weekStart,
        'week_end' => $weekStart->addDays(4),
        'response_deadline' => now()->addDays(2),
        'status' => $status,
        'published_at' => $status === WeeklyMenu::PUBLISHED ? now() : null,
    ]);

    foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'] as $index => $dayName) {
        $menu->days()->create([
            'menu_date' => $weekStart->addDays($index),
            'day_name' => $dayName,
            'menu_1' => 'Menu 1 du jour',
            'menu_2' => 'Menu 2 du jour',
            'dessert' => 'Fruit',
        ]);
    }

    return $menu;
}

it('renders the admin menu pages and exports a workbook', function () {
    $admin = User::factory()->superAdmin()->create();
    $location = MealLocation::create([
        'name' => 'Bureaux de test',
        'code' => 'TEST',
        'is_active' => true,
    ]);
    $menu = createWeeklyMenuForTest($location, $admin);

    $this->actingAs($admin)
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertSee('Menus hebdomadaires');

    $this->get(route('admin.menus.create'))
        ->assertOk()
        ->assertSee('Période et destinataires');

    $this->get(route('admin.menus.show', $menu))
        ->assertOk()
        ->assertSee('Réponses incomplètes');

    $this->get(route('admin.monitoring'))
        ->assertOk()
        ->assertSee('20 dernières actions');

    $export = $this->get(route('admin.menus.export', $menu))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($export->streamedContent())->toStartWith('PK');
});

it('lets an employee save choices only for their assigned location', function () {
    $admin = User::factory()->superAdmin()->create();
    $location = MealLocation::create([
        'name' => 'Bureaux de test',
        'code' => 'TEST',
        'is_active' => true,
    ]);
    $otherLocation = MealLocation::create([
        'name' => 'Site de test',
        'code' => 'TEST-SITE',
        'is_active' => true,
    ]);
    $menu = createWeeklyMenuForTest($location, $admin);
    $employee = User::factory()->create(['meal_location_id' => $location->id]);
    $otherEmployee = User::factory()->create(['meal_location_id' => $otherLocation->id]);

    $choices = $menu->days->mapWithKeys(fn ($day) => [$day->id => WeeklyMenuChoice::MENU_1])->all();

    $this->actingAs($employee)
        ->get(route('intranet.menu.index'))
        ->assertOk()
        ->assertSee('Menu du personnel');

    $this->post(route('intranet.menu.store', $menu), ['choices' => $choices])
        ->assertRedirect(route('intranet.menu.index'));

    expect(DB::table('weekly_menu_choices')->where('user_id', $employee->id)->count())->toBe(5);

    $this->actingAs($otherEmployee)
        ->post(route('intranet.menu.store', $menu), ['choices' => $choices])
        ->assertForbidden();
});
