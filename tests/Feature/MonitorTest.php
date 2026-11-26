<?php

use App\Enums\DepartmentRole;
use App\Enums\MonitorStatus;
use App\Models\Department;
use App\Models\MonitorDevice;
use App\Models\MonitorDiscovery;
use App\Models\MonitorEvent;
use App\Models\MonitorGroup;
use App\Models\MonitorMap;
use App\Models\MonitorReading;
use App\Models\MonitorSensor;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\MonitorAlert;
use App\Services\Monitor\MonitorHistory;
use App\Services\Monitor\MonitorPoller;
use App\Services\Monitor\NetworkScanner;
use App\Services\Monitor\SnmpClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

function monitorIt(): Department
{
    return Department::query()->firstWhere('code', 'IT') ?? Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);
}

function monitorTechnician(): User
{
    return User::factory()->withRole(monitorIt(), DepartmentRole::Technician, 1)->create();
}

function monitorDevice(array $attributes = [], bool $template = true): MonitorDevice
{
    $group = MonitorGroup::query()->first() ?? MonitorGroup::create(['name' => 'Siège']);
    $device = MonitorDevice::create($attributes + ['group_id' => $group->id, 'name' => 'Serveur fichiers', 'host' => '10.0.0.5', 'type' => 'server']);
    if ($template) {
        $device->applyTemplate(false);
    }

    return $device->load('sensors.device', 'group');
}

function pingReply(int $ms): string
{
    return "Réponse de 10.0.0.5 : octets=32 temps={$ms} ms TTL=64\r\n";
}

function pingFails(): void
{
    Process::fake(['*' => Process::result("Délai d'attente de la demande dépassé.\r\n")]);
}

/** Client SNMP de test : renvoie des valeurs fixes au lieu d'interroger le réseau. */
class FakeSnmp extends SnmpClient
{
    public function __construct(public array $values = []) {}

    protected function request(string $host, string $community, int $pduType, array $oids): array
    {
        $oid = ltrim($oids[0], '.');
        if ($pduType === 0xA0) {
            return collect($oids)->mapWithKeys(fn ($o) => [ltrim($o, '.') => $this->values[ltrim($o, '.')] ?? null])->all();
        }
        $keys = array_keys($this->values);
        usort($keys, 'version_compare');
        foreach ($keys as $key) {
            if (version_compare($key, $oid, '>')) {
                return [$key => $this->values[$key]];
            }
        }

        return [];
    }

    public function decode(string $hex, int $id): array
    {
        return $this->parseResponse(hex2bin($hex), $id);
    }
}

it('opens supervision to the IT team and administrators only', function () {
    $technician = monitorTechnician();

    $this->actingAs(User::factory()->create())->get(route('intranet.monitor.index'))->assertForbidden();
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('intranet.monitor.index'))->assertOk();
    $this->actingAs($technician)->get(route('intranet.monitor.index'))
        ->assertOk()
        ->assertSee('Créer le premier groupe')
        ->assertSee('Découverte automatique')
        ->assertDontSee('<span>Supervision réseau</span>', false);

    config(['monitor.show_in_menu' => true]);
    $this->get(route('intranet.monitor.index'))->assertSee('<span>Supervision réseau</span>', false);
});

it('inherits settings from parent groups and applies device templates', function () {
    $this->actingAs(monitorTechnician());
    $this->post(route('intranet.monitor.groups.store'), ['name' => 'Mine', 'notify_delay_minutes' => 5, 'snmp_community' => 'nere-ro'])->assertRedirect();
    $mine = MonitorGroup::firstOrFail();
    $this->post(route('intranet.monitor.groups.store'), ['name' => 'Zone du bois', 'parent_id' => $mine->id, 'interval_seconds' => 120])->assertRedirect();
    $zone = MonitorGroup::where('name', 'Zone du bois')->firstOrFail();

    $this->put(route('intranet.monitor.groups.update', $mine), ['name' => 'Mine', 'parent_id' => $zone->id])
        ->assertSessionHasErrors('parent_id');

    $this->post(route('intranet.monitor.devices.store'), ['name' => 'Caméra portail', 'host' => '10.0.0.9', 'type' => 'camera', 'group_id' => $zone->id, 'snmp' => '1'])->assertRedirect();
    $camera = MonitorDevice::with('sensors', 'group')->firstOrFail();

    expect($zone->path())->toBe('Mine > Zone du bois')
        ->and($camera->setting('interval_seconds'))->toBe(120)
        ->and($camera->setting('notify_delay_minutes'))->toBe(5)
        ->and($camera->setting('snmp_community'))->toBe('nere-ro')
        ->and($camera->setting('notify_repeat_minutes'))->toBe(0)
        ->and($camera->sensors->pluck('name')->all())->toBe(['Ping', 'Flux vidéo (RTSP)', 'Interface web', 'Durée de fonctionnement'])
        ->and(DB::table('monitor_groups')->where('id', $mine->id)->value('snmp_community'))->not->toBe('nere-ro');

    $this->get(route('intranet.monitor.index'))->assertSee('Zone du bois')->assertSee('Caméra portail')->assertDontSee('nere-ro');
    $this->post(route('intranet.monitor.devices.store'), ['name' => 'Plage', 'host' => '10.0.0.0/24', 'type' => 'server', 'group_id' => $zone->id])->assertSessionHasErrors('host');
});

it('warns on the first failure, goes down on the second, then notifies after the delay, repeats and escalates', function () {
    Notification::fake();
    $technician = monitorTechnician();
    $director = User::factory()->withRole(monitorIt(), DepartmentRole::Director)->create();
    $device = monitorDevice();
    $device->group->update(['notify_delay_minutes' => 5, 'notify_repeat_minutes' => 30, 'escalate_after_minutes' => 60]);
    $ping = $device->sensors->first();
    $poller = app(MonitorPoller::class);

    pingFails();
    $poller->poll();
    expect($ping->fresh()->status)->toBe(MonitorStatus::Warning)
        ->and($ping->fresh()->last_message)->toContain('échec 1/2');

    $poller->poll(); // nouvel essai immédiat
    expect($ping->fresh()->status)->toBe(MonitorStatus::Down);
    Notification::assertNothingSent();

    $this->travel(6)->minutes();
    $poller->poll();
    Notification::assertSentTo($technician, MonitorAlert::class, fn ($n) => $n->kind === 'down');

    $this->travel(31)->minutes();
    $poller->poll();
    Notification::assertSentTo($technician, MonitorAlert::class, fn ($n) => $n->kind === 'still_down');

    $this->travel(30)->minutes();
    $poller->poll();
    Notification::assertSentTo($director, MonitorAlert::class, fn ($n) => $n->kind === 'escalated');
    Notification::assertNotSentTo($technician, MonitorAlert::class, fn ($n) => $n->kind === 'escalated');

    Process::fake(['*' => Process::result(pingReply(12))]);
    $this->travel(2)->minutes();
    $poller->poll();
    expect($ping->fresh()->status)->toBe(MonitorStatus::Up)->and($ping->fresh()->last_value)->toBe(12.0);
    Notification::assertSentTo($technician, MonitorAlert::class, fn ($n) => $n->kind === 'recovered');
});

it('stops reminders once acknowledged and returns to down when the acknowledgement expires', function () {
    Notification::fake();
    $technician = monitorTechnician();
    $device = monitorDevice();
    $device->group->update(['notify_repeat_minutes' => 10]);
    $ping = $device->sensors->first();
    $poller = app(MonitorPoller::class);
    pingFails();
    $poller->poll();
    $poller->poll();
    Notification::assertSentToTimes($technician, MonitorAlert::class, 1);

    $this->actingAs($technician)->post(route('intranet.monitor.sensors.acknowledge', $ping), ['acknowledgement' => 'Coupure électrique', 'duration' => '60'])->assertRedirect();
    expect($ping->fresh()->status)->toBe(MonitorStatus::DownAcknowledged);
    $this->get(route('intranet.monitor.index'))->assertSee('Coupure électrique')->assertSee('En panne (pris en compte)');

    $this->travel(30)->minutes();
    $poller->poll();
    expect($ping->fresh()->status)->toBe(MonitorStatus::DownAcknowledged);
    Notification::assertSentToTimes($technician, MonitorAlert::class, 1);

    $this->travel(31)->minutes();
    $poller->poll();
    expect($ping->fresh()->status)->toBe(MonitorStatus::Down);
    Notification::assertSentToTimes($technician, MonitorAlert::class, 2);
});

it('pauses dependent sensors and devices instead of raising more alarms', function () {
    $antenna = monitorDevice(['name' => 'Antenne', 'host' => '10.0.0.2', 'type' => 'antenna']);
    $camera = monitorDevice(['name' => 'Caméra', 'host' => '10.0.0.3', 'type' => 'camera', 'parent_device_id' => $antenna->id]);
    $poller = app(MonitorPoller::class);
    Http::fake(['*' => Http::response('', 200)]);

    Process::fake([
        '*10.0.0.2*' => Process::result("Délai d'attente dépassé.\r\n"),
        '*' => Process::result(pingReply(3)),
    ]);
    $poller->poll();
    $poller->poll();

    $antennaPing = $antenna->sensors()->where('kind', 'ping')->first();
    $antennaWeb = $antenna->sensors()->where('kind', 'http')->first();
    expect($antennaPing->status)->toBe(MonitorStatus::Down);

    $this->travel(2)->minutes();
    $poller->poll();
    expect($antennaWeb->fresh()->status)->toBe(MonitorStatus::Paused)
        ->and($antennaWeb->fresh()->last_message)->toContain('ping de l’équipement');
    foreach ($camera->sensors()->get() as $sensor) {
        expect($sensor->status)->toBe(MonitorStatus::Paused)->and($sensor->last_message)->toBe('En pause : dépend de Antenne, injoignable');
    }
});

it('applies limits and honours timed pauses', function () {
    $device = monitorDevice();
    $ping = $device->sensors->first();
    $ping->update(['warn_above' => 100, 'error_above' => 500]);

    Process::fake(['*' => Process::result(pingReply(250))]);
    app(MonitorPoller::class)->checkNow($ping->fresh());
    expect($ping->fresh()->status)->toBe(MonitorStatus::Warning)->and($ping->fresh()->last_message)->toContain('limite d’avertissement');

    Process::fake(['*' => Process::result(pingReply(800))]);
    app(MonitorPoller::class)->checkNow($ping->fresh());
    expect($ping->fresh()->status)->toBe(MonitorStatus::Down)->and($ping->fresh()->failures)->toBe(0);

    $this->actingAs(monitorTechnician())->post(route('intranet.monitor.devices.pause', $device), ['duration' => '15', 'reason' => 'Maintenance'])->assertRedirect();
    expect($device->fresh()->isPaused())->toBeTrue()
        ->and(app(MonitorPoller::class)->poll())->toBe(0);

    $this->travel(16)->minutes();
    Process::fake(['*' => Process::result(pingReply(20))]);
    expect(app(MonitorPoller::class)->poll())->toBe(1)
        ->and($device->fresh()->paused)->toBeFalse()
        ->and($ping->fresh()->status)->toBe(MonitorStatus::Up);
});

it('decodes SNMP answers and reads uptime, CPU, disk, printer supplies and traffic', function () {
    // Réponse SNMP v2c : sysUpTime = 360000 centièmes de seconde.
    expect((new FakeSnmp)->decode(str_replace(' ', '', '302a 0201 01 0406 7075626c6963 a21d 020204d2 020100 020100 3011 300f 06082b06010201010300 4303057e40'), 1234))
        ->toBe(['1.3.6.1.2.1.1.3.0' => 360000]);

    $snmp = new FakeSnmp([
        '1.3.6.1.2.1.1.3.0' => 864000000,
        '1.3.6.1.2.1.25.3.3.1.2.1' => 40, '1.3.6.1.2.1.25.3.3.1.2.2' => 60,
        '1.3.6.1.2.1.25.2.3.1.2.1' => '1.3.6.1.2.1.25.2.1.4', '1.3.6.1.2.1.25.2.3.1.3.1' => 'C:\\ Label:System', '1.3.6.1.2.1.25.2.3.1.4.1' => 4096, '1.3.6.1.2.1.25.2.3.1.5.1' => 1000, '1.3.6.1.2.1.25.2.3.1.6.1' => 900,
        '1.3.6.1.2.1.43.11.1.1.6.1.1' => 'Toner noir', '1.3.6.1.2.1.43.11.1.1.8.1.1' => 100, '1.3.6.1.2.1.43.11.1.1.9.1.1' => 4,
        '1.3.6.1.2.1.31.1.1.1.6.3' => 1_000_000, '1.3.6.1.2.1.31.1.1.1.10.3' => 500_000,
    ]);
    app()->instance(SnmpClient::class, $snmp);
    $device = monitorDevice(template: false);
    $sensors = collect(['snmp_uptime', 'snmp_cpu', 'snmp_disk', 'snmp_printer', 'snmp_traffic'])
        ->mapWithKeys(fn ($kind) => [$kind => $device->sensors()->create(['kind' => $kind, 'name' => $kind] + \App\Enums\MonitorSensorKind::from($kind)->defaultLimits())]);
    $poller = app(MonitorPoller::class);
    foreach ($sensors as $sensor) {
        $poller->checkNow($sensor);
    }

    expect($sensors['snmp_uptime']->fresh()->last_value)->toBe(100.0)
        ->and($sensors['snmp_cpu']->fresh()->last_value)->toBe(50.0)
        ->and($sensors['snmp_disk']->fresh()->status)->toBe(MonitorStatus::Warning)
        ->and($sensors['snmp_disk']->fresh()->last_message)->toContain('90 %')
        ->and($sensors['snmp_printer']->fresh()->status)->toBe(MonitorStatus::Down)
        ->and($sensors['snmp_printer']->fresh()->last_message)->toContain('limite de panne')
        ->and($sensors['snmp_traffic']->fresh()->state['index'])->toBe(3);

    $snmp->values['1.3.6.1.2.1.31.1.1.1.6.3'] = 1_000_000 + 125_000 * 60; // 1 Mbit/s entrant sur 60 s
    $snmp->values['1.3.6.1.2.1.31.1.1.1.10.3'] = 500_000;
    $traffic = $sensors['snmp_traffic']->fresh();
    $traffic->forceFill(['state' => ['at' => microtime(true) - 60] + $traffic->state])->save();
    $poller->checkNow($traffic->fresh());
    expect(round($traffic->fresh()->last_value))->toBe(1000.0);
});

it('rolls readings up by hour and reports availability, outages and downtime', function () {
    $this->travelTo(now()->setTime(10, 30));
    $device = monitorDevice(['name' => 'Imprimante RH', 'type' => 'printer'], template: false);
    $ping = $device->sensors()->create(['kind' => 'ping', 'name' => 'Ping']);
    foreach ([[now()->subHours(2)->setMinute(5), true, 10], [now()->subHours(2)->setMinute(10), true, 30], [now()->subHours(2)->setMinute(20), false, null], [now()->subDays(8), true, 5]] as [$at, $ok, $value]) {
        MonitorReading::create(['sensor_id' => $ping->id, 'checked_at' => $at, 'ok' => $ok, 'value' => $value]);
    }
    MonitorEvent::forceCreate(['sensor_id' => $ping->id, 'from_status' => 'up', 'to_status' => 'down', 'created_at' => now()->subHours(3)]);
    MonitorEvent::forceCreate(['sensor_id' => $ping->id, 'from_status' => 'down', 'to_status' => 'up', 'created_at' => now()->subHours(2)]);

    app(MonitorHistory::class)->rollup();
    expect((int) DB::table('monitor_hourly')->where('sensor_id', $ping->id)->value('samples'))->toBe(3)
        ->and(MonitorReading::count())->toBe(3);

    [$row] = app(MonitorHistory::class)->report(collect([$ping->load('device')]), now()->subDay(), now());
    expect($row['uptime'])->toBe(66.67)->and($row['outages'])->toBe(1)->and($row['downtime_minutes'])->toBe(60);

    $this->actingAs(monitorTechnician());
    $this->get(route('intranet.monitor.sensors.show', [$ping, 'periode' => '2j']))->assertOk()->assertSee('66,67 %')->assertSee('<polyline', false);
    $this->get(route('intranet.monitor.reports', ['periode' => '30j']))->assertOk()->assertSee('Imprimante RH')->assertSee('1 h 0 min');
    $csv = $this->get(route('intranet.monitor.reports', ['periode' => '30j', 'format' => 'csv']))->assertOk()->streamedContent();
    expect($csv)->toContain('Imprimante RH')->toContain('66.67');
    $this->get(route('intranet.monitor.reports', ['format' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('intranet.monitor.logs'))->assertOk()->assertSee('Imprimante RH');
});

it('classifies discovered hosts, sets staff computers aside and imports the chosen ones', function () {
    expect(NetworkScanner::classify([80, 554], ''))->toBe(['camera', false])
        ->and(NetworkScanner::classify([9100], ''))->toBe(['printer', false])
        ->and(NetworkScanner::classify([80], 'Linux airOS ubiquiti'))->toBe(['antenna', false])
        ->and(NetworkScanner::classify([135, 445, 3389], ''))->toBe([null, true])
        ->and(NetworkScanner::classify([135, 445, 3389], 'Hardware: Intel64 - Software: Windows Server 2016'))->toBe(['server', false])
        ->and(count(NetworkScanner::hosts('192.168.10.0/24')))->toBe(254);
    expect(fn () => NetworkScanner::hosts('8.8.8.0/24'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => NetworkScanner::hosts('10.0.0.0/16'))->toThrow(InvalidArgumentException::class);

    $technician = monitorTechnician();
    $group = MonitorGroup::create(['name' => 'Mine']);
    $this->actingAs($technician)->post(route('intranet.monitor.discovery.store'), ['cidr' => '10.0.0.0/16', 'group_id' => $group->id])->assertSessionHasErrors('cidr');

    $discovery = MonitorDiscovery::create(['group_id' => $group->id, 'cidr' => '10.1.0.0/24', 'status' => 'done', 'results' => [
        ['ip' => '10.1.0.5', 'name' => 'cam-portail.nere.local', 'ports' => [554], 'description' => '', 'type' => 'camera', 'workstation' => false, 'monitored' => false],
        ['ip' => '10.1.0.40', 'name' => 'PC-COMPTA', 'ports' => [135, 445], 'description' => '', 'type' => null, 'workstation' => true, 'monitored' => false],
    ]]);
    $this->get(route('intranet.monitor.discovery.show', $discovery))->assertOk()->assertSee('Probablement un ordinateur du personnel');
    $this->post(route('intranet.monitor.discovery.import', $discovery), ['hosts' => ['10.1.0.5'], 'types' => ['10.1.0.5' => 'camera']])->assertRedirect();

    $camera = MonitorDevice::with('sensors')->firstOrFail();
    expect($camera->name)->toBe('cam-portail')->and($camera->sensors)->toHaveCount(3)->and(MonitorDevice::count())->toBe(1);
});

it('places devices on a map and opens a ticket from an alarm', function () {
    $technician = monitorTechnician();
    $device = monitorDevice();
    $this->actingAs($technician)->post(route('intranet.monitor.maps.store'), ['name' => 'Plan du siège'])->assertRedirect();
    $map = MonitorMap::firstOrFail();
    $this->put(route('intranet.monitor.maps.update', $map), ['name' => 'Plan du siège', 'add_device_id' => $device->id])->assertRedirect();
    $this->put(route('intranet.monitor.maps.update', $map), ['name' => 'Plan du siège', 'items' => [$device->id => ['x' => 20, 'y' => 75]]])->assertRedirect();
    expect($map->items()->first()->only('x', 'y'))->toBe(['x' => 20.0, 'y' => 75.0]);
    $this->get(route('intranet.monitor.maps.show', $map))->assertOk()->assertSee('Serveur fichiers')->assertSee('left: 20%', false);

    $ping = $device->sensors->first();
    pingFails();
    app(MonitorPoller::class)->poll();
    app(MonitorPoller::class)->poll();
    $this->post(route('intranet.monitor.sensors.ticket', $ping))->assertRedirect();
    $ticket = Ticket::firstOrFail();
    expect($ticket->subject)->toContain('Serveur fichiers')
        ->and($ticket->priority->value)->toBe('high')
        ->and($ping->fresh()->ticket_id)->toBe($ticket->id);
    $this->post(route('intranet.monitor.sensors.ticket', $ping))->assertRedirect(route('intranet.tickets.show', $ticket));
    expect(Ticket::count())->toBe(1);
});

it('helps with search, colour legend, quick add and a one-click acknowledgement', function () {
    $technician = monitorTechnician();
    $device = monitorDevice();
    pingFails();
    app(MonitorPoller::class)->poll();
    app(MonitorPoller::class)->poll();

    $this->actingAs($technician)->get(route('intranet.monitor.index'))
        ->assertOk()
        ->assertSee('Rechercher un équipement')
        ->assertSee('Que signifient les couleurs ?')
        ->assertSee('1 en panne')
        ->assertSee('<option value="0" selected>Jusqu’à reprise manuelle</option>', false);

    $this->get(route('intranet.monitor.devices.show', $device))->assertOk()->assertSee('+ Bureau à distance (3389)')->assertDontSee('Retirer l’équipement');
    $this->get(route('intranet.monitor.devices.edit', $device))->assertOk()->assertSee('Retirer l’équipement');

    $this->from(route('intranet.monitor.devices.show', $device))
        ->post(route('intranet.monitor.sensors.store', $device), ['quick' => 1, 'kind' => 'tcp', 'name' => 'Bureau à distance', 'port' => 3389])
        ->assertRedirect(route('intranet.monitor.devices.show', $device));
    $rdp = $device->sensors()->where('port', 3389)->firstOrFail();
    expect($rdp->checked_at)->not->toBeNull();
    $this->get(route('intranet.monitor.devices.show', $device))->assertDontSee('+ Bureau à distance (3389)');
});
