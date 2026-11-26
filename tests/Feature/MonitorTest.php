<?php

use App\Enums\DepartmentRole;
use App\Enums\MonitorStatus;
use App\Models\Department;
use App\Models\MonitorDevice;
use App\Models\MonitorReading;
use App\Models\MonitorSensor;
use App\Models\User;
use App\Notifications\MonitorAlert;
use App\Services\Monitor\MonitorHistory;
use App\Services\Monitor\MonitorPoller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

function monitorTechnician(): User
{
    $it = Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);

    return User::factory()->withRole($it, DepartmentRole::Technician, 1)->create();
}

function pingReply(int $ms): string
{
    return "Envoi d'une requête 'Ping'  10.0.0.5 avec 32 octets de données :\r\nRéponse de 10.0.0.5 : octets=32 temps={$ms} ms TTL=64\r\n";
}

it('opens supervision to the IT team and administrators only', function () {
    $technician = monitorTechnician();

    $this->actingAs(User::factory()->create())->get(route('intranet.monitor.index'))->assertForbidden();
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('intranet.monitor.index'))->assertOk();
    $this->actingAs($technician)->get(route('intranet.monitor.index'))
        ->assertOk()
        ->assertSee('Supervision réseau')
        ->assertSee('Aucune alerte');
});

it('creates the default sensors for each type of device', function () {
    $this->actingAs(monitorTechnician())->post(route('intranet.monitor.devices.store'), [
        'name' => 'Caméra portail', 'host' => '10.0.0.5', 'type' => 'camera', 'site' => 'Mine',
    ])->assertRedirect();

    $camera = MonitorDevice::with('sensors.device')->firstOrFail();
    expect($camera->sensors->pluck('name')->all())->toBe(['Ping', 'Flux vidéo (RTSP)', 'Interface web'])
        ->and($camera->sensors[1]->port)->toBe(554)
        ->and($camera->sensors[2]->url())->toBe('http://10.0.0.5/');

    $this->post(route('intranet.monitor.devices.store'), [
        'name' => 'Plage', 'host' => '10.0.0.0/24', 'type' => 'server', 'site' => 'Mine',
    ])->assertSessionHasErrors('host');
    $this->post(route('intranet.monitor.devices.store'), [
        'name' => 'Doublon', 'host' => '10.0.0.5', 'type' => 'server', 'site' => 'Mine',
    ])->assertSessionHasErrors(['host' => 'Cet équipement est déjà supervisé.']);
});

it('goes down after two failures, alerts the IT team, then reports the recovery', function () {
    Notification::fake();
    $technician = monitorTechnician();
    $device = MonitorDevice::create(['name' => 'Serveur fichiers', 'host' => '10.0.0.5', 'type' => 'server', 'site' => 'Siège']);
    $ping = $device->sensors()->firstOrFail();
    $poller = app(MonitorPoller::class);

    Process::fake(['*' => Process::result("Délai d'attente de la demande dépassé.\r\n")]);
    $poller->poll();
    expect($ping->fresh()->status)->toBe(MonitorStatus::Warning)
        ->and($ping->fresh()->last_message)->toBe('Aucune réponse au ping (échec 1/2)');
    Notification::assertNothingSent();

    $this->travel(1)->minutes();
    $poller->poll();
    expect($ping->fresh()->status)->toBe(MonitorStatus::Down);
    Notification::assertSentTo($technician, MonitorAlert::class, fn ($alert) => str_starts_with($alert->toArray($technician)['title'], 'En panne : Serveur fichiers'));

    $this->actingAs($technician)->post(route('intranet.monitor.sensors.acknowledge', $ping), ['acknowledgement' => 'Coupure électrique'])->assertRedirect();
    expect($ping->fresh()->acknowledged_by)->toBe($technician->id);
    $this->get(route('intranet.monitor.index'))->assertSee('Coupure électrique')->assertSee('Serveur fichiers · Ping');

    $this->travel(1)->minutes();
    Process::fake(['*' => Process::result(pingReply(12))]);
    $poller->poll();
    $ping->refresh();
    expect($ping->status)->toBe(MonitorStatus::Up)
        ->and($ping->last_ms)->toBe(12)
        ->and($ping->acknowledged_by)->toBeNull()
        ->and($ping->events()->pluck('to_status')->map->value->all())->toBe(['warning', 'down', 'up']);
    Notification::assertSentToTimes($technician, MonitorAlert::class, 2);

    // Déjà vérifié il y a moins d'une minute : pas de nouveau contrôle.
    expect($poller->poll())->toBe(0);
});

it('treats a Windows unreachable reply as a failure and warns when too slow', function () {
    $device = MonitorDevice::create(['name' => 'Antenne', 'host' => '10.0.0.9', 'type' => 'server', 'site' => 'Mine']);
    $ping = $device->sensors()->firstOrFail();
    $ping->update(['warning_ms' => 100]);

    Process::fake(['*' => Process::result("Réponse de 10.0.0.1 : Impossible de joindre l'hôte de destination.\r\n")]);
    app(MonitorPoller::class)->checkNow($ping->fresh());
    expect($ping->fresh()->status)->toBe(MonitorStatus::Warning)->and($ping->fresh()->failures)->toBe(1);

    Process::fake(['*' => Process::result(pingReply(250))]);
    app(MonitorPoller::class)->checkNow($ping->fresh());
    expect($ping->fresh()->status)->toBe(MonitorStatus::Warning)
        ->and($ping->fresh()->last_message)->toBe('Lent : 250 ms (seuil 100 ms)');
});

it('checks TCP ports and web pages', function () {
    $server = stream_socket_server('tcp://127.0.0.1:0');
    $openPort = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
    $device = MonitorDevice::create(['name' => 'Local', 'host' => '127.0.0.1', 'type' => 'server', 'site' => 'Test']);
    $device->sensors()->where('kind', 'ping')->update(['paused' => true]);
    $open = $device->sensors()->create(['kind' => 'tcp', 'name' => 'Ouvert', 'port' => $openPort]);
    $web = $device->sensors()->create(['kind' => 'http', 'name' => 'Web', 'port' => 8080, 'path' => '/etat']);
    $broken = $device->sensors()->create(['kind' => 'http', 'name' => 'Cassé', 'port' => 80, 'path' => '/']);
    Http::fake([
        'http://127.0.0.1:8080/etat' => Http::response('', 401),
        'http://127.0.0.1/' => Http::response('', 503),
    ]);

    app(MonitorPoller::class)->poll();
    fclose($server);

    expect($open->fresh()->status)->toBe(MonitorStatus::Up)
        ->and($web->fresh()->status)->toBe(MonitorStatus::Up)
        ->and($web->fresh()->last_message)->toStartWith('HTTP 401')
        ->and($broken->fresh()->last_message)->toBe('Erreur HTTP 503 (échec 1/2)');
});

it('skips paused devices and sensors', function () {
    Process::fake();
    $device = MonitorDevice::create(['name' => 'En travaux', 'host' => '10.0.0.7', 'type' => 'printer', 'site' => 'Siège', 'paused' => true]);

    expect(app(MonitorPoller::class)->poll())->toBe(0)
        ->and($device->load('sensors')->status())->toBe(MonitorStatus::Paused);
    Process::assertNothingRan();
});

it('rolls readings up by hour, purges old ones and draws the history', function () {
    $this->travelTo(now()->setTime(10, 30));
    $device = MonitorDevice::create(['name' => 'Imprimante RH', 'host' => '10.0.0.8', 'type' => 'printer', 'site' => 'Siège']);
    $ping = $device->sensors()->firstOrFail();
    foreach ([[now()->subHours(2)->setMinute(5), true, 10], [now()->subHours(2)->setMinute(10), true, 30], [now()->subHours(2)->setMinute(20), false, null], [now()->subDays(8), true, 5]] as [$at, $ok, $ms]) {
        MonitorReading::create(['sensor_id' => $ping->id, 'checked_at' => $at, 'ok' => $ok, 'ms' => $ms]);
    }

    app(MonitorHistory::class)->rollup();

    $hour = DB::table('monitor_hourly')->where('sensor_id', $ping->id)->first();
    expect((int) $hour->samples)->toBe(3)
        ->and((int) $hour->failures)->toBe(1)
        ->and((int) $hour->avg_ms)->toBe(20)
        ->and(MonitorReading::count())->toBe(3);

    $series = app(MonitorHistory::class)->series($ping, '7j');
    expect($series['uptime'])->toBe(66.67)->and($series['avg_ms'])->toBe(20);

    $this->actingAs(monitorTechnician())->get(route('intranet.monitor.sensors.show', [$ping, 'periode' => '7j']))
        ->assertOk()
        ->assertSee('66,67 %')
        ->assertSee('<polyline', false);
    $this->get(route('intranet.monitor.devices.show', $device))->assertOk()->assertSee("Port d'impression");
});
