<?php

use App\Enums\DepartmentRole;
use App\Models\AppSetting;
use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

it('reserves the system settings area for active system administrators', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.settings'))
        ->assertForbidden();

    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Paramètres de l’application')
        ->assertSee('Synchronisation Active Directory');
});

it('shows the latest AD synchronization failure details to administrators', function () {
    AppSetting::saveValues([
        'ldap_last_sync_attempt_at' => now()->toIso8601String(),
        'ldap_last_sync_error' => 'AD injoignable : timeout connexion.',
    ]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Détail de la dernière erreur')
        ->assertSee('AD injoignable : timeout connexion.')
        ->assertSee('Dernière tentative');
});

it('does not grant system administration based on department membership', function () {
    $department = Department::factory()->create(['name' => 'Administration']);
    $user = User::factory()->withRole($department, DepartmentRole::Director)->create();

    $this->actingAs($user)
        ->get(route('admin.settings'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('intranet.departments.manage.index'))
        ->assertForbidden();
});

it('saves site details and an AD synchronization schedule', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'site_name' => 'NÉRÉ Mining - ITSM',
            'support_email' => 'support@example.com',
            'ldap_sync_frequency' => 'weekly',
            'ldap_sync_time' => '03:30',
            'ldap_sync_day' => '2',
        ])
        ->assertRedirect(route('admin.settings'));

    expect(AppSetting::value('site_name'))->toBe('NÉRÉ Mining - ITSM')
        ->and(AppSetting::value('support_email'))->toBe('support@example.com')
        ->and(AppSetting::value('ldap_sync_frequency'))->toBe('weekly')
        ->and(AppSetting::value('ldap_sync_time'))->toBe('03:30')
        ->and(AppSetting::value('ldap_sync_day'))->toBe('2');
});

it('runs the scheduled AD sync only at the configured time', function () {
    AppSetting::saveValues([
        'ldap_sync_frequency' => 'daily',
        'ldap_sync_time' => '03:30',
        'ldap_sync_day' => '2',
    ]);
    config(['ldap_integration.enabled' => true]);

    $syncEvent = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'ldap:sync'));

    expect($syncEvent)->not->toBeNull();

    Carbon::setTestNow('2026-10-20 03:30:00');
    expect($syncEvent->filtersPass(app()))->toBeTrue();

    Carbon::setTestNow('2026-10-20 03:31:00');
    expect($syncEvent->filtersPass(app()))->toBeFalse();
    Carbon::setTestNow();
});

it('prevents the current administrator from removing their own access', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'matricule' => $admin->matricule,
        ])
        ->assertSessionHas('error');

    expect($admin->fresh()->is_super_admin)->toBeTrue()
        ->and($admin->fresh()->actif)->toBeTrue();
});

it('does not allow removing the last active system administrator', function () {
    $admin = User::factory()->superAdmin()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'matricule' => $admin->matricule,
            'actif' => '1',
        ])
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHas('error');

    expect($admin->fresh()->is_super_admin)->toBeTrue()
        ->and($admin->fresh()->actif)->toBeTrue()
        ->and($otherUser->fresh()->actif)->toBeTrue();
});
