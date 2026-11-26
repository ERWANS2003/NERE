@php
    $sensors = $map->items->flatMap(fn ($item) => $item->device->sensors);
    $alarms = $sensors->filter(fn ($s) => $s->displayStatus()->isAlarm())->sortBy(fn ($s) => $s->displayStatus()->rank());
    $counts = $sensors->countBy(fn ($s) => $s->displayStatus()->value);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Écran mural : la page se recharge seule toutes les 30 secondes. --}}
    <meta http-equiv="refresh" content="30">
    <title>{{ $map->name }} · Supervision</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-graphite-950 text-white antialiased">
    <div class="flex min-h-screen flex-col gap-4 p-4 lg:p-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-graphite-400">NÉRÉ Mining · Supervision réseau</p>
                <h1 class="text-2xl font-bold lg:text-3xl">{{ $map->name }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @foreach([\App\Enums\MonitorStatus::Down, \App\Enums\MonitorStatus::DownAcknowledged, \App\Enums\MonitorStatus::Warning, \App\Enums\MonitorStatus::Up, \App\Enums\MonitorStatus::Paused] as $status)
                    <span class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold {{ $status->color() }} {{ ($counts[$status->value] ?? 0) === 0 ? 'opacity-30' : '' }}">
                        {{ $status->label() }} <span class="text-xl tabular-nums">{{ $counts[$status->value] ?? 0 }}</span>
                    </span>
                @endforeach
                <span class="ml-2 text-3xl font-light tabular-nums text-graphite-300">{{ now()->format('H:i') }}</span>
            </div>
        </header>

        <div class="grid flex-1 gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
            @include('intranet.monitor.partials.map-canvas', ['map' => $map, 'dark' => true])
            <aside class="self-start rounded-xl border border-graphite-700 bg-graphite-900" aria-label="Alarmes">
                <h2 class="border-b border-graphite-700 px-4 py-3 font-semibold">Alarmes ({{ $alarms->count() }})</h2>
                <ul class="divide-y divide-graphite-800">
                    @forelse($alarms->take(12) as $sensor)
                        <li class="px-4 py-3">
                            <p class="flex items-center gap-2 font-semibold"><span class="h-3 w-3 shrink-0 rounded-full {{ $sensor->displayStatus()->color() }}"></span>{{ $sensor->device->name }}</p>
                            <p class="pl-5 text-sm text-graphite-300">{{ $sensor->name }} : {{ $sensor->last_message }}</p>
                            <p class="pl-5 text-xs text-graphite-500">depuis {{ $sensor->status_changed_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}{{ $sensor->acknowledger ? ' · pris en compte par '.$sensor->acknowledger->name : '' }}</p>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-lg text-green-400">Tout va bien</li>
                    @endforelse
                </ul>
            </aside>
        </div>
        <p class="text-xs text-graphite-500">Mis à jour à {{ now()->format('H:i:s') }} · actualisation automatique toutes les 30 secondes</p>
    </div>
</body>
</html>
