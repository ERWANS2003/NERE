<?php

use App\Services\SystemHealth;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function failedJob(): string
{
    $uuid = (string) Str::uuid();
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Notifications\\TicketNotification', 'job' => 'x', 'data' => []]),
        'exception' => "Connection could not be established with host \"127.0.0.1:2525\"\n#0 trace",
        'failed_at' => now(),
    ]);

    return $uuid;
}

it('restricts the health page to system administrators', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.health'))->assertForbidden();
});

it('reports scheduler, mail, backup and failed jobs', function () {
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.from.address' => 'hello@example.com']);
    Cache::forever(SystemHealth::SCHEDULER_HEARTBEAT_KEY, now()->subMinute()->toIso8601String());
    failedJob();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.health'))
        ->assertOk()
        ->assertSee('Actives')
        ->assertSee('Non configuré')
        ->assertSee('Aucune sauvegarde enregistrée')
        ->assertSee('TicketNotification')
        ->assertSee('127.0.0.1:2525');
});

it('flags a stopped scheduler', function () {
    Cache::forever(SystemHealth::SCHEDULER_HEARTBEAT_KEY, now()->subHour()->toIso8601String());

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.health'))
        ->assertOk()
        ->assertSee('Arrêtées');
});

it('retries and forgets failed jobs', function () {
    $admin = User::factory()->superAdmin()->create();
    $retried = failedJob();
    $forgotten = failedJob();

    $this->actingAs($admin)->post(route('admin.health.retry', $retried))->assertRedirect();
    $this->actingAs($admin)->delete(route('admin.health.forget', $forgotten))->assertRedirect();

    expect(DB::table('failed_jobs')->count())->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(1);

    $this->actingAs($admin)->post(route('admin.health.retry', 'inconnu'))->assertNotFound();
});

it('alerts administrators once per new problem', function () {
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.from.address' => 'hello@example.com']);
    Cache::forever(SystemHealth::SCHEDULER_HEARTBEAT_KEY, now()->toIso8601String());
    $admin = User::factory()->superAdmin()->create();
    $employee = User::factory()->create();

    $this->artisan('intranet:alerte-sante')->assertSuccessful();
    $first = $admin->notifications()->first();
    expect($admin->notifications()->count())->toBe(1)
        ->and($first->data['problems'])->toContain('mail', 'backup')
        ->and($first->data['url'])->toBe(route('admin.health'))
        ->and($employee->notifications()->count())->toBe(0);

    $this->artisan('intranet:alerte-sante')->assertSuccessful();
    expect($admin->notifications()->count())->toBe(1);

    failedJob();
    $this->artisan('intranet:alerte-sante')->assertSuccessful();
    expect($admin->notifications()->count())->toBe(2)
        ->and($admin->notifications()->get()->pluck('data.problems')->all())->toContain(['queue']);
});
