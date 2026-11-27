@php
    $device = $sensor->device;
    $breadcrumbs = [
        ['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')],
        ['label' => $device->name, 'url' => route('intranet.monitor.devices.show', $device)],
        ['label' => $sensor->name],
    ];
    $status = $sensor->displayStatus();
    $unit = $sensor->unitLabel();
@endphp

<x-intranet-layout :title="$device->name.' · '.$sensor->name" :breadcrumbs="$breadcrumbs">
    @include('intranet.monitor.partials.nav')

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-graphite-900">{{ $sensor->name }}</h1>
                @include('intranet.monitor.partials.badge', ['status' => $status])
                <span class="text-2xl font-semibold text-graphite-900">{{ $sensor->formatValue() }}</span>
            </div>
            <p class="mt-1 text-graphite-600">{{ $device->name }} · {{ $sensor->kind->label() }} · {{ $sensor->target() }}</p>
            <p class="mt-1 text-sm text-graphite-600">
                {{ $sensor->last_message ?? 'Pas encore contrôlé' }}
                @if($sensor->checked_at) · contrôlé {{ $sensor->checked_at->diffForHumans() }}, toutes les {{ $sensor->interval() }} s @endif
                @if($sensor->status_changed_at) · dans cet état depuis le {{ $sensor->status_changed_at->format('d/m/Y à H:i') }} @endif
            </p>
            @if($sensor->paused && $sensor->isPaused())
                <p class="mt-1 text-sm text-blue-800">En pause{{ $sensor->paused_until ? ' jusqu’au '.$sensor->paused_until->format('d/m/Y à H:i') : '' }}{{ $sensor->pause_reason ? ' : '.$sensor->pause_reason : '' }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2" data-auto-refresh data-interval="60000">
            @can('monitor.manage')
                <form method="POST" action="{{ route('intranet.monitor.sensors.check', $sensor) }}">
                    @csrf
                    <button class="bouton-primaire">Vérifier maintenant</button>
                </form>
                @include('intranet.monitor.partials.pause-form', ['paused' => $sensor->paused && $sensor->isPaused(), 'action' => route('intranet.monitor.sensors.pause', $sensor)])
            @endcan
            @if($sensor->ticket)
                <a href="{{ route('intranet.tickets.show', $sensor->ticket) }}" class="bouton-secondaire">Ticket {{ $sensor->ticket->number }}</a>
            @elseif($status->isAlarm() && auth()->user()->can('monitor.manage'))
                <form method="POST" action="{{ route('intranet.monitor.sensors.ticket', $sensor) }}">
                    @csrf
                    <button class="bouton-secondaire">Ouvrir un ticket</button>
                </form>
            @endif
            <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
            <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
        </div>
    </div>

    @if(in_array($sensor->status, [\App\Enums\MonitorStatus::Down, \App\Enums\MonitorStatus::DownAcknowledged], true))
        <section class="carte mb-6 p-5">
            @if($sensor->status === \App\Enums\MonitorStatus::DownAcknowledged)
                <p class="text-sm text-graphite-700">Pris en compte par <strong>{{ $sensor->acknowledger?->name }}</strong> le {{ $sensor->acknowledged_at?->format('d/m/Y à H:i') }}@if($sensor->acknowledged_until), jusqu’au {{ $sensor->acknowledged_until->format('d/m/Y à H:i') }}@endif @if($sensor->acknowledgement) : « {{ $sensor->acknowledgement }} »@endif. Plus de rappel ni d’escalade tant que la panne dure.</p>
            @elseif(! auth()->user()->can('monitor.manage'))
                <p class="text-sm text-graphite-700">Panne pas encore prise en compte par l’équipe.</p>
            @else
                <form method="POST" action="{{ route('intranet.monitor.sensors.acknowledge', $sensor) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <label class="block min-w-64 flex-1">
                        <span class="etiquette">Prendre en compte la panne</span>
                        <input name="acknowledgement" maxlength="255" placeholder="Ex. : technicien en route, coupure électrique du site" class="champ">
                    </label>
                    <label class="block">
                        <span class="etiquette">Pendant</span>
                        <select name="duration" class="champ">
                            @foreach($durations as $value => $label)
                                <option value="{{ $value }}" @selected((string) $value === '0')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="bouton-primaire">Je m’en occupe</button>
                </form>
            @endif
        </section>
    @endif

    <section class="carte mb-6 p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-graphite-900">Historique{{ $unit ? ' ('.$unit.')' : '' }}</h2>
            <nav class="flex flex-wrap gap-1" aria-label="Période">
                @foreach($periods as $key => $info)
                    <a href="{{ route('intranet.monitor.sensors.show', [$sensor, 'periode' => $key]) }}" @if($key === $period) aria-current="page" @endif
                       class="rounded-md px-3 py-1.5 text-sm font-medium {{ $key === $period ? 'bg-nere-700 text-white' : 'text-graphite-700 hover:bg-graphite-100' }}">{{ $info['label'] }}</a>
                @endforeach
            </nav>
        </div>
        <dl class="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div><dt class="text-graphite-500">Disponibilité</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['uptime'] === null ? '-' : number_format($series['uptime'], 2, ',', ' ').' %' }}</dd></div>
            <div><dt class="text-graphite-500">Moyenne</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['avg'] === null ? '-' : $sensor->formatValue($series['avg']) }}</dd></div>
            <div><dt class="text-graphite-500">Maximum</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['max'] === null ? '-' : $sensor->formatValue($series['max']) }}</dd></div>
            <div><dt class="text-graphite-500">Mesures</dt><dd class="text-xl font-semibold text-graphite-900">{{ $series['samples'] }}</dd></div>
        </dl>
        @if($series['points'])
            @include('intranet.monitor.partials.chart', ['points' => $series['points'], 'unit' => $unit, 'label' => $sensor->name])
        @else
            <p class="text-sm text-graphite-600">Pas encore de mesures sur cette période. Au-delà de 7 jours, l’historique est construit à partir des moyennes horaires.</p>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="carte overflow-hidden">
            <h2 class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">Journal du capteur</h2>
            <ul class="divide-y divide-graphite-100 text-sm">
                @forelse($events as $event)
                    <li class="flex flex-wrap items-center gap-2 px-5 py-2.5">
                        <span class="w-32 text-graphite-500">{{ $event->created_at->format('d/m/Y H:i') }}</span>
                        @if($event->to_status) @include('intranet.monitor.partials.badge', ['status' => $event->to_status]) @endif
                        <span class="text-graphite-700">{{ $event->message }}@if($event->user) · {{ $event->user->name }}@endif</span>
                    </li>
                @empty
                    <li class="px-5 py-3 text-graphite-600">Aucun événement enregistré.</li>
                @endforelse
            </ul>
        </section>

        @can('monitor.manage')
        <details class="carte p-5" @if($errors->any()) open @endif>
            <summary class="cursor-pointer font-semibold text-graphite-900">Paramètres et seuils</summary>
            <form method="POST" action="{{ route('intranet.monitor.sensors.update', $sensor) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <label class="block">
                    <span class="etiquette">Nom</span>
                    <input name="name" value="{{ old('name', $sensor->name) }}" required maxlength="120" class="champ">
                </label>
                <label class="block">
                    <span class="etiquette">Intervalle (secondes)</span>
                    <input type="number" name="interval_seconds" min="30" value="{{ old('interval_seconds', $sensor->interval_seconds) }}" placeholder="(hérité : {{ $device->setting('interval_seconds') }})" class="champ">
                    @error('interval_seconds')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                @if($sensor->kind->needsPort())
                    <label class="block">
                        <span class="etiquette">Port</span>
                        <input name="port" type="number" min="1" max="65535" value="{{ old('port', $sensor->port) }}" class="champ">
                        @error('port')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                @endif
                @if($sensor->kind->parameterLabel())
                    <label class="block">
                        <span class="etiquette">{{ $sensor->kind->parameterLabel() }}</span>
                        <input name="parameter" value="{{ old('parameter', $sensor->parameter) }}" maxlength="255" class="champ">
                        @error('parameter')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                    </label>
                @endif
                @if($sensor->kind === \App\Enums\MonitorSensorKind::SnmpCustom)
                    <label class="block">
                        <span class="etiquette">Unité</span>
                        <input name="unit" value="{{ old('unit', $sensor->unit) }}" maxlength="20" class="champ">
                    </label>
                @endif
                <fieldset class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                    <legend class="mb-1 font-semibold text-graphite-900 sm:col-span-2">Seuils{{ $unit ? ' (en '.$unit.')' : '' }}</legend>
                    @foreach(['error_above' => 'Panne au-dessus de', 'warn_above' => 'Avertissement au-dessus de', 'warn_below' => 'Avertissement en dessous de', 'error_below' => 'Panne en dessous de'] as $field => $label)
                        <label class="block">
                            <span class="etiquette">{{ $label }}</span>
                            <input type="number" step="any" name="{{ $field }}" value="{{ old($field, $sensor->{$field}) }}" class="champ">
                            @error($field)<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                </fieldset>
                <p class="text-xs text-graphite-500 sm:col-span-2">Sans réponse, le capteur passe en avertissement au premier échec puis en panne au deuxième. Un seuil franchi change l’état aussitôt. L’équipe IT est prévenue selon les réglages du groupe : délai {{ $device->setting('notify_delay_minutes') }} min, répétition {{ $device->setting('notify_repeat_minutes') ?: 'aucune' }}, escalade {{ $device->setting('escalate_after_minutes') ? 'après '.$device->setting('escalate_after_minutes').' min' : 'aucune' }}.</p>
                <div class="sm:col-span-2">
                    <button class="bouton-primaire">Enregistrer</button>
                </div>
            </form>
            <form method="POST" action="{{ route('intranet.monitor.sensors.destroy', $sensor) }}" class="mt-6 border-t border-graphite-100 pt-4" onsubmit="return confirm('Supprimer ce capteur et son historique ?')">
                @csrf
                @method('DELETE')
                <button class="bouton-danger">Supprimer le capteur</button>
            </form>
        </details>
        @endcan
    </div>
</x-intranet-layout>
