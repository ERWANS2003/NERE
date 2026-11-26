@php
    $breadcrumbs = [
        ['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')],
        ['label' => $device->group->path(), 'url' => route('intranet.monitor.index')],
        ['label' => $device->name],
    ];
@endphp

<x-intranet-layout :title="$device->name" :breadcrumbs="$breadcrumbs">
    @include('intranet.monitor.partials.nav')

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-graphite-900">{{ $device->name }}</h1>
                @include('intranet.monitor.partials.badge', ['status' => $device->status()])
            </div>
            <p class="mt-1 text-graphite-600">{{ $device->type->label() }} · {{ $device->host }} · {{ $device->group->path() }}</p>
            @if($device->parentDevice)
                <p class="mt-1 text-sm text-graphite-600">Dépend de <a href="{{ route('intranet.monitor.devices.show', $device->parentDevice) }}" class="font-medium text-nere-700 hover:underline">{{ $device->parentDevice->name }}</a></p>
            @endif
            @if($device->isPaused())
                <p class="mt-1 text-sm text-blue-800">En pause{{ $device->paused_until ? ' jusqu’au '.$device->paused_until->format('d/m/Y à H:i') : '' }}{{ $device->pause_reason ? ' : '.$device->pause_reason : '' }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2" data-auto-refresh data-interval="60000">
            @include('intranet.monitor.partials.pause-form', ['paused' => $device->isPaused(), 'action' => route('intranet.monitor.devices.pause', $device)])
            <a href="{{ route('intranet.monitor.devices.edit', $device) }}" class="bouton-secondaire">Modifier</a>
            <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
            <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
        </div>
    </div>

    <section class="carte mb-6 overflow-hidden">
        <h2 class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">Capteurs</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-graphite-50 text-left text-xs uppercase tracking-wide text-graphite-500">
                    <tr>
                        <th class="px-5 py-2">État</th>
                        <th class="px-5 py-2">Capteur</th>
                        <th class="px-5 py-2">Valeur</th>
                        <th class="px-5 py-2">Message</th>
                        <th class="w-48 px-5 py-2">2 jours</th>
                        <th class="px-5 py-2">Contrôlé</th>
                        <th class="px-5 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite-100">
                    @forelse($device->sensors as $sensor)
                        <tr>
                            <td class="px-5 py-3">@include('intranet.monitor.partials.badge', ['status' => $sensor->displayStatus()])</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="font-medium text-graphite-900 hover:text-nere-700">{{ $sensor->name }}</a>
                                <p class="text-xs text-graphite-500">{{ $sensor->kind->label() }} · {{ $sensor->target() }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 font-semibold text-graphite-900">{{ $sensor->formatValue() }}</td>
                            <td class="px-5 py-3 text-graphite-700">{{ $sensor->last_message ?? 'Pas encore contrôlé' }}</td>
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
                        <tr><td colspan="7" class="px-5 py-4 text-graphite-600">Aucun capteur. Ajoutez-en un ci-dessous.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
