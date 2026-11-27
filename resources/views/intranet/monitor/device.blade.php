@php
    $breadcrumbs = [
        ['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')],
        ['label' => $device->group->path(), 'url' => route('intranet.monitor.index')],
        ['label' => $device->name],
    ];
@endphp

<x-intranet-layout :title="$device->name" :breadcrumbs="$breadcrumbs">
    @include('intranet.monitor.partials.nav')

    @php $deviceStatus = $device->status(); @endphp
    <section class="carte mb-6 border-l-4 {{ $deviceStatus->accent() }} p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                @include('intranet.monitor.partials.device-icon', ['type' => $device->type, 'status' => $deviceStatus, 'size' => 'lg'])
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-graphite-900 sm:text-3xl">{{ $device->name }}</h1>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $deviceStatus->color() }}">{{ $deviceStatus->label() }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-md bg-graphite-100 px-2 py-1 font-medium text-graphite-700">{{ $device->type->label() }}</span>
                        <span class="rounded-md bg-graphite-100 px-2 py-1 font-mono text-graphite-700">{{ $device->host }}</span>
                        <span class="rounded-md bg-graphite-100 px-2 py-1 text-graphite-700">{{ $device->group->path() }}</span>
                        @if($device->parentDevice)
                            <a href="{{ route('intranet.monitor.devices.show', $device->parentDevice) }}" class="rounded-md bg-nere-50 px-2 py-1 font-medium text-nere-700 hover:underline">Dépend de {{ $device->parentDevice->name }}</a>
                        @endif
                    </div>
                    @if($device->inMaintenance())
                        <p class="mt-2 text-sm text-blue-800">En maintenance planifiée jusqu’à {{ $device->maintenanceWindow()['end'] }} : contrôles et alertes suspendus.</p>
                    @elseif($device->isPaused())
                        <p class="mt-2 text-sm text-blue-800">En pause{{ $device->paused_until ? ' jusqu’au '.$device->paused_until->format('d/m/Y à H:i') : '' }}{{ $device->pause_reason ? ' : '.$device->pause_reason : '' }}</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2" data-auto-refresh data-interval="60000">
                @include('intranet.monitor.partials.pause-form', ['paused' => $device->isPaused(), 'action' => route('intranet.monitor.devices.pause', $device)])
                <a href="{{ route('intranet.monitor.devices.edit', $device) }}" class="bouton-secondaire">Modifier</a>
                <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
                <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
            </div>
        </div>
    </section>

    @if($reference)
        <section class="mb-6 grid gap-3 sm:grid-cols-3" aria-label="Disponibilité">
            @foreach($availability as $period => $row)
                <div class="carte p-4">
                    <p class="text-sm text-graphite-500">Disponibilité sur {{ $period }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $row['uptime'] !== null && $row['uptime'] < 99 ? 'text-red-700' : 'text-graphite-900' }}">{{ $row['uptime'] === null ? '-' : number_format($row['uptime'], 2, ',', ' ').' %' }}</p>
                    <p class="mt-1 text-xs text-graphite-500">
                        {{ $row['outages'] }} panne(s){{ $row['downtime_minutes'] ? ', '.\App\Services\Monitor\SensorChecks::duration($row['downtime_minutes'] * 60).' au total' : '' }}
                        @if($row['avg'] !== null) · {{ $reference->name }} moyen {{ $reference->formatValue($row['avg']) }} @endif
                    </p>
                </div>
            @endforeach
        </section>
    @endif

    <section class="carte mb-6 overflow-hidden">
        <h2 class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">Capteurs</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-graphite-50 text-left text-xs uppercase tracking-wide text-graphite-500">
                    <tr>
                        <th class="px-5 py-2">État</th>
                        <th class="px-5 py-2">Capteur</th>
                        <th class="px-5 py-2">Valeur</th>
                        <th class="w-56 px-5 py-2">2 jours</th>
                        <th class="px-5 py-2">Contrôlé</th>
                        <th class="px-5 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite-100">
                    @forelse($device->sensors as $sensor)
                        <tr>
                            <td class="px-5 py-3"><span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $sensor->displayStatus()->soft() }}"><span class="h-2 w-2 rounded-full {{ $sensor->displayStatus()->dot() }}"></span>{{ $sensor->displayStatus()->label() }}</span></td>
                            <td class="px-5 py-3">
                                <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="font-medium text-graphite-900 hover:text-nere-700">{{ $sensor->name }}</a>
                                <p class="text-sm text-graphite-700">{{ $sensor->last_message ?? 'Pas encore contrôlé' }}</p>
                                <p class="text-xs text-graphite-400">{{ $sensor->kind->label() }} · {{ $sensor->target() }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-base font-semibold tabular-nums text-graphite-900">{{ $sensor->formatValue() }}</td>
                            <td class="px-5 py-3">
                                @include('intranet.monitor.partials.chart', ['points' => $sparklines[$sensor->id], 'height' => 32, 'compact' => true, 'label' => $sensor->name.' sur 2 jours', 'unit' => $sensor->unitLabel()])
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-graphite-500">{{ $sensor->checked_at?->diffForHumans() ?? 'jamais' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <form method="POST" action="{{ route('intranet.monitor.sensors.check', $sensor) }}" class="inline">
                                    @csrf
                                    <button class="font-medium text-nere-700 hover:underline">Vérifier maintenant</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-4 text-graphite-600">Aucun capteur. Ajoutez-en un ci-dessous.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="carte mb-6 p-5" x-data="{ html: '', loading: true, load(refresh = false) { this.loading = true; fetch(@js(route('intranet.monitor.devices.sheet', $device)) + (refresh ? '?actualiser=1' : ''), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.text()).then(t => { this.html = t; this.loading = false }).catch(() => { this.html = '<p class=&quot;text-sm text-red-700&quot;>Fiche impossible à charger.</p>'; this.loading = false }) } }" x-init="load()">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="font-semibold text-graphite-900">Fiche de l’équipement</h2>
            <button type="button" class="text-sm font-medium text-nere-700 hover:underline" @click="load(true)" :disabled="loading">Relire maintenant</button>
        </div>
        <p class="text-sm text-graphite-500" x-show="loading">Lecture des informations de l’équipement…</p>
        <div x-show="! loading" x-html="html"></div>
    </section>

    @php
        $quick = collect([
            ['kind' => 'tcp', 'name' => 'Bureau à distance', 'port' => 3389],
            ['kind' => 'tcp', 'name' => 'SSH', 'port' => 22],
            ['kind' => 'http', 'name' => 'Site web', 'port' => 80],
            ['kind' => 'http', 'name' => 'Site web sécurisé', 'port' => 443],
            ['kind' => 'ssl', 'name' => 'Certificat SSL', 'port' => 443],
            ['kind' => 'tcp', 'name' => 'Base PostgreSQL', 'port' => 5432],
            ['kind' => 'snmp_uptime', 'name' => 'Durée de fonctionnement (SNMP)', 'port' => null],
            ...($device->type === \App\Enums\MonitorDeviceType::Printer ? [
                ['kind' => 'snmp_printer_status', 'name' => 'État de l’imprimante', 'port' => null],
                ['kind' => 'snmp_printer', 'name' => 'Consommables', 'port' => null],
                ['kind' => 'snmp_pages', 'name' => 'Compteur de pages', 'port' => null],
            ] : []),
            ...($device->type === \App\Enums\MonitorDeviceType::Server ? [
                ['kind' => 'snmp_cpu', 'name' => 'Processeur', 'port' => null],
                ['kind' => 'snmp_memory', 'name' => 'Mémoire', 'port' => null],
                ['kind' => 'snmp_disk', 'name' => 'Disque', 'port' => null],
            ] : []),
        ])->reject(fn ($q) => $device->sensors->contains(fn ($s) => $s->kind->value === $q['kind'] && $s->port === $q['port']));
    @endphp
    @if($quick->isNotEmpty())
        <section class="carte mb-4 p-5">
            <h2 class="mb-3 font-semibold text-graphite-900">Ajout rapide</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($quick as $item)
                    <form method="POST" action="{{ route('intranet.monitor.sensors.store', $device) }}">
                        @csrf
                        <input type="hidden" name="quick" value="1">
                        @foreach($item as $field => $value)
                            @if($value !== null)<input type="hidden" name="{{ $field }}" value="{{ $value }}">@endif
                        @endforeach
                        <button class="rounded-full border border-graphite-300 px-3 py-1.5 text-sm font-medium text-graphite-700 hover:border-nere-300 hover:bg-nere-50 hover:text-nere-700">+ {{ $item['name'] }}{{ $item['port'] && $item['kind'] === 'tcp' ? ' ('.$item['port'].')' : '' }}</button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    <details class="carte p-5" @if($errors->any()) open @endif>
        <summary class="cursor-pointer font-semibold text-graphite-900">Ajouter un autre capteur</summary>
        <form method="POST" action="{{ route('intranet.monitor.sensors.store', $device) }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
              x-data="{ kind: @js(old('kind', 'tcp')), needsPort: @js(collect($kinds)->filter->needsPort()->map->value->values()), parameters: @js(collect($kinds)->mapWithKeys(fn ($k) => [$k->value => $k->parameterLabel()])) }">
            @csrf
            <label class="block">
                <span class="etiquette">Type de capteur</span>
                <select name="kind" x-model="kind" class="champ">
                    @foreach($kinds as $kind)
                        <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="etiquette">Nom</span>
                <input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Bureau à distance" class="champ">
                @error('name')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block" x-show="needsPort.includes(kind)">
                <span class="etiquette">Port</span>
                <input name="port" type="number" min="1" max="65535" value="{{ old('port') }}" placeholder="3389" class="champ" :disabled="! needsPort.includes(kind)">
                @error('port')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block" x-show="parameters[kind]" x-cloak>
                <span class="etiquette" x-text="parameters[kind]"></span>
                <input name="parameter" value="{{ old('parameter') }}" maxlength="255" class="champ" :disabled="! parameters[kind]">
                @error('parameter')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block" x-show="kind === 'snmp_custom'" x-cloak>
                <span class="etiquette">Unité</span>
                <input name="unit" value="{{ old('unit') }}" maxlength="20" placeholder="dBm" class="champ" :disabled="kind !== 'snmp_custom'">
            </label>
            <p class="text-xs text-graphite-500 sm:col-span-2 lg:col-span-3">Ports courants : 22 SSH, 80 web, 443 web sécurisé, 554 vidéo RTSP, 3389 bureau à distance, 5432 PostgreSQL, 9100 impression. Les seuils par défaut du type sont appliqués ; vous pouvez les régler ensuite sur la page du capteur.</p>
            <div class="sm:col-span-2 lg:col-span-3">
                <button class="bouton-primaire">Ajouter le capteur</button>
            </div>
        </form>
    </details>

</x-intranet-layout>
