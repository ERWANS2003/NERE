@php
    $breadcrumbs = [
        ['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')],
        ['label' => $sensor->device->name, 'url' => route('intranet.monitor.devices.show', $sensor->device)],
        ['label' => $sensor->name],
    ];
    $status = $sensor->displayStatus();
@endphp

<x-intranet-layout :title="$sensor->device->name.' · '.$sensor->name" :breadcrumbs="$breadcrumbs">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-graphite-900">{{ $sensor->name }}</h1>
                @include('intranet.monitor.partials.badge', ['status' => $status])
            </div>
            <p class="mt-1 text-graphite-600">{{ $sensor->device->name }} · {{ $sensor->kind->label() }} · {{ $sensor->target() }}</p>
            <p class="mt-1 text-sm text-graphite-600">
                {{ $sensor->last_message ?? 'Pas encore vérifié' }}
                @if($sensor->checked_at) · vérifié {{ $sensor->checked_at->diffForHumans() }} @endif
                @if($sensor->status_changed_at) · dans cet état depuis {{ $sensor->status_changed_at->format('d/m/Y H:i') }} @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2" data-auto-refresh data-interval="60000">
            <form method="POST" action="{{ route('intranet.monitor.sensors.check', $sensor) }}">
                @csrf
                <button class="bouton-primaire">Vérifier maintenant</button>
            </form>
            <form method="POST" action="{{ route('intranet.monitor.sensors.pause', $sensor) }}">
                @csrf
                <button class="bouton-secondaire">{{ $sensor->paused ? 'Reprendre' : 'Mettre en pause' }}</button>
            </form>
            <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
            <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
        </div>
    </div>


    @if($sensor->status === \App\Enums\MonitorStatus::Down)
        <section class="carte mb-6 border-red-200 p-5">
            @if($sensor->acknowledged_at)
                <p class="text-sm text-graphite-700">Pris en compte par <strong>{{ $sensor->acknowledger?->name }}</strong> le {{ $sensor->acknowledged_at->format('d/m/Y à H:i') }}@if($sensor->acknowledgement) : « {{ $sensor->acknowledgement }} »@endif</p>
            @else
                <form method="POST" action="{{ route('intranet.monitor.sensors.acknowledge', $sensor) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <label class="block min-w-64 flex-1">
                        <span class="etiquette">Prendre en compte la panne</span>
                        <input name="acknowledgement" maxlength="255" placeholder="Ex. : technicien en route, coupure électrique du site" class="champ">
                    </label>
                    <button class="bouton-primaire">Je m’en occupe</button>
                </form>
            @endif
        </section>
    @endif

    <section class="carte mb-6 p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-graphite-900">Temps de réponse</h2>
            <nav class="flex gap-1" aria-label="Période">
                @foreach($periods as $key => $info)
                    <a href="{{ route('intranet.monitor.sensors.show', [$sensor, 'periode' => $key]) }}" @if($key === $period) aria-current="page" @endif
                       class="rounded-md px-3 py-1.5 text-sm font-medium {{ $key === $period ? 'bg-nere-700 text-white' : 'text-graphite-700 hover:bg-graphite-100' }}">{{ $info['label'] }}</a>
                @endforeach
            </nav>
        </div>
        <dl class="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div><dt class="text-graphite-500">Disponibilité</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['uptime'] === null ? '-' : number_format($series['uptime'], 2, ',', ' ').' %' }}</dd></div>
            <div><dt class="text-graphite-500">Temps moyen</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['avg_ms'] === null ? '-' : $series['avg_ms'].' ms' }}</dd></div>
            <div><dt class="text-graphite-500">Temps maximum</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['max_ms'] === null ? '-' : $series['max_ms'].' ms' }}</dd></div>
            <div><dt class="text-graphite-500">Mesures</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['samples'] }}</dd></div>
        </dl>
        @if($series['points'])
            @include('intranet.monitor.partials.chart', ['points' => $series['points']])
        @else
            <p class="text-sm text-graphite-600">Pas encore de mesures sur cette période. Au-delà de 24 heures, l’historique est construit heure par heure.</p>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="carte overflow-hidden">
            <h2 class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">Historique des changements d’état</h2>
            <ul class="divide-y divide-graphite-100 text-sm">
                @forelse($events as $event)
                    <li class="flex flex-wrap items-center gap-2 px-5 py-2.5">
                        <span class="w-32 text-graphite-500">{{ $event->created_at->format('d/m/Y H:i') }}</span>
                        @include('intranet.monitor.partials.badge', ['status' => $event->to_status])
                        <span class="text-graphite-700">{{ $event->message }}</span>
                    </li>
                @empty
                    <li class="px-5 py-3 text-graphite-600">Aucun changement d’état enregistré.</li>
                @endforelse
            </ul>
        </section>

        <details class="carte p-5" @if($errors->any()) open @endif>
            <summary class="cursor-pointer font-semibold text-graphite-900">Paramètres du capteur</summary>
            <form method="POST" action="{{ route('intranet.monitor.sensors.update', $sensor) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="etiquette">Nom</span>
                    <input name="name" value="{{ old('name', $sensor->name) }}" required maxlength="120" class="champ">
                </label>
                @if($sensor->kind !== \App\Enums\MonitorSensorKind::Ping)
                    <label class="block">
                        <span class="etiquette">Port</span>
                        <input name="port" type="number" min="1" max="65535" value="{{ old('port', $sensor->port) }}" required class="champ">
                        @error('port')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                @endif
                @if($sensor->kind === \App\Enums\MonitorSensorKind::Http)
                    <label class="block">
                        <span class="etiquette">Chemin de la page</span>
                        <input name="path" value="{{ old('path', $sensor->path) }}" maxlength="255" class="champ">
                        @error('path')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                @endif
                <label class="block">
                    <span class="etiquette">Avertir au-delà de (ms)</span>
                    <input name="warning_ms" type="number" min="1" max="60000" value="{{ old('warning_ms', $sensor->warning_ms) }}" class="champ">
                    @error('warning_ms')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <p class="text-xs text-graphite-500 sm:col-span-2">Le capteur passe en panne après {{ \App\Models\MonitorSensor::DOWN_AFTER }} échecs de suite ; l’équipe IT est alors prévenue, puis de nouveau quand il revient.</p>
                <div class="sm:col-span-2">
                    <button class="bouton-primaire">Enregistrer</button>
                </div>
            </form>
            <form method="POST" action="{{ route('intranet.monitor.sensors.destroy', $sensor) }}" class="mt-6 border-t border-graphite-100 pt-4"
                  onsubmit="return confirm('Supprimer ce capteur et son historique ?')">
                @csrf
                @method('DELETE')
                <button class="bouton-danger">Supprimer le capteur</button>
            </form>
        </details>
    </div>
</x-intranet-layout>
