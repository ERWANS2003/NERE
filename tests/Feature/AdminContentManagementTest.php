<?php

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('restricts announcement and calendar management to system administrators', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.announcements.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.calendar.index'))
        ->assertForbidden();
});

it('allows administrators to publish global announcements, edit and archive them', function () {
    Notification::fake();
    $admin = User::factory()->superAdmin()->create();
    $recipient = User::factory()->create();
    $globalRecipient = User::factory()->create();
    $department = Department::factory()->create();
    $recipient->departments()->attach($department, ['role' => 'user']);

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Maintenance réseau',
            'body' => 'Interruption prévue à 18 h.',
            'priority' => 'urgent',
            'department_id' => $department->id,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.announcements.index'));

    $announcement = Announcement::firstOrFail();
    expect($announcement->is_active)->toBeTrue()
        ->and($announcement->published_at)->not->toBeNull()
        ->and($announcement->department_id)->toBeNull();
    Notification::assertSentTo($recipient, AnnouncementPublished::class);
    Notification::assertSentTo($globalRecipient, AnnouncementPublished::class);

    $this->get(route('admin.announcements.index'))
        ->assertOk()
        ->assertSee('Maintenance réseau');
    $this->get(route('admin.announcements.create'))
        ->assertOk()
        ->assertSee('Publier maintenant');

    $this->put(route('admin.announcements.update', $announcement), [
        'title' => 'Maintenance réseau reportée',
        'body' => 'Nouvel horaire : 19 h.',
        'priority' => 'important',
        'department_id' => '',
        'is_active' => '1',
    ])->assertRedirect(route('admin.announcements.index'));

    expect($announcement->fresh()->title)->toBe('Maintenance réseau reportée')
        ->and($announcement->fresh()->department_id)->toBeNull();

    $this->delete(route('admin.announcements.destroy', $announcement))
        ->assertRedirect(route('admin.announcements.index'));

    expect($announcement->fresh()->is_active)->toBeFalse();
});

it('allows administrators to create, update and delete calendar events', function () {
    $admin = User::factory()->superAdmin()->create();
    $department = Department::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.calendar.store'), [
            'title' => 'Réunion sécurité',
            'description' => 'Réunion mensuelle.',
            'type' => 'meeting',
            'event_date' => '2026-11-02',
            'event_time' => '09:30',
            'department_id' => $department->id,
        ])
        ->assertRedirect(route('admin.calendar.index'));

    $event = CalendarEvent::firstOrFail();
    expect($event->title)->toBe('Réunion sécurité')
        ->and($event->all_day)->toBeFalse()
        ->and($event->department_id)->toBeNull()
        ->and($event->event_time)->toStartWith('09:30');

    $this->get(route('admin.calendar.index'))
        ->assertOk()
        ->assertSee('Réunion sécurité');
    $this->get(route('admin.calendar.create'))
        ->assertOk()
        ->assertSee('Toute la journée')
        ->assertDontSee('Département concerné');

    $this->put(route('admin.calendar.update', $event), [
        'title' => 'Réunion sécurité déplacée',
        'description' => '',
        'type' => 'training',
        'event_date' => '2026-11-03',
        'all_day' => '1',
        'department_id' => '',
    ])->assertRedirect(route('admin.calendar.index'));

    expect($event->fresh()->title)->toBe('Réunion sécurité déplacée')
        ->and($event->fresh()->all_day)->toBeTrue()
        ->and($event->fresh()->event_time)->toBeNull();

    $this->delete(route('admin.calendar.destroy', $event))
        ->assertRedirect(route('admin.calendar.index'));

    expect(CalendarEvent::find($event->id))->toBeNull();
});

it('rejects invalid announcement priorities and calendar event types', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), [
            'title' => 'Annonce',
            'body' => 'Contenu',
            'priority' => 'unknown',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('priority');

    $this->post(route('admin.calendar.store'), [
        'title' => 'Événement',
        'type' => 'birthday',
        'event_date' => '2026-11-02',
        'all_day' => '1',
    ])->assertSessionHasErrors('type');
});
