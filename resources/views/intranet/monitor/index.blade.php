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
    @elseif($lastCheck)
        <p class="mb-4 text-xs text-graphite-500">Dernier contrôle {{ $lastCheck->diffForHumans() }}.</p>
    @endif

    @error('group')<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $message }}</div>@enderror

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Filtrer par état">
            @foreach(\App\Enums\MonitorStatus::cases() as $status)
                <a href="{{ route('intranet.monitor.index', $filter === $status ? [] : ['etat' => $status->value]) }}"
                   @if($filter === $status) aria-current="true" @endif
                   title="{{ $counts[$status->value] }} capteur(s) : {{ $status->label() }}. Cliquez pour n’afficher que ceux-là."
                   class="flex min-w-28 items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-semibold {{ $status->color() }} {{ ($filter && $filter !== $status) || (! $filter && $counts[$status->value] === 0) ? 'opacity-35' : '' }}">
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
                    @include('intranet.monitor.partials.acknowledge', ['sensor' => $sensor])
                @endif
            </div>
        @empty
            <p class="px-5 py-4 text-sm text-graphite-600">Aucune alarme : tous les capteurs actifs répondent normalement.</p>
        @endforelse
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
