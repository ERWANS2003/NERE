@php
    $breadcrumbs = [['label' => 'Supervision réseau']];
@endphp

<x-intranet-layout title="Supervision réseau" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        title="Supervision réseau"
        description="Caméras, imprimantes, serveurs, antennes et équipements réseau, contrôlés en continu par le serveur de l'intranet." />

    @include('intranet.monitor.partials.nav')

    @error('group')<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $message }}</div>@enderror

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
            <a href="{{ route('intranet.monitor.groups.create') }}" class="bouton-secondaire">Nouveau groupe</a>
            @if($groups->isNotEmpty())
                <a href="{{ route('intranet.monitor.devices.create') }}" class="bouton-primaire">Ajouter un équipement</a>
            @endif
            <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
            <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
        </div>
    </div>

    <section class="carte mb-6 overflow-hidden" aria-labelledby="alarmes">
        <h2 id="alarmes" class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">Alarmes ({{ $alarms->count() }})</h2>
        @forelse($alarms as $sensor)
            @php $state = $sensor->displayStatus(); @endphp
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-graphite-100 px-5 py-3 text-sm last:border-0">
                <span class="w-44 shrink-0">@include('intranet.monitor.partials.badge', ['status' => $state])</span>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="font-medium text-graphite-900 hover:text-nere-700">{{ $sensor->device->name }} · {{ $sensor->name }}</a>
                    <span class="text-graphite-500">{{ $sensor->device->host }}</span>
                    <p class="text-graphite-600">{{ $sensor->last_message }} · depuis {{ $sensor->status_changed_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</p>
                    @if($state === \App\Enums\MonitorStatus::DownAcknowledged)
                        <p class="text-graphite-600">Pris en compte par {{ $sensor->acknowledger?->name }}@if($sensor->acknowledged_until) jusqu’à {{ $sensor->acknowledged_until->format('H:i') }}@endif @if($sensor->acknowledgement) : « {{ $sensor->acknowledgement }} »@endif</p>
                    @endif
                </div>
                @if($state === \App\Enums\MonitorStatus::Down)
                    <form method="POST" action="{{ route('intranet.monitor.sensors.acknowledge', $sensor) }}" class="flex flex-wrap gap-2">
                        @csrf
                        <input name="acknowledgement" maxlength="255" placeholder="Note (facultatif)" class="champ w-44" aria-label="Note de prise en compte">
                        <select name="duration" class="champ w-40" aria-label="Durée de la prise en compte">
                            @foreach($durations as $value => $label)
                                <option value="{{ $value }}" @selected($value === '0')>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="bouton-secondaire whitespace-nowrap">Je m’en occupe</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="px-5 py-4 text-sm text-graphite-600">Aucune alarme : tous les capteurs actifs répondent normalement.</p>
        @endforelse
    </section>

    @forelse($groups->whereNull('parent_id') as $group)
        @include('intranet.monitor.partials.group-tree', ['group' => $group, 'depth' => 0])
    @empty
        <div class="carte p-6 text-sm text-graphite-700">
            <p class="font-semibold text-graphite-900">Commencez par créer un groupe</p>
            <p class="mt-1">Un groupe correspond à un site ou une zone (ex. « Siège Ouagadougou », « Mine »). Vous y ajoutez ensuite les équipements, à la main ou avec la découverte automatique.</p>
            <a href="{{ route('intranet.monitor.groups.create') }}" class="bouton-primaire mt-4 inline-flex">Créer le premier groupe</a>
        </div>
    @endforelse
</x-intranet-layout>
