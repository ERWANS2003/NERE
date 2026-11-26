@php
    $breadcrumbs = [
        ['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')],
        ['label' => $device->name],
    ];
@endphp

<x-intranet-layout :title="$device->name" :breadcrumbs="$breadcrumbs">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-graphite-900">{{ $device->name }}</h1>
                @include('intranet.monitor.partials.badge', ['status' => $device->status()])
            </div>
            <p class="mt-1 text-graphite-600">{{ $device->type->label() }} · {{ $device->host }} · {{ $device->site }}</p>
        </div>
        <div class="flex flex-wrap gap-2" data-auto-refresh data-interval="60000">
            <form method="POST" action="{{ route('intranet.monitor.devices.pause', $device) }}">
                @csrf
                <button class="bouton-secondaire">{{ $device->paused ? 'Reprendre la supervision' : 'Mettre en pause' }}</button>
            </form>
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
                        <th class="px-5 py-2">Dernière valeur</th>
                        <th class="w-56 px-5 py-2">24 heures</th>
                        <th class="px-5 py-2">Vérifié</th>
                        <th class="px-5 py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite-100">
                    @foreach($device->sensors as $sensor)
                        <tr>
                            <td class="px-5 py-3">@include('intranet.monitor.partials.badge', ['status' => $sensor->displayStatus()])</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="font-medium text-graphite-900 hover:text-nere-700">{{ $sensor->name }}</a>
                                <p class="text-xs text-graphite-500">{{ $sensor->kind->label() }} · {{ $sensor->target() }}</p>
                            </td>
                            <td class="px-5 py-3 text-graphite-700">{{ $sensor->last_message ?? 'Pas encore vérifié' }}</td>
                            <td class="px-5 py-3">
                                @include('intranet.monitor.partials.chart', ['points' => $sparklines[$sensor->id]['points'], 'height' => 32, 'compact' => true, 'label' => 'Temps de réponse sur 24 heures'])
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-graphite-500">{{ $sensor->checked_at?->diffForHumans() ?? 'jamais' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <form method="POST" action="{{ route('intranet.monitor.sensors.check', $sensor) }}" class="inline">
                                    @csrf
                                    <button class="font-medium text-nere-700 hover:underline">Vérifier maintenant</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <details class="carte p-5" @if($errors->hasAny(['kind', 'port', 'path', 'warning_ms'])) open @endif>
            <summary class="cursor-pointer font-semibold text-graphite-900">Ajouter un capteur</summary>
            <form method="POST" action="{{ route('intranet.monitor.sensors.store', $device) }}" class="mt-4 grid gap-4 sm:grid-cols-2" x-data="{ kind: @js(old('kind', 'tcp')) }">
                @csrf
                <label class="block">
                    <span class="etiquette">Type de contrôle</span>
                    <select name="kind" x-model="kind" class="champ">
                        @foreach($kinds as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="etiquette">Nom</span>
                    <input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Bureau à distance" class="champ">
                </label>
                <label class="block" x-show="kind !== 'ping'">
                    <span class="etiquette">Port</span>
                    <input name="port" type="number" min="1" max="65535" value="{{ old('port') }}" placeholder="3389" class="champ" :disabled="kind === 'ping'">
                    @error('port')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block" x-show="kind === 'http'" x-cloak>
                    <span class="etiquette">Chemin de la page</span>
                    <input name="path" value="{{ old('path', '/') }}" maxlength="255" class="champ" :disabled="kind !== 'http'">
                    @error('path')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block">
                    <span class="etiquette">Avertir au-delà de (ms, facultatif)</span>
                    <input name="warning_ms" type="number" min="1" max="60000" value="{{ old('warning_ms') }}" placeholder="200" class="champ">
                    @error('warning_ms')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <div class="sm:col-span-2">
                    <p class="mb-3 text-xs text-graphite-500">Ports courants : 22 SSH, 80 web, 443 web sécurisé, 554 vidéo RTSP, 3389 bureau à distance, 5432 PostgreSQL, 9100 impression.</p>
                    <button class="bouton-primaire">Ajouter le capteur</button>
                </div>
            </form>
        </details>

        <details class="carte p-5" @if($errors->hasAny(['name', 'host', 'type', 'site'])) open @endif>
            <summary class="cursor-pointer font-semibold text-graphite-900">Modifier l’équipement</summary>
            <form method="POST" action="{{ route('intranet.monitor.devices.update', $device) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="etiquette">Nom</span>
                    <input name="name" value="{{ old('name', $device->name) }}" required maxlength="120" class="champ">
                </label>
                <label class="block">
                    <span class="etiquette">Adresse IP ou nom</span>
                    <input name="host" value="{{ old('host', $device->host) }}" required maxlength="253" class="champ">
                    @error('host')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block">
                    <span class="etiquette">Type</span>
                    <select name="type" class="champ">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected($device->type->value === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="etiquette">Site</span>
                    <input name="site" value="{{ old('site', $device->site) }}" required maxlength="100" class="champ">
                </label>
                <div class="sm:col-span-2">
                    <button class="bouton-primaire">Enregistrer</button>
                </div>
            </form>
            <form method="POST" action="{{ route('intranet.monitor.devices.destroy', $device) }}" class="mt-6 border-t border-graphite-100 pt-4"
                  onsubmit="return confirm('Retirer cet équipement et tout son historique de la supervision ?')">
                @csrf
                @method('DELETE')
                <button class="bouton-danger">Retirer l’équipement</button>
            </form>
        </details>
    </div>
</x-intranet-layout>
