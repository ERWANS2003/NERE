<?php

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

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

it('allows administrators to permanently delete an announcement and its notifications', function () {
    $admin = User::factory()->superAdmin()->create();
    $reader = User::factory()->create();
    $attributes = ['body' => 'Texte', 'priority' => 'info', 'created_by' => $admin->id, 'is_active' => true, 'published_at' => now()];
    $announcement = Announcement::create(['title' => 'Annonce a supprimer'] + $attributes);
    $other = Announcement::create(['title' => 'Annonce conservee'] + $attributes);
    $reader->notify(new AnnouncementPublished($announcement));
    $reader->notify(new AnnouncementPublished($other));

    $this->actingAs($admin)
        ->get(route('admin.announcements.index'))
        ->assertSee(route('admin.announcements.delete', $announcement));

    $this->delete(route('admin.announcements.delete', $announcement))
        ->assertRedirect(route('admin.announcements.index'));

    expect(Announcement::find($announcement->id))->toBeNull()
        ->and(Announcement::find($other->id))->not->toBeNull()
        ->and($reader->notifications()->pluck('data')->pluck('announcement_id')->all())->toBe([$other->id]);
});

it('attaches a file to an announcement and lets employees download it', function () {
    Storage::fake('local');
    Notification::fake();
    $admin = User::factory()->superAdmin()->create();
    $employee = User::factory()->create();
    $payload = ['title' => 'Note de service', 'body' => 'Voir le document joint.', 'priority' => 'info', 'is_active' => '1'];

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), $payload + [
            'attachment' => UploadedFile::fake()->create('note-service.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect(route('admin.announcements.index'));

    $announcement = Announcement::firstOrFail();
    Storage::disk('local')->assertExists($announcement->attachment_path);
    expect($announcement->attachment_name)->toBe('note-service.pdf');

    $this->actingAs($employee)
        ->get(route('intranet.announcements.show', $announcement))
        ->assertOk()
        ->assertSee('note-service.pdf');
    $this->actingAs($employee)
        ->get(route('intranet.announcements.attachment', $announcement))
        ->assertOk()
        ->assertDownload('note-service.pdf');

    $this->actingAs($admin)
        ->post(route('admin.announcements.store'), $payload + [
            'attachment' => UploadedFile::fake()->create('script.exe', 10),
        ])
        ->assertSessionHasErrors('attachment');

    $oldPath = $announcement->attachment_path;
    $this->actingAs($admin)
        ->put(route('admin.announcements.update', $announcement), $payload + ['remove_attachment' => '1'])
        ->assertRedirect(route('admin.announcements.index'));

    Storage::disk('local')->assertMissing($oldPath);
    expect($announcement->fresh()->attachment_path)->toBeNull();
    $this->actingAs($employee)
        ->get(route('intranet.announcements.attachment', $announcement))
        ->assertNotFound();
});

function chefDeDepartement(Department $department): User
{
    $chef = User::factory()->create();
    $chef->departments()->attach($department, ['role' => 'director']);

    return $chef->refresh();
}

function annoncePayload(?Department $department, string $title = 'Note du chef'): array
{
    return ['title' => $title, 'body' => 'Contenu de la note.', 'priority' => 'info', 'department_id' => $department?->id];
}

it('lets a department head publish to their department only that department sees', function () {
    Notification::fake();
    $department = Department::factory()->create();
    $chef = chefDeDepartement($department);
    $member = User::factory()->create();
    $member->departments()->attach($department, ['role' => 'user']);
    $outsider = User::factory()->create();

    $this->actingAs($chef)->get(route('intranet.announcements.index'))->assertOk()->assertSee(route('intranet.announcements.create'));
    $this->actingAs($chef)->get(route('intranet.announcements.create'))->assertOk()->assertSee($department->name);
    $this->actingAs($chef)->post(route('intranet.announcements.store'), annoncePayload($department))->assertRedirect();

    $announcement = Announcement::firstOrFail();
    expect($announcement->department_id)->toBe($department->id)
        ->and($announcement->created_by)->toBe($chef->id);

    Notification::assertSentTo($member, AnnouncementPublished::class);
    Notification::assertNotSentTo($outsider, AnnouncementPublished::class);
    Notification::assertNotSentTo($chef, AnnouncementPublished::class);

    $this->actingAs($member)->get(route('intranet.announcements.show', $announcement))->assertOk()->assertSee($department->name);
    $this->actingAs($member)->get(route('intranet.dashboard'))->assertSee('Note du chef');
    $this->actingAs($outsider)->get(route('intranet.announcements.show', $announcement))->assertForbidden();
    $this->actingAs($outsider)->get(route('intranet.announcements.index'))->assertOk()->assertDontSee('Note du chef');
    $this->actingAs($outsider)->get(route('intranet.dashboard'))->assertDontSee('Note du chef');
    $this->actingAs($outsider)->get(route('api.search.data'))->assertOk()->assertDontSee('Note du chef');
});

it('lets a department head choose a company-wide audience but no other department', function () {
    Notification::fake();
    $department = Department::factory()->create();
    $chef = chefDeDepartement($department);
    $outsider = User::factory()->create();

    $this->actingAs($chef)->post(route('intranet.announcements.store'), annoncePayload(Department::factory()->create()))
        ->assertSessionHasErrors('department_id');
    $this->actingAs($chef)->post(route('intranet.announcements.store'), ['title' => 'Sans public', 'body' => 'x', 'priority' => 'info'])
        ->assertSessionHasErrors('department_id');
    expect(Announcement::count())->toBe(0);

    $this->actingAs($chef)->post(route('intranet.announcements.store'), annoncePayload(null))->assertRedirect();

    expect(Announcement::firstOrFail()->department_id)->toBeNull();
    Notification::assertSentTo($outsider, AnnouncementPublished::class);
    $this->actingAs($outsider)->get(route('intranet.announcements.show', Announcement::firstOrFail()))->assertOk();
});

it('lets a department head manage only their own announcements', function () {
    Notification::fake();
    $department = Department::factory()->create();
    $chef = chefDeDepartement($department);
    $otherChef = chefDeDepartement($department);
    $this->actingAs($chef)->post(route('intranet.announcements.store'), annoncePayload($department));
    $announcement = Announcement::firstOrFail();

    $this->actingAs($otherChef)->get(route('intranet.announcements.edit', $announcement))->assertForbidden();
    $this->actingAs($otherChef)->delete(route('intranet.announcements.destroy', $announcement))->assertForbidden();

    $this->actingAs($chef)->put(route('intranet.announcements.update', $announcement), annoncePayload($department, 'Note corrigée'))->assertRedirect();
    expect($announcement->fresh()->title)->toBe('Note corrigée');

    $this->actingAs($chef)->delete(route('intranet.announcements.destroy', $announcement))->assertRedirect();
    expect($announcement->fresh()->is_active)->toBeFalse();
});

it('refuses announcement creation to employees and keeps the audience on admin edits', function () {
    Notification::fake();
    $department = Department::factory()->create();
    $employee = User::factory()->create();
    $employee->departments()->attach($department, ['role' => 'technician', 'tech_level' => 3]);

    $this->actingAs($employee->refresh())->get(route('intranet.announcements.create'))->assertForbidden();
    $this->actingAs($employee)->post(route('intranet.announcements.store'), annoncePayload($department))->assertForbidden();

    $chef = chefDeDepartement($department);
    $this->actingAs($chef)->post(route('intranet.announcements.store'), annoncePayload($department));
    $announcement = Announcement::firstOrFail();

    $this->actingAs(User::factory()->superAdmin()->create())->put(route('admin.announcements.update', $announcement), [
        'title' => 'Relue',
        'body' => 'Contenu.',
        'priority' => 'info',
        'is_active' => '1',
    ])->assertRedirect(route('admin.announcements.index'));

    expect($announcement->fresh()->department_id)->toBe($department->id);
});
