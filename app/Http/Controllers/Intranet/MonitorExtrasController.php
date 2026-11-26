<?php

namespace App\Http\Controllers\Intranet;

use App\Enums\MonitorDeviceType;
use App\Http\Controllers\Controller;
use App\Jobs\RunNetworkDiscovery;
use App\Models\MonitorDevice;
use App\Models\MonitorDiscovery;
use App\Models\MonitorGroup;
use App\Models\MonitorMap;
use App\Models\MonitorSensor;
use App\Services\Monitor\MonitorHistory;
use App\Services\Monitor\NetworkScanner;
use App\Support\PdfRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Cartes, rapports et découverte automatique de la supervision réseau. */
class MonitorExtrasController extends Controller
{
    // Cartes

    public function maps(): View
    {
        return view('intranet.monitor.maps', ['maps' => MonitorMap::query()->with('items.device.sensors.device')->orderBy('name')->get()]);
    }

    public function storeMap(Request $request): RedirectResponse
    {
        $map = MonitorMap::create($request->validate(['name' => ['required', 'string', 'max:120']], [], ['name' => 'nom']));

        return redirect()->route('intranet.monitor.maps.show', [$map, 'edition' => 1]);
    }

    public function showMap(Request $request, MonitorMap $map): View
    {
        $map->load('items.device.sensors.device');

        return view('intranet.monitor.map', [
            'map' => $map,
            'editing' => $request->boolean('edition'),
            'devices' => MonitorDevice::query()->whereNotIn('id', $map->items->pluck('device_id'))->orderBy('name')->get(['id', 'name', 'host']),
            'groups' => MonitorGroup::query()->get()->mapWithKeys(fn (MonitorGroup $g) => [$g->id => $g->path()])->sort(),
        ]);
    }

    /** Mode écran mural : la carte en plein écran, sans menu, avec ses alarmes. */
    public function mapScreen(MonitorMap $map): View
    {
        $map->load('items.device.sensors.device', 'items.device.sensors.acknowledger');

        return view('intranet.monitor.map-screen', ['map' => $map]);
    }

    public function updateMap(Request $request, MonitorMap $map): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:20480'],
            'remove_background' => ['sometimes', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.x' => ['required', 'numeric', 'between:0,100'],
            'items.*.y' => ['required', 'numeric', 'between:0,100'],
            'add_device_id' => ['nullable', 'integer', Rule::exists('monitor_devices', 'id')],
            'add_group_id' => ['nullable', 'integer', Rule::exists('monitor_groups', 'id')],
        ], [], ['name' => 'nom', 'background' => 'image de fond']);

        $map->name = $validated['name'];
        if ($request->hasFile('background') || $request->boolean('remove_background')) {
            if ($map->background_path) {
                Storage::disk('local')->delete($map->background_path);
            }
            $map->background_path = $request->hasFile('background') ? $request->file('background')->store('monitor-maps', 'local') : null;
        }
        $map->save();

        foreach ($validated['items'] ?? [] as $deviceId => $position) {
            $map->items()->where('device_id', $deviceId)->update(['x' => $position['x'], 'y' => $position['y']]);
        }
        if ($deviceId = $validated['add_device_id'] ?? null) {
            $map->items()->firstOrCreate(['device_id' => $deviceId], ['x' => 50, 'y' => 50]);
        }
        if ($groupId = $validated['add_group_id'] ?? null) {
            // Les équipements du groupe arrivent alignés en bas de la carte, prêts à être placés.
            $existing = $map->items()->pluck('device_id');
            MonitorDevice::query()->whereIn('group_id', MonitorGroup::findOrFail($groupId)->descendantIds())
                ->whereNotIn('id', $existing)->orderBy('name')->get()
                ->each(fn (MonitorDevice $device, int $i) => $map->items()->create([
                    'device_id' => $device->id,
                    'x' => 8 + ($i % 8) * 12,
                    'y' => max(10, 88 - intdiv($i, 8) * 10),
                ]));
        }

        return redirect()->route('intranet.monitor.maps.show', [$map, 'edition' => 1])->with('success', 'Carte enregistrée.');
    }

    public function removeMapItem(MonitorMap $map, MonitorDevice $device): RedirectResponse
    {
        $map->items()->where('device_id', $device->id)->delete();

        return redirect()->route('intranet.monitor.maps.show', [$map, 'edition' => 1]);
    }

    public function destroyMap(MonitorMap $map): RedirectResponse
    {
        if ($map->background_path) {
            Storage::disk('local')->delete($map->background_path);
        }
        $map->delete();

        return redirect()->route('intranet.monitor.maps')->with('success', 'Carte supprimée.');
    }

    public function mapBackground(MonitorMap $map): Response
    {
        abort_unless($map->background_path && Storage::disk('local')->exists($map->background_path), 404);

        return Storage::disk('local')->response($map->background_path);
    }

    // Rapports

    public const RANGES = ['hier' => 'Hier', '7j' => '7 derniers jours', '30j' => '30 derniers jours', 'mois' => 'Mois dernier', '12m' => '12 derniers mois', 'perso' => 'Dates choisies'];

    public function report(Request $request, MonitorHistory $history): View|Response|StreamedResponse
    {
        $validated = $request->validate([
            'groupe' => ['nullable', 'integer', Rule::exists('monitor_groups', 'id')],
            'periode' => ['nullable', Rule::in(array_keys(self::RANGES))],
            'du' => ['nullable', 'required_if:periode,perso', 'date'],
            'au' => ['nullable', 'required_if:periode,perso', 'date', 'after_or_equal:du'],
            'format' => ['nullable', Rule::in(['csv', 'pdf'])],
        ], [], ['du' => 'date de début', 'au' => 'date de fin']);
        $range = $validated['periode'] ?? '7j';
        [$from, $to] = match ($range) {
            'hier' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '30j' => [now()->subDays(30), now()],
            'mois' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '12m' => [now()->subYear(), now()],
            'perso' => [Carbon::parse($validated['du'])->startOfDay(), Carbon::parse($validated['au'])->endOfDay()],
            default => [now()->subDays(7), now()],
        };
        $group = isset($validated['groupe']) ? MonitorGroup::find($validated['groupe']) : null;
        $sensors = MonitorSensor::query()
            ->with('device.group')
            ->when($group, fn ($query) => $query->whereHas('device', fn ($q) => $q->whereIn('group_id', $group->descendantIds())))
            ->get()
            ->sortBy(fn (MonitorSensor $s) => $s->device->group->name.' '.$s->device->name.' '.$s->id)
            ->values();
        $rows = $history->report($sensors, $from, $to);
        $title = 'Disponibilité '.($group ? $group->path() : 'de tous les équipements').' du '.$from->format('d/m/Y').' au '.$to->format('d/m/Y');

        if (($validated['format'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Groupe', 'Équipement', 'Adresse', 'Capteur', 'Disponibilité (%)', 'Pannes', 'Durée de panne (min)', 'Moyenne', 'Maximum', 'Unité', 'Mesures'], ';');
                foreach ($rows as $row) {
                    $s = $row['sensor'];
                    fputcsv($out, [$s->device->group->path(), $s->device->name, $s->device->host, $s->name, $row['uptime'], $row['outages'], $row['downtime_minutes'], $row['avg'], $row['max'], $s->unitLabel(), $row['samples']], ';');
                }
                fclose($out);
            }, 'disponibilite-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $data = ['rows' => $rows, 'title' => $title, 'from' => $from, 'to' => $to];
        if (($validated['format'] ?? null) === 'pdf') {
            return response(PdfRenderer::render(view('intranet.monitor.report-pdf', $data)->render(), 'landscape'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="disponibilite-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf"',
            ]);
        }

        // Tendances des 7 derniers jours, tous équipements confondus.
        $week = collect($history->report(MonitorSensor::query()->with('device.group')->get(), now()->subDays(7), now()));
        $trends = [
            'Les moins disponibles' => $week->filter(fn ($row) => $row['uptime'] !== null && $row['uptime'] < 100)->sortBy('uptime')->take(5)
                ->map(fn ($row) => [$row['sensor'], number_format($row['uptime'], 2, ',', ' ').' %']),
            'Le plus de pannes' => $week->filter(fn ($row) => $row['outages'] > 0)->sortByDesc('outages')->take(5)
                ->map(fn ($row) => [$row['sensor'], $row['outages'].' panne(s)'.($row['downtime_minutes'] ? ', '.\App\Services\Monitor\SensorChecks::duration($row['downtime_minutes'] * 60) : '')]),
            'Les plus lents (ping)' => $week->filter(fn ($row) => $row['sensor']->kind === \App\Enums\MonitorSensorKind::Ping && $row['avg'] !== null)->sortByDesc('avg')->take(5)
                ->map(fn ($row) => [$row['sensor'], $row['sensor']->formatValue($row['avg']).' en moyenne']),
        ];

        return view('intranet.monitor.report', $data + [
            'trends' => $trends,
            'groups' => MonitorGroup::query()->get()->mapWithKeys(fn (MonitorGroup $g) => [$g->id => $g->path()])->sort(),
            'ranges' => self::RANGES,
            'filters' => $validated + ['periode' => $range],
        ]);
    }

    // Découverte automatique

    public function discovery(): View
    {
        return view('intranet.monitor.discovery', [
            'discoveries' => MonitorDiscovery::query()->with('group')->latest()->limit(10)->get(),
            'groups' => MonitorGroup::query()->get()->mapWithKeys(fn (MonitorGroup $g) => [$g->id => $g->path()])->sort(),
        ]);
    }

    public function startDiscovery(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cidr' => ['required', 'string', 'max:18'],
            'group_id' => ['required', 'integer', Rule::exists('monitor_groups', 'id')],
        ], [], ['cidr' => 'sous-réseau', 'group_id' => 'groupe']);
        try {
            NetworkScanner::hosts($validated['cidr']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['cidr' => $exception->getMessage()]);
        }
        if (MonitorDiscovery::query()->whereIn('status', ['pending', 'running'])->exists()) {
            throw ValidationException::withMessages(['cidr' => 'Une découverte est déjà en cours. Attendez qu’elle se termine.']);
        }

        $discovery = MonitorDiscovery::create($validated + ['requested_by' => $request->user()->id, 'status' => 'pending']);
        RunNetworkDiscovery::dispatch($discovery->id);

        return redirect()->route('intranet.monitor.discovery.show', $discovery)
            ->with('success', 'Découverte lancée. Elle prend quelques minutes ; la page se met à jour toute seule.');
    }

    public function showDiscovery(MonitorDiscovery $discovery): View
    {
        return view('intranet.monitor.discovery-result', [
            'discovery' => $discovery->load('group'),
            'types' => MonitorDeviceType::options(),
        ]);
    }

    public function importDiscovery(Request $request, MonitorDiscovery $discovery): RedirectResponse
    {
        abort_unless($discovery->status === 'done', 409);
        $found = collect($discovery->results)->keyBy('ip');
        $validated = $request->validate([
            'hosts' => ['required', 'array', 'min:1'],
            'hosts.*' => ['string', Rule::in($found->keys()->all())],
            'types' => ['array'],
            'types.*' => [Rule::enum(MonitorDeviceType::class)],
        ], ['hosts.required' => 'Cochez au moins un équipement à ajouter.']);

        $added = 0;
        DB::transaction(function () use ($validated, $found, $discovery, &$added) {
            foreach ($validated['hosts'] as $ip) {
                if (MonitorDevice::query()->where('host', $ip)->exists()) {
                    continue;
                }
                $type = MonitorDeviceType::from($validated['types'][$ip] ?? $found[$ip]['type'] ?? 'server');
                $device = MonitorDevice::create([
                    'group_id' => $discovery->group_id,
                    'name' => $found[$ip]['name'] ? explode('.', $found[$ip]['name'])[0] : $type->label().' '.$ip,
                    'host' => $ip,
                    'type' => $type,
                ]);
                $device->applyTemplate(filled($found[$ip]['description']));
                $added++;
            }
        });

        return redirect()->route('intranet.monitor.index')->with('success', "{$added} équipement(s) ajouté(s) à la supervision.");
    }
}
