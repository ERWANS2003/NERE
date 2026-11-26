<?php

namespace App\Http\Controllers\Intranet;

use App\Enums\MonitorDeviceType;
use App\Enums\MonitorSensorKind;
use App\Enums\MonitorStatus;
use App\Http\Controllers\Controller;
use App\Models\MonitorDevice;
use App\Models\MonitorSensor;
use App\Services\Monitor\MonitorHistory;
use App\Services\Monitor\MonitorPoller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function index(Request $request): View
    {
        $filter = MonitorStatus::tryFrom((string) $request->query('etat'));
        $devices = MonitorDevice::query()->with('sensors.device', 'sensors.acknowledger')->orderBy('site')->orderBy('name')->get();
        $sensors = $devices->flatMap->sensors;

        return view('intranet.monitor.index', [
            'sites' => $devices
                ->when($filter, fn ($all) => $all->filter(fn (MonitorDevice $device) => $device->sensors
                    ->contains(fn (MonitorSensor $sensor) => $sensor->displayStatus() === $filter)))
                ->groupBy('site'),
            'counts' => collect(MonitorStatus::cases())->mapWithKeys(fn (MonitorStatus $status) => [
                $status->value => $sensors->filter(fn (MonitorSensor $sensor) => $sensor->displayStatus() === $status)->count(),
            ]),
            'alerts' => $sensors
                ->filter(fn (MonitorSensor $sensor) => in_array($sensor->displayStatus(), [MonitorStatus::Down, MonitorStatus::Warning], true))
                ->sortBy([fn ($a, $b) => $a->status->rank() <=> $b->status->rank(), fn ($a, $b) => $a->status_changed_at <=> $b->status_changed_at]),
            'filter' => $filter,
            'types' => MonitorDeviceType::options(),
            'siteNames' => $devices->pluck('site')->unique()->sort()->values(),
        ]);
    }

    public function storeDevice(Request $request): RedirectResponse
    {
        $device = MonitorDevice::create($this->validateDevice($request));

        return redirect()->route('intranet.monitor.devices.show', $device)
            ->with('success', 'Équipement ajouté avec ses capteurs par défaut. Le premier contrôle a lieu dans la minute.');
    }

    public function showDevice(MonitorDevice $device, MonitorHistory $history): View
    {
        $device->load('sensors.device');

        return view('intranet.monitor.device', [
            'device' => $device,
            'types' => MonitorDeviceType::options(),
            'kinds' => MonitorSensorKind::options(),
            'sparklines' => $device->sensors->mapWithKeys(fn (MonitorSensor $sensor) => [$sensor->id => $history->series($sensor, '24h')]),
        ]);
    }

    public function updateDevice(Request $request, MonitorDevice $device): RedirectResponse
    {
        $device->update($this->validateDevice($request, $device));

        return back()->with('success', 'Équipement modifié.');
    }

    public function destroyDevice(MonitorDevice $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('intranet.monitor.index')->with('success', 'Équipement retiré de la supervision, avec son historique.');
    }

    public function toggleDevice(MonitorDevice $device): RedirectResponse
    {
        $device->update(['paused' => ! $device->paused]);

        return back()->with('success', $device->paused ? 'Supervision de l’équipement en pause.' : 'Supervision de l’équipement reprise.');
    }

    public function storeSensor(Request $request, MonitorDevice $device): RedirectResponse
    {
        $device->sensors()->create($this->validateSensor($request));

        return back()->with('success', 'Capteur ajouté.');
    }

    public function showSensor(Request $request, MonitorSensor $sensor, MonitorHistory $history): View
    {
        $period = array_key_exists((string) $request->query('periode'), MonitorHistory::PERIODS) ? $request->query('periode') : '24h';
        $sensor->load('device', 'acknowledger');

        return view('intranet.monitor.sensor', [
            'sensor' => $sensor,
            'period' => $period,
            'periods' => MonitorHistory::PERIODS,
            'series' => $history->series($sensor, $period),
            'events' => $sensor->events()->latest('created_at')->limit(50)->get(),
        ]);
    }

    public function updateSensor(Request $request, MonitorSensor $sensor): RedirectResponse
    {
        $sensor->update($this->validateSensor($request, $sensor->kind));

        return back()->with('success', 'Capteur modifié.');
    }

    public function destroySensor(MonitorSensor $sensor): RedirectResponse
    {
        $device = $sensor->device;
        $sensor->delete();

        return redirect()->route('intranet.monitor.devices.show', $device)->with('success', 'Capteur supprimé.');
    }

    public function toggleSensor(MonitorSensor $sensor): RedirectResponse
    {
        $sensor->update(['paused' => ! $sensor->paused]);

        return back()->with('success', $sensor->paused ? 'Capteur en pause.' : 'Capteur repris.');
    }

    public function checkSensor(MonitorSensor $sensor, MonitorPoller $poller): RedirectResponse
    {
        $poller->checkNow($sensor->load('device'));

        return back()->with('success', 'Contrôle effectué : '.$sensor->fresh()->last_message.'.');
    }

    public function acknowledge(Request $request, MonitorSensor $sensor): RedirectResponse
    {
        abort_unless($sensor->status === MonitorStatus::Down, 422, 'Seul un capteur en panne peut être pris en compte.');
        $validated = $request->validate(['acknowledgement' => ['nullable', 'string', 'max:255']]);

        $sensor->forceFill([
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
            'acknowledgement' => $validated['acknowledgement'] ?? null,
        ])->save();

        return back()->with('success', 'Panne prise en compte : l’équipe voit que vous vous en occupez.');
    }

    /** @return array<string, mixed> */
    private function validateDevice(Request $request, ?MonitorDevice $device = null): array
    {
        $request->merge(['host' => strtolower(trim((string) $request->input('host')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'host' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', Rule::unique('monitor_devices', 'host')->ignore($device?->id)],
            'type' => ['required', Rule::enum(MonitorDeviceType::class)],
            'site' => ['required', 'string', 'max:100'],
        ], [
            'host.regex' => 'Saisissez une adresse IP ou un nom d’hôte, sans plage ni espace.',
            'host.unique' => 'Cet équipement est déjà supervisé.',
        ], ['host' => 'adresse IP ou nom', 'name' => 'nom', 'site' => 'site']);
    }

    /** @return array<string, mixed> */
    private function validateSensor(Request $request, ?MonitorSensorKind $kind = null): array
    {
        $kind ??= MonitorSensorKind::tryFrom((string) $request->input('kind'));
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'warning_ms' => ['nullable', 'integer', 'min:1', 'max:60000'],
        ];
        if ($request->routeIs('intranet.monitor.sensors.store')) {
            $rules['kind'] = ['required', Rule::enum(MonitorSensorKind::class)];
        }
        if ($kind !== MonitorSensorKind::Ping) {
            $rules['port'] = ['required', 'integer', 'min:1', 'max:65535'];
        }
        if ($kind === MonitorSensorKind::Http) {
            $rules['path'] = ['nullable', 'string', 'max:255', 'starts_with:/'];
        }

        return $request->validate($rules, [], ['name' => 'nom', 'port' => 'port', 'warning_ms' => 'seuil', 'path' => 'chemin']);
    }
}
