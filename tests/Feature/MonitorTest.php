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

    protected function request(string $host, string|array $community, int $pduType, array $oids): array
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
    Notification::assertSentTo($technician, MonitorAlert::class, fn ($n) => in_array('down', $n->kinds(), true));

    $this->travel(31)->minutes();
    $poller->poll();
    Notification::assertSentTo($technician, MonitorAlert::class, fn ($n) => in_array('still_down', $n->kinds(), true));

    $this->travel(30)->minutes();
    $poller->poll();
    Notification::assertSentTo($director, MonitorAlert::class, fn ($n) => in_array('escalated', $n->kinds(), true));
    Notification::assertNotSentTo($technician, MonitorAlert::class, fn ($n) => in_array('escalated', $n->kinds(), true));

    Process::fake(['*' => Process::result(pingReply(12))]);
    $this->travel(2)->minutes();
    $poller->poll();
    expect($ping->fresh()->status)->toBe(MonitorStatus::Up)->and($ping->fresh()->last_value)->toBe(12.0);
    Notification::assertSentTo($technician, MonitorAlert::class, fn ($n) => in_array('recovered', $n->kinds(), true));
});

it('stops reminders once acknowledged and returns to down when the acknowledgement expires', function () {
    Notification::fake();
    $technician = monitorTechnician();
    $device = monitorDevice();
    $device->group->update(['notify_repeat_minutes' => 10, 'notify_delay_minutes' => 0]);
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
        ->and(NetworkScanner::classify([135, 445], ''))->toBe([null, true])
        ->and(NetworkScanner::classify([135, 445], '', 'KAR-LT-015'))->toBe([null, true])
        ->and(NetworkScanner::classify([135, 445], '', 'ERWAN-LAPTOP'))->toBe([null, true])
        ->and(NetworkScanner::classify([135, 445], '', 'NERE-LPT-CSR'))->toBe([null, true])
        ->and(NetworkScanner::classify([135, 445, 3389], '', 'OUA-NM-FLS'))->toBe(['server', false])
        ->and(NetworkScanner::classify([80, 443, 445], '', 'OUA-SYNOLOGY'))->toBe(['server', false])
        ->and(NetworkScanner::classify([80, 135, 443, 3389], ''))->toBe(['server', false])
        ->and(NetworkScanner::classify([80], ''))->toBe(['network', false])
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

it('reads printer status, page counter and the device sheet over SNMP', function () {
    expect(\App\Services\Monitor\SensorChecks::printerState(['Prêt.'], "\x00"))->toBe([0, 'Écran : Prêt.'])
        ->and(\App\Services\Monitor\SensorChecks::printerState(['Fermer couvercle du bac de récupération de toner.'], "\x00")[0])->toBe(2)
        ->and(\App\Services\Monitor\SensorChecks::printerState(['En veille...'], "\x20"))->toBe([1, 'Écran : En veille... · Toner bas'])
        ->and(\App\Services\Monitor\SensorChecks::printerState([], "\x04")[0])->toBe(2);

    $snmp = new FakeSnmp([
        '1.3.6.1.2.1.1.1.0' => 'KYOCERA Document Solutions Printing System', '1.3.6.1.2.1.1.3.0' => 360000,
        '1.3.6.1.2.1.25.3.2.1.3.1' => 'TASKalfa 2554ci', '1.3.6.1.2.1.43.5.1.1.17.1' => 'H721400190',
        '1.3.6.1.2.1.25.3.5.1.2.1' => "\x00", '1.3.6.1.2.1.43.16.5.1.2.1.1' => 'Fermer couvercle du bac de récupération de toner.',
        '1.3.6.1.2.1.43.10.2.1.4.1.1' => 365593,
        '1.3.6.1.2.1.43.11.1.1.6.1.1' => 'TK-8368C', '1.3.6.1.2.1.43.11.1.1.8.1.1' => 12000, '1.3.6.1.2.1.43.11.1.1.9.1.1' => 5040,
        '1.3.6.1.2.1.43.8.2.1.9.1.1' => 500, '1.3.6.1.2.1.43.8.2.1.10.1.1' => 150,
        '1.3.6.1.2.1.2.2.1.2.1' => 'eth0', '1.3.6.1.2.1.2.2.1.8.1' => 1, '1.3.6.1.2.1.2.2.1.5.1' => 1000000000,
    ]);
    app()->instance(SnmpClient::class, $snmp);
    $printer = monitorDevice(['name' => 'Imprimante sous-sol', 'host' => '192.168.10.10', 'type' => 'printer'], template: false);
    $printer->applyTemplate(true);
    $sensors = $printer->sensors()->get()->keyBy(fn ($s) => $s->kind->value);
    foreach (['snmp_printer_status', 'snmp_pages'] as $kind) {
        app(MonitorPoller::class)->checkNow($sensors[$kind]);
    }

    expect($sensors['snmp_printer_status']->fresh()->status)->toBe(MonitorStatus::Down)
        ->and($sensors['snmp_printer_status']->fresh()->last_message)->toBe('Écran : Fermer couvercle du bac de récupération de toner.')
        ->and($sensors['snmp_printer_status']->fresh()->formatValue())->toBe('Bloquée')
        ->and($sensors['snmp_pages']->fresh()->last_value)->toBe(365593.0);

    $this->actingAs(monitorTechnician());
    $this->get(route('intranet.monitor.devices.show', $printer))->assertOk()->assertSee('Fiche de l’équipement')->assertSee('Disponibilité sur 30 jours');
    $this->get(route('intranet.monitor.devices.sheet', $printer))->assertOk()
        ->assertSee('TASKalfa 2554ci')->assertSee('H721400190')->assertSee('365 593')
        ->assertSee('TK-8368C')->assertSee('42 %')->assertSee('150 / 500 feuilles')->assertSee('eth0');
});

it('draws maps with icons, dependency lines, alarms, a wall screen and whole groups', function () {
    $technician = monitorTechnician();
    $antenna = monitorDevice(['name' => 'Antenne', 'host' => '10.0.0.2', 'type' => 'antenna']);
    $camera = monitorDevice(['name' => 'Caméra portail', 'host' => '10.0.0.3', 'type' => 'camera', 'parent_device_id' => $antenna->id]);
    $map = MonitorMap::create(['name' => 'Mine']);

    $this->actingAs($technician)->put(route('intranet.monitor.maps.update', $map), ['name' => 'Mine', 'add_group_id' => $antenna->group_id])->assertRedirect();
    expect($map->items()->count())->toBe(2);

    pingFails();
    app(MonitorPoller::class)->poll();
    app(MonitorPoller::class)->poll();

    $this->get(route('intranet.monitor.maps.show', $map))->assertOk()
        ->assertSee('data-line-from="'.$antenna->id.'"', false)
        ->assertSee('stroke-dasharray', false)
        ->assertSee('Alarmes sur cette carte')
        ->assertSee('Écran mural');
    $this->get(route('intranet.monitor.maps.screen', $map))->assertOk()
        ->assertSee('http-equiv="refresh" content="30"', false)
        ->assertSee('Caméra portail')
        ->assertDontSee('intranet-sidebar', false);
    $this->get(route('intranet.monitor.maps'))->assertOk()->assertSee('2 en panne');

    // Déplacement enregistré aussitôt, et croix de retrait en mode modification.
    $this->patchJson(route('intranet.monitor.maps.items.move', [$map, $camera]), ['x' => 33, 'y' => 66])->assertOk()->assertJson(['saved' => true]);
    expect($map->items()->where('device_id', $camera->id)->first()->only('x', 'y'))->toBe(['x' => 33.0, 'y' => 66.0]);
    $this->patchJson(route('intranet.monitor.maps.items.move', [$map, $camera]), ['x' => 140, 'y' => 66])->assertUnprocessable();
    $this->get(route('intranet.monitor.maps.show', [$map, 'edition' => 1]))->assertOk()
        ->assertSee('form="retirer-'.$camera->id.'"', false)
        ->assertSee('id="retirer-'.$camera->id.'"', false);
});

it('sends no e-mail for short outages and groups alerts into one message per check', function () {
    Notification::fake();
    $technician = monitorTechnician();
    $printer = monitorDevice(['name' => 'Imprimante', 'host' => '10.0.0.7', 'type' => 'printer']);
    $poller = app(MonitorPoller::class);

    // Coupure de 3 minutes : en panne à l'écran, mais aucun message (délai par défaut : 10 min).
    pingFails();
    $poller->poll();
    $poller->poll();
    expect($printer->sensors()->where('kind', 'ping')->first()->status)->toBe(MonitorStatus::Down);
    $this->travel(3)->minutes();
    Process::fake(['*' => Process::result(pingReply(5))]);
    $poller->poll();
    Notification::assertNothingSent();

    // Panne qui dure : un seul message pour les capteurs touchés au même moment.
    $printer->sensors()->where('kind', 'tcp')->update(['port' => 1]);
    $this->travel(2)->minutes();
    Process::fake(['*' => Process::result("Délai d'attente dépassé.
")]);
    $poller->poll();
    $poller->poll();
    $this->travel(11)->minutes();
    $poller->poll();

    Notification::assertSentToTimes($technician, MonitorAlert::class, 1);
    Notification::assertSentTo($technician, MonitorAlert::class, function (MonitorAlert $alert) use ($technician) {
        $data = $alert->toArray($technician);

        return $alert->kinds() === ['down'] && str_starts_with($data['title'], 'En panne : Imprimante');
    });

    $sensors = $printer->sensors()->with('device')->get();
    $grouped = new MonitorAlert([[$sensors[0], 'down'], [$sensors[1], 'down'], [$sensors[0], 'recovered']]);
    expect($grouped->toArray($technician)['title'])->toBe('Supervision : 2 en panne, 1 rétabli')
        ->and($grouped->toArray($technician)['url'])->toBe(route('intranet.monitor.index'))
        ->and(implode("
", $grouped->toMail($technician)->introLines))->toContain('En panne : Imprimante · Ping');
});

it('honours weekly maintenance windows, including across midnight', function () {
    $technician = monitorTechnician();
    $device = monitorDevice();
    $this->actingAs($technician)->put(route('intranet.monitor.groups.update', $device->group), [
        'name' => $device->group->name, 'maintenance_days' => [0], 'maintenance_start' => '22:00', 'maintenance_end' => '02:00',
    ])->assertSessionHasNoErrors();
    $device = $device->fresh();

    expect($device->inMaintenance(\Illuminate\Support\Carbon::parse('next sunday 23:00')))->toBeTrue()
        ->and($device->inMaintenance(\Illuminate\Support\Carbon::parse('next monday 01:30')))->toBeTrue()
        ->and($device->inMaintenance(\Illuminate\Support\Carbon::parse('next monday 02:30')))->toBeFalse()
        ->and($device->inMaintenance(\Illuminate\Support\Carbon::parse('next saturday 23:00')))->toBeFalse();

    $this->travelTo(\Illuminate\Support\Carbon::parse('next sunday 23:00'));
    Process::fake();
    expect(app(MonitorPoller::class)->poll())->toBe(0);
    $this->get(route('intranet.monitor.devices.show', $device))->assertSee('En maintenance planifiée jusqu’à 02:00');
});

it('posts alerts to a chat webhook, exposes a JSON status, trends, a watchdog and port monitoring', function () {
    config(['monitor.webhook_url' => 'https://chat.example.test/hook', 'monitor.notify_delay_minutes' => 0]);
    Http::fake(['chat.example.test/*' => Http::response('ok', 200)]);
    Notification::fake();
    $technician = monitorTechnician();
    $device = monitorDevice();
    pingFails();
    app(MonitorPoller::class)->poll();
    app(MonitorPoller::class)->poll();

    Http::assertSent(fn ($request) => $request->url() === 'https://chat.example.test/hook'
        && str_contains($request['text'], 'En panne : Serveur fichiers · Ping'));

    $this->actingAs($technician)->getJson(route('intranet.monitor.status'))->assertOk()
        ->assertJsonPath('counts.down', 1)
        ->assertJsonPath('devices.0.name', 'Serveur fichiers')
        ->assertJsonPath('devices.0.sensors.0.status', 'down');

    $this->get(route('intranet.monitor.reports'))->assertOk()->assertSee('Tendances des 7 derniers jours')->assertSee('Le plus de pannes')->assertSee('Disponibilité de tous les équipements');
    $this->get(route('intranet.monitor.index'))->assertDontSee('Les contrôles ne tournent plus');
    $this->travel(10)->minutes();
    $this->get(route('intranet.monitor.index'))->assertSee('Les contrôles ne tournent plus');

    app()->instance(SnmpClient::class, new FakeSnmp(['1.3.6.1.2.1.1.1.0' => 'Cisco IOS', '1.3.6.1.2.1.2.2.1.2.3' => 'Gi0/3', '1.3.6.1.2.1.2.2.1.8.3' => 1, '1.3.6.1.2.1.2.2.1.5.3' => 1000000000]));
    $this->get(route('intranet.monitor.devices.sheet', $device))->assertOk()->assertSee('Gi0/3')->assertSee('Surveiller le trafic')->assertSee('name="parameter" value="3"', false);
});

/**
 * Faux équipement SNMPv3 : découverte du moteur, vérification de la signature
 * HMAC et déchiffrement AES de la question, réponse chiffrée et signée.
 */
class FakeSnmpV3Agent extends SnmpClient
{
    public const ENGINE = "\x80\x00\x1f\x88\x80NEREAGENT";

    public array $asked = [];

    public function __construct(private string $authPassword, private string $privPassword, private string $algo = 'sha256', private int $macLength = 24) {}

    protected function exchange(string $host, string $packet): string
    {
        $o = 0;
        [, $message] = $this->read($packet, $o);
        $p = 0;
        $this->read($message, $p);
        $this->read($message, $p);
        [, $securityBlob] = $this->read($message, $p);
        $s = 0;
        [, $security] = $this->read($securityBlob, $s);
        $q = 0;
        [, $engine] = $this->read($security, $q);
        $this->read($security, $q);
        $this->read($security, $q);
        [, $user] = $this->read($security, $q);
        [, $mac] = $this->read($security, $q);
        [, $salt] = $this->read($security, $q);
        [, $msgData] = $this->read($message, $p);

        if ($engine === '') {
            return $this->reply('', '', 0x04, $this->tlv(0x30, $this->tlv(0x04, self::ENGINE).$this->tlv(0x04, '').$this->pdu(0xA8, 0, '1.3.6.1.6.3.15.1.1.4.0', $this->tlv(0x41, "\x01"))));
        }
        $key = SnmpClient::localizeKey($this->algo, $this->authPassword, self::ENGINE);
        $expected = substr(hash_hmac($this->algo, str_replace($mac, str_repeat("\0", $this->macLength), $packet), $key, true), 0, $this->macLength);
        if (! hash_equals($expected, $mac)) {
            return $this->reply('', '', 0x04, $this->tlv(0x30, $this->tlv(0x04, self::ENGINE).$this->tlv(0x04, '').$this->pdu(0xA8, 0, '1.3.6.1.6.3.15.1.1.5.0', $this->tlv(0x41, "\x01"))));
        }
        $privKey = substr(SnmpClient::localizeKey($this->algo, $this->privPassword, self::ENGINE), 0, 16);
        $scoped = openssl_decrypt($msgData, 'aes-128-cfb', $privKey, OPENSSL_RAW_DATA, pack('NN', 3, 1000).$salt);
        $c = 0;
        [, $scopedBody] = $this->read($scoped, $c);
        $d = 0;
        $this->read($scopedBody, $d);
        $this->read($scopedBody, $d);
        [, $pdu] = $this->read($scopedBody, $d);
        $e = 0;
        [, $id] = $this->read($pdu, $e);
        $this->asked[] = $user;
        $answer = $this->tlv(0x30, $this->tlv(0x04, self::ENGINE).$this->tlv(0x04, '').$this->pdu(0xA2, (int) $this->toInt($id), '1.3.6.1.2.1.1.3.0', $this->tlv(0x43, "\x10\x92")));
        $newSalt = random_bytes(8);

        return $this->reply($user, $newSalt, 0x03, $this->tlv(0x04, openssl_encrypt($answer, 'aes-128-cfb', $privKey, OPENSSL_RAW_DATA, pack('NN', 3, 1000).$newSalt)));
    }

    private function pdu(int $type, int $id, string $oid, string $value): string
    {
        return $this->tlv($type, $this->integer($id).$this->integer(0).$this->integer(0).$this->tlv(0x30, $this->tlv(0x30, $this->encodeOid($oid).$value)));
    }

    private function reply(string $user, string $salt, int $flags, string $msgData): string
    {
        $security = $this->tlv(0x30, $this->tlv(0x04, self::ENGINE).$this->integer(3).$this->integer(1000).$this->tlv(0x04, $user).$this->tlv(0x04, str_repeat("\0", $this->macLength)).$this->tlv(0x04, $salt));

        return $this->tlv(0x30, $this->integer(3).$this->tlv(0x30, $this->integer(1).$this->integer(65507).$this->tlv(0x04, chr($flags)).$this->integer(3)).$this->tlv(0x04, $security).$msgData);
    }
}

it('speaks SNMPv3 with SHA-256 authentication and AES encryption', function () {
    // Valeurs de référence de la RFC 3414 (A.3.2) pour la localisation des clés.
    expect(bin2hex(SnmpClient::localizeKey('sha1', 'maplesyrup', hex2bin('000000000000000000000002'))))
        ->toBe('6695febc9288e36282235fc7151f128497b38f3f');

    $agent = new FakeSnmpV3Agent('motdepasse-auth', 'motdepasse-chiffre');
    $credentials = ['user' => 'supervision', 'auth' => 'SHA256', 'auth_password' => 'motdepasse-auth', 'priv' => 'AES', 'priv_password' => 'motdepasse-chiffre'];
    expect($agent->get('10.9.9.9', $credentials, ['1.3.6.1.2.1.1.3.0']))->toBe(['1.3.6.1.2.1.1.3.0' => 4242])
        ->and($agent->asked)->toBe(['supervision']);

    // SHA-512 avec un seul mot de passe pour les deux usages, comme sur les Kyocera.
    $kyocera = new FakeSnmpV3Agent('un-seul-motdepasse', 'un-seul-motdepasse', 'sha512', 48);
    expect($kyocera->get('10.9.9.7', ['user' => 'superviseur', 'auth' => 'SHA512', 'auth_password' => 'un-seul-motdepasse', 'priv' => 'AES', 'priv_password' => 'un-seul-motdepasse'], ['1.3.6.1.2.1.1.3.0']))
        ->toBe(['1.3.6.1.2.1.1.3.0' => 4242]);

    $wrong = new FakeSnmpV3Agent('autre-mot-de-passe', 'motdepasse-chiffre');
    expect(fn () => $wrong->get('10.9.9.8', $credentials, ['1.3.6.1.2.1.1.3.0']))->toThrow(RuntimeException::class, 'mot de passe d’authentification refusé');

    // Saisie dans les réglages du groupe, chiffrée en base, puis utilisée par l'équipement.
    $technician = monitorTechnician();
    $device = monitorDevice();
    $this->actingAs($technician)->put(route('intranet.monitor.groups.update', $device->group), [
        'name' => $device->group->name, 'snmp_v3_user' => 'supervision', 'snmp_v3_auth' => 'SHA256',
        'snmp_v3_auth_password' => 'motdepasse-auth', 'snmp_v3_priv' => 'AES', 'snmp_v3_priv_password' => 'motdepasse-chiffre',
    ])->assertSessionHasNoErrors();
    expect(DB::table('monitor_groups')->value('snmp_v3'))->not->toContain('motdepasse')
        ->and($device->fresh()->snmpCredentials())->toBe($credentials);
    $this->get(route('intranet.monitor.groups.edit', $device->group))->assertSee('SNMPv3 actif')->assertDontSee('motdepasse-auth');

    $this->put(route('intranet.monitor.groups.update', $device->group), ['name' => $device->group->name, 'snmp_v3_user' => 'autre', 'snmp_v3_auth' => 'SHA', 'snmp_v3_priv' => 'AES'])
        ->assertSessionHasNoErrors();
    expect($device->fresh()->snmpCredentials()['auth_password'])->toBe('motdepasse-auth');
    $this->put(route('intranet.monitor.groups.update', $device->group), ['name' => $device->group->name, 'clear_snmp_v3' => 1]);
    expect($device->fresh()->snmpCredentials())->toBe('public');
});
