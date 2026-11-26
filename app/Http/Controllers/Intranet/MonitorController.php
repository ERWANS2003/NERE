<?php

namespace App\Http\Controllers\Intranet;

use App\Enums\MonitorDeviceType;
use App\Enums\MonitorSensorKind;
use App\Enums\MonitorStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MonitorDevice;
use App\Models\MonitorEvent;
use App\Models\MonitorGroup;
use App\Models\MonitorSensor;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Notifications\TicketNotification;
use App\Services\Monitor\DeviceInventory;
use App\Services\Monitor\MonitorHistory;
use App\Services\Monitor\MonitorPoller;
use App\Services\TicketSlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MonitorController extends Controller
{
    /** Durées proposées pour mettre en pause ou prendre en compte, en minutes (0 = sans limite). */
    public const DURATIONS = ['15' => '15 minutes', '60' => '1 heure', '180' => '3 heures', '1440' => '1 jour', '0' => 'Jusqu’à reprise manuelle'];

    public function index(Request $request): View
    {
        $filter = MonitorStatus::tryFrom((string) $request->query('etat'));
        $groups = MonitorGroup::query()->orderBy('name')->get();
        $devices = MonitorDevice::query()->with('sensors.device', 'sensors.acknowledger')->orderBy('name')->get();
        $sensors = $devices->flatMap(fn (MonitorDevice $device) => $device->sensors);
        $visible = $filter
            ? $devices->filter(fn (MonitorDevice $device) => $device->sensors->contains(fn (MonitorSensor $s) => $s->displayStatus() === $filter))
            : $devices;

        return view('intranet.monitor.index', [
            'groups' => $groups,
            'devicesByGroup' => $visible->groupBy('group_id'),
            'allDevicesByGroup' => $devices->groupBy('group_id'),
            'counts' => collect(MonitorStatus::cases())->mapWithKeys(fn (MonitorStatus $status) => [
                $status->value => $sensors->filter(fn (MonitorSensor $sensor) => $sensor->displayStatus() === $status)->count(),
            ]),
            'alarms' => $sensors
                ->filter(fn (MonitorSensor $sensor) => $sensor->displayStatus()->isAlarm())
                ->sortBy([fn ($a, $b) => $a->status->rank() <=> $b->status->rank(), fn ($a, $b) => $a->status_changed_at <=> $b->status_changed_at])
                ->values(),
            'filter' => $filter,
            'durations' => self::DURATIONS,
            'lastCheck' => $this->lastCheck($sensors),
        ]);
    }

    /** Dernier contrôle effectué : s'il date, le planificateur ne tourne plus. */
    private function lastCheck($sensors): ?Carbon
    {
        return $sensors->filter(fn (MonitorSensor $sensor) => ! $sensor->isPaused())->max('checked_at');
    }

    /** État en JSON, pour un écran ou un outil externe (même accès que les pages). */
    public function statusJson(): JsonResponse
    {
        $devices = MonitorDevice::query()->with('sensors.device', 'group')->orderBy('name')->get();
        $sensors = $devices->flatMap(fn (MonitorDevice $device) => $device->sensors);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'last_check' => $this->lastCheck($sensors)?->toIso8601String(),
            'counts' => collect(MonitorStatus::cases())->mapWithKeys(fn (MonitorStatus $status) => [
                $status->value => $sensors->filter(fn (MonitorSensor $sensor) => $sensor->displayStatus() === $status)->count(),
            ]),
            'devices' => $devices->map(fn (MonitorDevice $device) => [
                'id' => $device->id,
                'name' => $device->name,
                'host' => $device->host,
                'type' => $device->type->value,
                'group' => $device->group->name,
                'status' => $device->status()->value,
                'sensors' => $device->sensors->map(fn (MonitorSensor $sensor) => [
                    'id' => $sensor->id,
                    'name' => $sensor->name,
                    'kind' => $sensor->kind->value,
                    'status' => $sensor->displayStatus()->value,
                    'value' => $sensor->last_value,
                    'unit' => $sensor->unitLabel(),
                    'message' => $sensor->last_message,
                    'checked_at' => $sensor->checked_at?->toIso8601String(),
                ])->values(),
            ])->values(),
        ]);
    }

    public function logs(Request $request): View
    {
        $filter = MonitorStatus::tryFrom((string) $request->query('etat'));

        return view('intranet.monitor.logs', [
            'events' => MonitorEvent::query()
                ->with('sensor.device', 'user')
                ->when($filter, fn ($query) => $query->where('to_status', $filter))
                ->latest('created_at')->latest('id')
                ->paginate(50)->withQueryString(),
            'filter' => $filter,
        ]);
    }

    // Groupes

    public function createGroup(Request $request): View
    {
        return view('intranet.monitor.group-form', ['group' => new MonitorGroup(['parent_id' => $request->integer('parent') ?: null]), 'parents' => $this->groupOptions()]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        MonitorGroup::create($this->validateGroup($request));

        return redirect()->route('intranet.monitor.index')->with('success', 'Groupe créé.');
    }

    public function editGroup(MonitorGroup $group): View
    {
        return view('intranet.monitor.group-form', ['group' => $group, 'parents' => $this->groupOptions($group)]);
    }

    public function updateGroup(Request $request, MonitorGroup $group): RedirectResponse
    {
        $group->update($this->validateGroup($request, $group));

        return redirect()->route('intranet.monitor.index')->with('success', 'Groupe modifié.');
    }

    public function destroyGroup(MonitorGroup $group): RedirectResponse
    {
        if ($group->devices()->exists() || $group->children()->exists()) {
            return back()->withErrors(['group' => 'Videz d’abord le groupe : déplacez ou retirez ses équipements et sous-groupes.']);
        }
        $group->delete();

        return redirect()->route('intranet.monitor.index')->with('success', 'Groupe supprimé.');
    }

    // Équipements

    public function createDevice(Request $request): View
    {
        abort_if(MonitorGroup::query()->doesntExist(), 409, 'Créez d’abord un groupe.');

        return view('intranet.monitor.device-form', [
            'device' => new MonitorDevice(['group_id' => $request->integer('group') ?: null, 'host' => $request->query('host'), 'type' => MonitorDeviceType::tryFrom((string) $request->query('type'))]),
            'groups' => $this->groupOptions(),
            'devices' => MonitorDevice::query()->orderBy('name')->get(['id', 'name', 'host']),
            'types' => MonitorDeviceType::options(),
        ]);
    }

    public function storeDevice(Request $request): RedirectResponse
    {
        $device = MonitorDevice::create($this->validateDevice($request));
        $device->applyTemplate($request->boolean('snmp'));

        return redirect()->route('intranet.monitor.devices.show', $device)
            ->with('success', 'Équipement ajouté avec les capteurs de son modèle. Le premier contrôle a lieu dans la minute.');
    }

    public function showDevice(MonitorDevice $device, MonitorHistory $history): View
    {
        $device->load('sensors.device', 'group', 'parentDevice');
        $reference = $device->sensors->firstWhere('kind', MonitorSensorKind::Ping) ?? $device->sensors->first();
        $availability = $reference ? collect(['24 heures' => 1, '7 jours' => 7, '30 jours' => 30])
            ->map(fn (int $days) => $history->report(collect([$reference]), now()->subDays($days), now())[0])
            : collect();

        return view('intranet.monitor.device', [
            'availability' => $availability,
            'reference' => $reference,
            'hasSnmp' => $device->sensors->contains(fn (MonitorSensor $sensor) => $sensor->kind->isSnmp()),
            'device' => $device,
            'kinds' => MonitorSensorKind::cases(),
            'durations' => self::DURATIONS,
            'sparklines' => $device->sensors->mapWithKeys(fn (MonitorSensor $sensor) => [$sensor->id => $history->series($sensor, '2j')['points']]),
        ]);
    }

    /** Fiche SNMP de l'équipement, chargée après la page (elle peut prendre quelques secondes). */
    public function deviceSheet(Request $request, MonitorDevice $device, DeviceInventory $inventory): View
    {
        $device->load('group');

        return view('intranet.monitor.partials.inventory', [
            'device' => $device,
            'info' => $inventory->get($device, $request->boolean('actualiser')),
            'watchedPorts' => $device->sensors()->where('kind', MonitorSensorKind::SnmpTraffic)->pluck('parameter')->map(fn ($p) => (string) $p)->all(),
        ]);
    }

    public function editDevice(MonitorDevice $device): View
    {
        return view('intranet.monitor.device-form', [
            'device' => $device,
            'groups' => $this->groupOptions(),
            'devices' => MonitorDevice::query()->whereKeyNot($device->id)->orderBy('name')->get(['id', 'name', 'host']),
            'types' => MonitorDeviceType::options(),
        ]);
    }

    public function updateDevice(Request $request, MonitorDevice $device): RedirectResponse
    {
        $device->update($this->validateDevice($request, $device));

        return redirect()->route('intranet.monitor.devices.show', $device)->with('success', 'Équipement modifié.');
    }

    public function destroyDevice(MonitorDevice $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('intranet.monitor.index')->with('success', 'Équipement retiré de la supervision, avec son historique.');
    }

    public function pauseDevice(Request $request, MonitorDevice $device): RedirectResponse
    {
        $device->update($this->pauseFields($request, ! $device->isPaused()));
        $this->logToSensors($device->sensors()->get(), $request->user(), $device->isPaused() ? 'Équipement mis en pause'.($device->pause_reason ? ' : '.$device->pause_reason : '') : 'Supervision de l’équipement reprise');

        return back()->with('success', $device->isPaused() ? 'Équipement en pause.' : 'Supervision de l’équipement reprise.');
    }

    // Capteurs

    public function storeSensor(Request $request, MonitorDevice $device, MonitorPoller $poller): RedirectResponse
    {
        $data = $this->validateSensor($request);
        $kind = MonitorSensorKind::from($data['kind']);
        $data += ['port' => $kind->defaultPort()];
        foreach ($kind->defaultLimits() as $field => $value) {
            $data[$field] ??= $value;
        }
        $sensor = $device->sensors()->create($data);
        // Premier contrôle tout de suite, pour un résultat immédiat.
        $poller->checkNow($sensor);

        return $request->boolean('quick')
            ? back()->with('success', 'Capteur « '.$sensor->name.' » ajouté : '.$sensor->fresh()->last_message.'.')
            : redirect()->route('intranet.monitor.sensors.show', $sensor)->with('success', 'Capteur ajouté et contrôlé une première fois.');
    }

    public function showSensor(Request $request, MonitorSensor $sensor, MonitorHistory $history): View
    {
        $period = array_key_exists((string) $request->query('periode'), MonitorHistory::PERIODS) ? $request->query('periode') : '2j';
        $sensor->load('device.group', 'acknowledger', 'ticket');

        return view('intranet.monitor.sensor', [
            'sensor' => $sensor,
            'period' => $period,
            'periods' => MonitorHistory::PERIODS,
            'series' => $history->series($sensor, $period),
            'events' => $sensor->events()->with('user')->latest('created_at')->latest('id')->limit(50)->get(),
            'durations' => self::DURATIONS,
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

    public function pauseSensor(Request $request, MonitorSensor $sensor): RedirectResponse
    {
        $pausing = ! ($sensor->paused && ($sensor->paused_until === null || $sensor->paused_until->isFuture()));
        $sensor->update($this->pauseFields($request, $pausing) + ($pausing ? [] : ['next_check_at' => null]));
        $this->logToSensors(collect([$sensor]), $request->user(), $pausing ? 'Mis en pause'.($sensor->pause_reason ? ' : '.$sensor->pause_reason : '') : 'Reprise');

        return back()->with('success', $pausing ? 'Capteur en pause.' : 'Capteur repris.');
    }

    public function checkSensor(MonitorSensor $sensor, MonitorPoller $poller): RedirectResponse
    {
        $poller->checkNow($sensor);

        return back()->with('success', 'Contrôle effectué : '.$sensor->fresh()->last_message.'.');
    }

    public function acknowledge(Request $request, MonitorSensor $sensor): RedirectResponse
    {
        abort_unless($sensor->status === MonitorStatus::Down, 422, 'Seul un capteur en panne peut être pris en compte.');
        $validated = $request->validate([
            'acknowledgement' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', Rule::in(array_keys(self::DURATIONS))],
        ]);
        $minutes = (int) ($validated['duration'] ?? 0);

        $sensor->forceFill([
            'status' => MonitorStatus::DownAcknowledged,
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
            'acknowledged_until' => $minutes > 0 ? now()->addMinutes($minutes) : null,
            'acknowledgement' => $validated['acknowledgement'] ?? null,
        ])->save();
        MonitorEvent::create([
            'sensor_id' => $sensor->id, 'user_id' => $request->user()->id,
            'from_status' => MonitorStatus::Down, 'to_status' => MonitorStatus::DownAcknowledged,
            'message' => 'Pris en compte'.($minutes > 0 ? ' pour '.self::DURATIONS[(string) $minutes] : '').($sensor->acknowledgement ? ' : '.$sensor->acknowledgement : ''),
        ]);

        return back()->with('success', 'Panne prise en compte : plus de rappel ni d’escalade tant qu’elle dure.');
    }

    /** Ouvre un ticket au support informatique à partir d'une alarme. */
    public function createTicket(Request $request, MonitorSensor $sensor, TicketSlaService $sla): RedirectResponse
    {
        if ($sensor->ticket_id) {
            return redirect()->route('intranet.tickets.show', $sensor->ticket_id);
        }
        $sensor->load('device');
        $it = Department::query()->where('code', 'IT')->where('is_active', true)->firstOrFail();
        $device = $sensor->device;

        $ticket = DB::transaction(function () use ($request, $it, $sensor, $device, $sla) {
            $ticket = Ticket::create([
                'number' => 'tmp-'.Str::uuid(),
                'department_id' => $it->id,
                'requester_id' => $request->user()->id,
                'subject' => Str::limit("Supervision : {$device->name} · {$sensor->name} – {$sensor->displayStatus()->label()}", 180, ''),
                'description' => "Alarme de la supervision réseau.\n\nÉquipement : {$device->name} ({$device->host})\nCapteur : {$sensor->name} ({$sensor->kind->label()})\nÉtat : {$sensor->displayStatus()->label()} depuis le ".($sensor->status_changed_at?->format('d/m/Y H:i') ?? '-')."\nDernier résultat : {$sensor->last_message}\n\n".route('intranet.monitor.sensors.show', $sensor),
                'category' => in_array($device->type, [MonitorDeviceType::Printer, MonitorDeviceType::Camera, MonitorDeviceType::Server], true) ? 'hardware' : 'network',
                'priority' => $sensor->status === MonitorStatus::Warning ? TicketPriority::Normal : TicketPriority::High,
                'status' => TicketStatus::New,
            ]);
            $ticket->update(['number' => 'IT-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            $sla->setDeadlines($ticket);
            TicketActivity::create(['ticket_id' => $ticket->id, 'user_id' => $request->user()->id, 'event' => 'created']);
            $sensor->forceFill(['ticket_id' => $ticket->id])->save();

            return $ticket;
        });

        $agents = User::query()->where('actif', true)->handlersOf($it->id)->get()->reject(fn (User $agent) => $agent->is($request->user()));
        Notification::send($agents, new TicketNotification($ticket, 'Nouveau ticket à traiter', 'Ticket ouvert depuis une alarme de la supervision réseau.'));

        return redirect()->route('intranet.tickets.show', $ticket)->with('success', 'Ticket '.$ticket->number.' créé depuis l’alarme.');
    }

    // Aides

    /** @return array<int, string> groupes par chemin, sans le groupe lui-même ni ses descendants */
    private function groupOptions(?MonitorGroup $exclude = null): array
    {
        $excluded = $exclude?->exists ? $exclude->descendantIds() : [];

        return MonitorGroup::query()->whereNotIn('id', $excluded)->get()
            ->mapWithKeys(fn (MonitorGroup $group) => [$group->id => $group->path()])
            ->sort()->all();
    }

    private function settingRules(): array
    {
        return [
            'interval_seconds' => ['nullable', 'integer', 'min:30', 'max:86400'],
            'notify_delay_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'notify_repeat_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'escalate_after_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'snmp_community' => ['nullable', 'string', 'max:64'],
        ];
    }

    private const SETTING_NAMES = ['interval_seconds' => 'intervalle', 'notify_delay_minutes' => 'délai avant alerte', 'notify_repeat_minutes' => 'répétition', 'escalate_after_minutes' => 'escalade', 'snmp_community' => 'communauté SNMP', 'name' => 'nom', 'host' => 'adresse IP ou nom', 'group_id' => 'groupe', 'parent_id' => 'groupe parent', 'parent_device_id' => 'équipement parent'];

    /** Fenêtre de maintenance hebdomadaire saisie, ou null pour reprendre celle du niveau au-dessus. */
    private function maintenanceFrom(Request $request): ?array
    {
        $validated = $request->validate([
            'maintenance_days' => ['nullable', 'array'],
            'maintenance_days.*' => ['integer', 'between:0,6'],
            'maintenance_start' => ['nullable', 'required_with:maintenance_days', 'date_format:H:i'],
            'maintenance_end' => ['nullable', 'required_with:maintenance_days', 'date_format:H:i', 'different:maintenance_start'],
        ], [], ['maintenance_start' => 'début de maintenance', 'maintenance_end' => 'fin de maintenance']);

        return empty($validated['maintenance_days']) ? null : [
            'days' => array_values(array_unique(array_map('intval', $validated['maintenance_days']))),
            'start' => $validated['maintenance_start'],
            'end' => $validated['maintenance_end'],
        ];
    }

    private function keepSecret(Request $request, array $data, ?object $model): array
    {
        $data['maintenance'] = $this->maintenanceFrom($request);
        if ($request->boolean('clear_snmp_community')) {
            $data['snmp_community'] = null;
        } elseif (blank($data['snmp_community'] ?? null) && $model?->exists) {
            unset($data['snmp_community']);
        }

        if ($request->boolean('clear_snmp_v3')) {
            $data['snmp_v3'] = null;
        } elseif (filled($request->input('snmp_v3_user'))) {
            $data['snmp_v3'] = $this->snmpV3From($request, $model?->snmp_v3 ?? []);
        }

        return $data;
    }

    /** Identifiants SNMPv3 saisis ; un mot de passe laissé vide garde le précédent. */
    private function snmpV3From(Request $request, array $previous): array
    {
        $v = $request->validate([
            'snmp_v3_user' => ['required', 'string', 'max:32'],
            'snmp_v3_auth' => ['required', Rule::in(array_keys(\App\Services\Monitor\SnmpClient::AUTH))],
            'snmp_v3_auth_password' => ['nullable', 'string', 'min:8', 'max:64'],
            'snmp_v3_priv' => ['required', Rule::in(['AES', 'none'])],
            'snmp_v3_priv_password' => ['nullable', 'string', 'min:8', 'max:64'],
        ], [], [
            'snmp_v3_user' => 'utilisateur SNMPv3', 'snmp_v3_auth_password' => 'mot de passe d’authentification',
            'snmp_v3_priv_password' => 'mot de passe de chiffrement',
        ]);
        $authPassword = $v['snmp_v3_auth_password'] ?: ($previous['auth_password'] ?? null);
        $privPassword = $v['snmp_v3_priv_password'] ?: ($previous['priv_password'] ?? null);
        $errors = array_filter([
            'snmp_v3_auth_password' => $authPassword ? null : 'Le mot de passe d’authentification est obligatoire (8 caractères au moins).',
            'snmp_v3_priv_password' => $v['snmp_v3_priv'] === 'AES' && ! $privPassword ? 'Le mot de passe de chiffrement est obligatoire (8 caractères au moins).' : null,
        ]);
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'user' => $v['snmp_v3_user'],
            'auth' => $v['snmp_v3_auth'],
            'auth_password' => $authPassword,
            'priv' => $v['snmp_v3_priv'],
            'priv_password' => $v['snmp_v3_priv'] === 'AES' ? $privPassword : null,
        ];
    }

    private function validateGroup(Request $request, ?MonitorGroup $group = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', Rule::exists('monitor_groups', 'id'), Rule::notIn($group?->exists ? $group->descendantIds() : [])],
            ...$this->settingRules(),
        ], ['parent_id.not_in' => 'Un groupe ne peut pas être rangé dans lui-même ou un de ses sous-groupes.'], self::SETTING_NAMES);

        return $this->keepSecret($request, $data, $group);
    }

    private function validateDevice(Request $request, ?MonitorDevice $device = null): array
    {
        $request->merge(['host' => strtolower(trim((string) $request->input('host')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'host' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', Rule::unique('monitor_devices', 'host')->ignore($device?->id)],
            'type' => ['required', Rule::enum(MonitorDeviceType::class)],
            'group_id' => ['required', 'integer', Rule::exists('monitor_groups', 'id')],
            'parent_device_id' => ['nullable', 'integer', Rule::exists('monitor_devices', 'id'), Rule::notIn([$device?->id])],
            ...$this->settingRules(),
        ], [
            'host.regex' => 'Saisissez une adresse IP ou un nom d’hôte, sans plage ni espace.',
            'host.unique' => 'Cet équipement est déjà supervisé.',
            'parent_device_id.not_in' => 'Un équipement ne peut pas dépendre de lui-même.',
        ], self::SETTING_NAMES);

        // Pas de boucle de dépendances : le parent ne doit pas dépendre de cet équipement.
        if ($device?->exists && ($parentId = $data['parent_device_id'] ?? null)) {
            $seen = [];
            while ($parentId && ! isset($seen[$parentId])) {
                if ($parentId === $device->id) {
                    throw ValidationException::withMessages(['parent_device_id' => 'Ce choix créerait une boucle de dépendances.']);
                }
                $seen[$parentId] = true;
                $parentId = MonitorDevice::query()->whereKey($parentId)->value('parent_device_id');
            }
        }

        return $this->keepSecret($request, $data, $device);
    }

    private function validateSensor(Request $request, ?MonitorSensorKind $kind = null): array
    {
        $kind ??= MonitorSensorKind::tryFrom((string) $request->input('kind'));
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'interval_seconds' => ['nullable', 'integer', 'min:30', 'max:86400'],
            'unit' => ['nullable', 'string', 'max:20'],
        ];
        foreach (['warn_above', 'error_above', 'warn_below', 'error_below'] as $limit) {
            $rules[$limit] = ['nullable', 'numeric'];
        }
        if ($request->routeIs('intranet.monitor.sensors.store')) {
            $rules['kind'] = ['required', Rule::enum(MonitorSensorKind::class)];
        }
        if ($kind === MonitorSensorKind::Tcp) {
            $rules['port'] = ['required', 'integer', 'min:1', 'max:65535'];
        } elseif ($kind?->needsPort()) {
            $rules['port'] = ['nullable', 'integer', 'min:1', 'max:65535'];
        }
        if ($kind === MonitorSensorKind::Http) {
            $rules['parameter'] = ['nullable', 'string', 'max:255', 'starts_with:/'];
        } elseif ($kind === MonitorSensorKind::SnmpCustom) {
            $rules['parameter'] = ['required', 'string', 'max:255', 'regex:/^\.?\d+(\.\d+)+$/'];
        } elseif ($kind?->parameterLabel()) {
            $rules['parameter'] = ['nullable', 'string', 'max:255'];
        }

        return $request->validate($rules, ['parameter.regex' => 'Saisissez un OID numérique, ex. 1.3.6.1.2.1.1.3.0.'], [
            'name' => 'nom', 'port' => 'port', 'parameter' => 'paramètre', 'interval_seconds' => 'intervalle',
            'warn_above' => 'avertissement au-dessus de', 'error_above' => 'panne au-dessus de', 'warn_below' => 'avertissement en dessous de', 'error_below' => 'panne en dessous de',
        ]);
    }

    private function pauseFields(Request $request, bool $pausing): array
    {
        if (! $pausing) {
            return ['paused' => false, 'paused_until' => null, 'pause_reason' => null];
        }
        $validated = $request->validate([
            'duration' => ['nullable', Rule::in(array_keys(self::DURATIONS))],
            'until' => ['nullable', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [], ['until' => 'date de fin', 'reason' => 'motif']);
        $minutes = (int) ($validated['duration'] ?? 0);

        return [
            'paused' => true,
            'paused_until' => isset($validated['until']) ? Carbon::parse($validated['until']) : ($minutes > 0 ? now()->addMinutes($minutes) : null),
            'pause_reason' => $validated['reason'] ?? null,
        ];
    }

    private function logToSensors($sensors, User $user, string $message): void
    {
        foreach ($sensors as $sensor) {
            MonitorEvent::create(['sensor_id' => $sensor->id, 'user_id' => $user->id, 'message' => $message]);
        }
    }
}
