@php
    $breadcrumbs = [['label' => 'Supervision réseau']];
@endphp

<x-intranet-layout title="Supervision réseau" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        title="Supervision réseau"
        description="Caméras, imprimantes, serveurs et antennes, vérifiés chaque minute par le serveur de l'intranet." />


    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Filtrer par état">
            @foreach(\App\Enums\MonitorStatus::cases() as $status)
                <a href="{{ route('intranet.monitor.index', $filter === $status ? [] : ['etat' => $status->value]) }}"
                   @if($filter === $status) aria-current="true" @endif
                   class="flex min-w-28 items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-semibold {{ $status->color() }} {{ $filter && $filter !== $status ? 'opacity-40' : '' }}">
                    <span>{{ $status->label() }}</span>
                    <span class="text-lg tabular-nums">{{ $counts[$status->value] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="flex items-center gap-2" data-auto-refresh data-interval="60000">
            <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
            <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
        </div>
    </div>

    <section class="carte mb-6 overflow-hidden" aria-labelledby="alertes">
        <h2 id="alertes" class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">Alertes en cours ({{ $alerts->count() }})</h2>
        @forelse($alerts as $sensor)
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-graphite-100 px-5 py-3 text-sm last:border-0">
                @include('intranet.monitor.partials.badge', ['status' => $sensor->displayStatus()])
                <div class="min-w-0 flex-1">
                    <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="font-medium text-graphite-900 hover:text-nere-700">{{ $sensor->device->name }} · {{ $sensor->name }}</a>
                    <span class="text-graphite-500">{{ $sensor->device->host }} · {{ $sensor->device->site }}</span>
                    <p class="text-graphite-600">{{ $sensor->last_message }} · depuis {{ $sensor->status_changed_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</p>
                </div>
                @if($sensor->acknowledged_at)
                    <p class="text-graphite-600">Pris en compte par {{ $sensor->acknowledger?->name }} {{ $sensor->acknowledged_at->diffForHumans() }}@if($sensor->acknowledgement) : « {{ $sensor->acknowledgement }} »@endif</p>
                @elseif($sensor->status === \App\Enums\MonitorStatus::Down)
                    <form method="POST" action="{{ route('intranet.monitor.sensors.acknowledge', $sensor) }}" class="flex gap-2">
                        @csrf
                        <input name="acknowledgement" maxlength="255" placeholder="Note (facultatif)" class="champ w-48" aria-label="Note de prise en compte">
                        <button class="bouton-secondaire whitespace-nowrap">Je m’en occupe</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="px-5 py-4 text-sm text-graphite-600">Aucune alerte : tous les capteurs actifs répondent.</p>
        @endforelse
    </section>

    @forelse($sites as $site => $devices)
        @php $siteStatus = \App\Enums\MonitorStatus::worst($devices->map->status()); @endphp
        <section class="carte mb-4 overflow-hidden" aria-label="Site {{ $site }}">
            <h2 class="flex items-center gap-3 border-b border-graphite-100 bg-graphite-50 px-5 py-3 font-semibold text-graphite-900">
                @include('intranet.monitor.partials.badge', ['status' => $siteStatus])
                {{ $site }}
                <span class="text-sm font-normal text-graphite-500">{{ $devices->count() }} équipement(s)</span>
            </h2>
            <ul class="divide-y divide-graphite-100">
                @foreach($devices as $device)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3">
                        <div class="w-64 min-w-0">
                            <a href="{{ route('intranet.monitor.devices.show', $device) }}" class="flex items-center gap-2 font-medium text-graphite-900 hover:text-nere-700">
                                <span class="h-3 w-3 shrink-0 rounded-full {{ $device->status()->color() }}" aria-hidden="true"></span>
                                <span class="truncate">{{ $device->name }}</span>
                            </a>
                            <p class="truncate pl-5 text-xs text-graphite-500">{{ $device->type->label() }} · {{ $device->host }}</p>
                        </div>
                        <div class="flex flex-1 flex-wrap gap-1.5">
                            @foreach($device->sensors as $sensor)
                                <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" title="{{ $sensor->last_message ?? 'Pas encore vérifié' }}"
                                   class="rounded px-2 py-1 text-xs font-medium {{ $sensor->displayStatus()->color() }}">
                                    {{ $sensor->name }}@if($sensor->last_ms !== null && $sensor->displayStatus() !== \App\Enums\MonitorStatus::Paused) · {{ $sensor->last_ms }} ms @endif
                                </a>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="carte mb-6 p-5 text-sm text-graphite-600">
            {{ $filter ? 'Aucun équipement dans cet état.' : 'Aucun équipement supervisé pour l’instant. Ajoutez le premier ci-dessous.' }}
        </p>
    @endforelse

    <details class="carte mt-6 p-5" @if($errors->any() || $sites->isEmpty()) open @endif>
        <summary class="cursor-pointer font-semibold text-graphite-900">Ajouter un équipement</summary>
        <p class="mt-2 text-sm text-graphite-600">Les capteurs de base sont créés automatiquement : ping pour tous, flux vidéo et page web pour une caméra, port d’impression pour une imprimante, page web pour une antenne.</p>
        <form method="POST" action="{{ route('intranet.monitor.devices.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <label class="block">
                <span class="etiquette">Nom</span>
                <input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Caméra portail nord" class="champ">
                @error('name')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="etiquette">Adresse IP ou nom</span>
                <input name="host" value="{{ old('host') }}" required maxlength="253" placeholder="192.168.10.50" class="champ">
                @error('host')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="etiquette">Type</span>
                <select name="type" required class="champ">
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="etiquette">Site</span>
                <input name="site" value="{{ old('site') }}" required maxlength="100" list="monitor-sites" placeholder="Bureau Ouagadougou" class="champ">
                <datalist id="monitor-sites">
                    @foreach($siteNames as $name)
                        <option value="{{ $name }}">
                    @endforeach
                </datalist>
                @error('site')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <div class="sm:col-span-2 lg:col-span-4">
                <button class="bouton-primaire">Ajouter l’équipement</button>
            </div>
        </form>
    </details>
</x-intranet-layout>
