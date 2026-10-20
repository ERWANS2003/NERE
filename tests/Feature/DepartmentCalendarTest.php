<?php

use App\Enums\DepartmentRole;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\User;

it('shows global and own-department calendar events but hides other department events', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $departmentMember = User::factory()->withRole($department, DepartmentRole::User)->create();
    $globalEvent = CalendarEvent::create([
        'title' => 'Réunion générale',
        'type' => 'meeting',
        'event_date' => '2026-11-02',
        'created_by' => User::factory()->superAdmin()->create()->id,
    ]);
    $departmentEvent = CalendarEvent::create([
        'title' => 'Réunion du département',
        'type' => 'meeting',
        'event_date' => '2026-11-03',
        'department_id' => $department->id,
        'created_by' => User::factory()->create()->id,
    ]);
    CalendarEvent::create([
        'title' => 'Événement confidentiel autre équipe',
        'type' => 'event',
        'event_date' => '2026-11-04',
        'department_id' => $otherDepartment->id,
        'created_by' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($departmentMember)
        ->get(route('intranet.calendar.events', [
            'start' => '2026-11-01',
            'end' => '2026-11-10',
        ]))
        ->assertOk();

    $events = collect($response->json());
    expect($events->pluck('title')->all())->toContain('Réunion générale', 'Réunion du département')
        ->and($events->pluck('title')->all())->not->toContain('Événement confidentiel autre équipe')
        ->and($events->firstWhere('id', 'event-'.$globalEvent->id)['extendedProps']['scope'])->toBe('global')
        ->and($events->firstWhere('id', 'event-'.$departmentEvent->id)['extendedProps']['scope'])->toBe('department');

    $this->get(route('api.search.data'))
        ->assertOk()
        ->assertJsonFragment(['title' => 'Réunion générale'])
        ->assertJsonFragment(['title' => 'Réunion du département'])
        ->assertJsonMissing(['title' => 'Événement confidentiel autre équipe']);
});

it('shows only global and own-department events in the dashboard agenda widget', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $member = User::factory()->withRole($department, DepartmentRole::User)->create();
    $admin = User::factory()->superAdmin()->create();

    CalendarEvent::create([
        'title' => 'Événement global du tableau de bord',
        'type' => 'event',
        'event_date' => now()->addDays(2)->toDateString(),
        'created_by' => $admin->id,
    ]);
    CalendarEvent::create([
        'title' => 'Événement de mon département',
        'type' => 'event',
        'event_date' => now()->addDays(3)->toDateString(),
        'department_id' => $department->id,
        'created_by' => $admin->id,
    ]);
    CalendarEvent::create([
        'title' => 'Événement privé d’un autre département',
        'type' => 'event',
        'event_date' => now()->addDays(1)->toDateString(),
        'department_id' => $otherDepartment->id,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($member)
        ->get(route('intranet.dashboard'))
        ->assertOk()
        ->assertSee('Événement global du tableau de bord')
        ->assertSee('Événement de mon département')
        ->assertDontSee('Événement privé d’un autre département');
});

it('includes Burkina Faso recurring public holidays and movable Easter dates offline', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('intranet.calendar.events', [
            'start' => '2026-01-01',
            'end' => '2027-01-01',
        ]))
        ->assertOk();

    $holidays = collect($response->json())
        ->filter(fn (array $event) => str_starts_with($event['id'], 'holiday-bf-'))
        ->keyBy(fn (array $event) => $event['start']);

    expect($holidays)->toHaveCount(13)
        ->and($holidays->get('2026-01-01')['title'])->toBe('Jour de l’An')
        ->and($holidays->get('2026-04-06')['title'])->toBe('Lundi de Pâques')
        ->and($holidays->get('2026-05-14')['title'])->toBe('Ascension')
        ->and($holidays->get('2026-12-25')['title'])->toBe('Noël')
        ->and($holidays->get('2026-01-01')['extendedProps']['scope'])->toBe('global');
});

it('allows department heads to create and manage events only for departments they lead', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();

    $this->actingAs($head)
        ->get(route('intranet.calendar'))
        ->assertOk()
        ->assertSee('Gérer mes événements départementaux');

    $this->actingAs($head)
        ->get(route('intranet.calendar.department-events.create'))
        ->assertOk()
        ->assertSee($department->name)
        ->assertDontSee($otherDepartment->name);

    $this->post(route('intranet.calendar.department-events.store'), [
        'title' => 'Formation sécurité',
        'description' => 'Session trimestrielle',
        'type' => 'training',
        'event_date' => '2026-11-05',
        'event_time' => '10:00',
        'department_id' => $department->id,
    ])->assertRedirect(route('intranet.calendar.department-events.index'));

    $event = CalendarEvent::where('title', 'Formation sécurité')->firstOrFail();
    expect($event->department_id)->toBe($department->id)
        ->and($event->created_by)->toBe($head->id);

    $this->put(route('intranet.calendar.department-events.update', $event), [
        'title' => 'Formation sécurité reportée',
        'type' => 'training',
        'event_date' => '2026-11-06',
        'all_day' => '1',
        'department_id' => $otherDepartment->id,
    ])->assertSessionHasErrors('department_id');

    expect($event->fresh()->title)->toBe('Formation sécurité');

    $this->actingAs(User::factory()->withRole($otherDepartment, DepartmentRole::Director)->create())
        ->get(route('intranet.calendar.department-events.edit', $event))
        ->assertForbidden();
});

it('does not allow technicians or ordinary users to manage department calendar events', function () {
    $department = Department::factory()->create();
    $technician = User::factory()->withRole($department, DepartmentRole::Technician, 1)->create();
    $ordinaryUser = User::factory()->create();

    $this->actingAs($technician)
        ->get(route('intranet.calendar.department-events.index'))
        ->assertForbidden();

    $this->actingAs($ordinaryUser)
        ->get(route('intranet.calendar.department-events.create'))
        ->assertForbidden();
});

it('restricts global announcement creation to system administrators', function () {
    $department = Department::factory()->create();
    $head = User::factory()->withRole($department, DepartmentRole::Director)->create();

    $this->actingAs($head)
        ->get(route('intranet.announcements.create'))
        ->assertForbidden();

    $this->post(route('intranet.announcements.store'), [
        'title' => 'Annonce départementale',
        'body' => 'Contenu',
        'priority' => 'info',
        'department_id' => $department->id,
    ])->assertForbidden();
});

it('keeps legacy department-tagged announcements visible to all users', function () {
    $department = Department::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $userOutsideDepartment = User::factory()->create();
    Announcement::create([
        'title' => 'Communication désormais globale',
        'body' => 'Information pour toute l’entreprise.',
        'priority' => 'info',
        'department_id' => $department->id,
        'created_by' => $admin->id,
        'is_active' => true,
        'published_at' => now(),
    ]);

    $this->actingAs($userOutsideDepartment)
        ->get(route('intranet.announcements.index'))
        ->assertOk()
        ->assertSee('Communication désormais globale');
});
