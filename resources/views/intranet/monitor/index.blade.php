@php
    $breadcrumbs = [['label' => 'Supervision réseau']];
@endphp

<x-intranet-layout title="Supervision réseau" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        title="Supervision réseau"
        description="Caméras, imprimantes, serveurs, antennes et équipements réseau, contrôlés en continu par le serveur de l'intranet." />

    @include('intranet.monitor.partials.nav')

    @if($lastCheck && $lastCheck->lt(now()->subMinutes(5)))
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">Les contrôles ne tournent plus depuis {{ $lastCheck->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}.</p>
            <p class="mt-1">Les états affichés ne sont plus à jour. Vérifiez la tâche planifiée « NERE - Laravel scheduler » sur le serveur.</p>
        </div>
    @endif

    @error('group')<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $message }}</div>@enderror

    @php
        $total = $counts->sum();
        $alarmCount = $alarms->count();
        $downCount = $counts['down'] + $counts['down_ack'];
    @endphp

    {{-- Chiffres clés --}}
    <section class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Chiffres clés">
        <div class="carte p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-graphite-500">Équipements surveillés</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-graphite-900">{{ $deviceCount }}</p>
            <p class="mt-1 text-xs text-graphite-500">{{ $total }} capteurs, {{ $groups->count() }} groupe(s)</p>
        </div>
        <div class="carte p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-graphite-500">Disponibilité sur 24 h</p>
            <p class="mt-1 text-3xl font-bold tabular-nums {{ $availability !== null && $availability < 99 ? 'text-red-700' : 'text-graphite-900' }}">{{ $availability === null ? '-' : number_format($availability, 2, ',', ' ').' %' }}</p>
            <p class="mt-1 text-xs text-graphite-500">moyenne des pings de tous les équipements</p>
        </div>
        <a href="#alarmes" class="carte block p-4 {{ $downCount ? 'ring-2 ring-red-200' : '' }}">
            <p class="text-xs font-semibold uppercase tracking-wide text-graphite-500">Alarmes en cours</p>
            <p class="mt-1 text-3xl font-bold tabular-nums {{ $downCount ? 'text-red-700' : ($alarmCount ? 'text-amber-700' : 'text-green-700') }}">{{ $alarmCount }}</p>
            <p class="mt-1 text-xs text-graphite-500">{{ $downCount }} en panne · {{ $alarmCount - $downCount }} à surveiller</p>
        </a>
        <div class="carte p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-graphite-500">Dernier contrôle</p>
            <p class="mt-1 text-3xl font-bold text-graphite-900">{{ $lastCheck ? $lastCheck->format('H:i') : '-' }}</p>
            <p class="mt-1 text-xs {{ $lastCheck && $lastCheck->lt(now()->subMinutes(5)) ? 'font-semibold text-red-700' : 'text-graphite-500' }}">{{ $lastCheck ? $lastCheck->diffForHumans() : 'aucun contrôle pour l’instant' }}</p>
        </div>
    </section>

    {{-- Barre d'état proportionnelle et filtres --}}
    <section class="carte mb-6 p-4" aria-label="Répartition des capteurs par état">
        @if($total > 0)
            <div class="mb-3 flex h-3 overflow-hidden rounded-full bg-graphite-100" aria-hidden="true">
                @foreach(\App\Enums\MonitorStatus::cases() as $status)
                    @if($counts[$status->value] > 0)
                        <div class="{{ $status->dot() }}" style="width: {{ 100 * $counts[$status->value] / $total }}%"></div>
                    @endif
                @endforeach
            </div>
        @endif
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex flex-wrap gap-1.5" aria-label="Filtrer par état">
                @foreach(\App\Enums\MonitorStatus::cases() as $status)
                    @continue($counts[$status->value] === 0 && $filter !== $status)
                    <a href="{{ route('intranet.monitor.index', $filter === $status ? [] : ['etat' => $status->value]) }}"
                       @if($filter === $status) aria-current="true" @endif
                       title="Cliquez pour n’afficher que les équipements « {{ $status->label() }} »"
                       class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium {{ $filter === $status ? $status->color() : $status->soft() }} {{ $filter && $filter !== $status ? 'opacity-50' : '' }}">
                        <span class="h-2.5 w-2.5 rounded-full {{ $filter === $status ? 'bg-white/80' : $status->dot() }}"></span>
                        {{ $status->label() }}
                        <span class="font-bold tabular-nums">{{ $counts[$status->value] }}</span>
                    </a>
                @endforeach
                @if($filter)
                    <a href="{{ route('intranet.monitor.index') }}" class="rounded-full px-3 py-1.5 text-sm font-medium text-nere-700 hover:underline">Tout afficher</a>
                @endif
            </nav>
            <div class="flex flex-wrap items-center gap-2" data-auto-refresh data-interval="60000">
                <a href="{{ route('intranet.monitor.groups.create') }}" class="bouton-secondaire">Nouveau groupe</a>
                @if($groups->isNotEmpty())
                    <a href="{{ route('intranet.monitor.devices.create') }}" class="bouton-primaire">Ajouter un équipement</a>
                @endif
                <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
                <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
            </div>
        </div>
    </section>

    {{-- Alarmes --}}
    <section class="carte mb-6 overflow-hidden" aria-labelledby="alarmes" x-data="{ all: false }">
        <div class="flex items-center justify-between border-b border-graphite-100 px-5 py-3">
            <h2 id="alarmes" class="font-semibold text-graphite-900">Alarmes en cours</h2>
            @if($alarmCount)<span class="rounded-full {{ $downCount ? 'bg-red-600 text-white' : 'bg-amber-400 text-graphite-950' }} px-2.5 py-0.5 text-xs font-bold">{{ $alarmCount }}</span>@endif
        </div>
        @forelse($alarms as $index => $sensor)
            @php $state = $sensor->displayStatus(); @endphp
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-l-4 border-b-graphite-100 {{ $state->accent() }} px-5 py-3 text-sm last:border-b-0"
                 @if($index >= 8) x-show="all" x-cloak @endif>
                @include('intranet.monitor.partials.device-icon', ['type' => $sensor->device->type, 'status' => $state, 'size' => 'sm'])
                <div class="min-w-0 flex-1">
                    <p>
                        <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="font-semibold text-graphite-900 hover:text-nere-700">{{ $sensor->device->name }}</a>
                        <span class="text-graphite-600">· {{ $sensor->name }}</span>
                        <span class="ml-1 rounded px-1.5 py-0.5 text-[11px] font-semibold {{ $state->soft() }}">{{ $state->label() }}</span>
                    </p>
                    <p class="truncate text-graphite-600">{{ $sensor->last_message }}</p>
                    @if($state === \App\Enums\MonitorStatus::DownAcknowledged)
                        <p class="text-xs text-graphite-500">Pris en compte par {{ $sensor->acknowledger?->name }}@if($sensor->acknowledged_until) jusqu’à {{ $sensor->acknowledged_until->format('H:i') }}@endif @if($sensor->acknowledgement) : « {{ $sensor->acknowledgement }} »@endif</p>
                    @endif
                </div>
                <span class="w-28 shrink-0 text-right text-xs text-graphite-500" title="{{ $sensor->status_changed_at?->format('d/m/Y H:i') }}">depuis {{ $sensor->status_changed_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                @if($state === \App\Enums\MonitorStatus::Down)
                    @include('intranet.monitor.partials.acknowledge', ['sensor' => $sensor])
                @endif
            </div>
        @empty
            <div class="flex items-center gap-3 px-5 py-6 text-sm text-green-800">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Aucune alarme : tous les capteurs actifs répondent normalement.
            </div>
        @endforelse
        @if($alarmCount > 8)
            <button type="button" @click="all = ! all" class="w-full border-t border-graphite-100 px-5 py-2.5 text-sm font-medium text-nere-700 hover:bg-graphite-50"
                    x-text="all ? 'Réduire la liste' : 'Voir les {{ $alarmCount - 8 }} autres alarmes'"></button>
        @endif
    </section>

    <details class="mb-6 text-sm text-graphite-700">
        <summary class="cursor-pointer font-medium text-nere-700">Que signifient les couleurs ?</summary>
        <dl class="carte mt-2 grid gap-x-6 gap-y-2 p-4 sm:grid-cols-2">
            @foreach([
                ['down', 'Le capteur a échoué 2 fois de suite, ou une valeur a franchi un seuil de panne. L’équipe IT est prévenue.'],
                ['down_ack', 'En panne, mais un technicien a cliqué « Je m’en occupe » : plus de rappel ni d’escalade.'],
                ['warning', 'Premier échec (nouvel essai en cours) ou valeur au-delà d’un seuil d’avertissement.'],
                ['unusual', 'Répond, mais bien plus lentement que d’habitude à ce jour et cette heure.'],
                ['up', 'Tout répond normalement.'],
                ['paused', 'Contrôles suspendus : pause manuelle, maintenance, ou équipement dont il dépend injoignable.'],
                ['unknown', 'Pas encore contrôlé.'],
            ] as [$value, $text])
                <div class="flex items-start gap-2">
                    <dt class="w-48 shrink-0">@include('intranet.monitor.partials.badge', ['status' => \App\Enums\MonitorStatus::from($value)])</dt>
                    <dd>{{ $text }}</dd>
                </div>
            @endforeach
        </dl>
    </details>

    @if($groups->isNotEmpty())
        <div x-data="{ q: '', norm(s) { return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim() }, matches(el) { return ! this.q || [...el.querySelectorAll('[data-search]')].some(li => li.dataset.search.includes(this.norm(this.q))) } }">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <label class="relative block w-full max-w-md">
                    <span class="sr-only">Rechercher un équipement</span>
                    <input type="search" x-model="q" placeholder="Rechercher un équipement (nom, adresse IP, type)…" class="champ">
                </label>
                <span class="text-sm text-graphite-500" x-show="q" x-cloak>Les groupes sont dépliés pendant la recherche.</span>
            </div>
            @foreach($groups->whereNull('parent_id') as $group)
                @include('intranet.monitor.partials.group-tree', ['group' => $group, 'depth' => 0])
            @endforeach
            <p class="carte p-5 text-sm text-graphite-600" x-show="q && ! matches($root)" x-cloak>Aucun équipement ne correspond à « <span x-text="q"></span> ».</p>
        </div>
    @endif

    @if($groups->isEmpty())
        <div class="carte p-6 text-sm text-graphite-700">
            <p class="font-semibold text-graphite-900">Commencez par créer un groupe</p>
            <p class="mt-1">Un groupe correspond à un site ou une zone (ex. « Siège Ouagadougou », « Mine »). Vous y ajoutez ensuite les équipements, à la main ou avec la découverte automatique.</p>
            <a href="{{ route('intranet.monitor.groups.create') }}" class="bouton-primaire mt-4 inline-flex">Créer le premier groupe</a>
        </div>
    @endif
</x-intranet-layout>
