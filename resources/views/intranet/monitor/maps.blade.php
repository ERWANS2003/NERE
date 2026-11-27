@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Cartes']];
@endphp

<x-intranet-layout title="Cartes de supervision" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Supervision réseau" description="Des plans (site, bâtiment, schéma réseau) où chaque équipement apparaît avec la couleur de son état. Une carte peut s'afficher en plein écran sur un écran mural." />
    @include('intranet.monitor.partials.nav')

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($maps as $map)
            @php
                $statuses = $map->items->map(fn ($item) => $item->device->status());
                $down = $statuses->filter(fn ($s) => $s === \App\Enums\MonitorStatus::Down)->count();
                $warning = $statuses->filter(fn ($s) => $s === \App\Enums\MonitorStatus::Warning)->count();
            @endphp
            <article class="carte flex flex-col overflow-hidden">
                <a href="{{ route('intranet.monitor.maps.show', $map) }}" class="block flex-1 p-5 hover:bg-graphite-50">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-semibold text-graphite-900">{{ $map->name }}</p>
                        @if($map->items->isNotEmpty())
                            @include('intranet.monitor.partials.badge', ['status' => \App\Enums\MonitorStatus::worst($statuses)])
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-graphite-600">
                        {{ $map->items->count() }} équipement(s){{ $map->background_path ? ' · avec plan' : '' }}
                        @if($down) · <span class="font-semibold text-red-700">{{ $down }} en panne</span>@endif
                        @if($warning) · <span class="font-semibold text-amber-700">{{ $warning }} en avertissement</span>@endif
                    </p>
                    @if($map->items->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-1" aria-hidden="true">
                            @foreach($statuses as $status)
                                <span class="h-2 w-2 rounded-full {{ $status->color() }}"></span>
                            @endforeach
                        </div>
                    @endif
                </a>
                <div class="flex gap-4 border-t border-graphite-100 px-5 py-2.5 text-sm font-medium">
                    <a href="{{ route('intranet.monitor.maps.screen', $map) }}" target="_blank" rel="noopener" class="text-nere-700 hover:underline">Écran mural</a>
                    @can('monitor.manage')
                        <a href="{{ route('intranet.monitor.maps.show', [$map, 'edition' => 1]) }}" class="text-graphite-600 hover:underline">Modifier</a>
                    @endcan
                </div>
            </article>
        @endforeach
        @can('monitor.manage')
        <form method="POST" action="{{ route('intranet.monitor.maps.store') }}" class="carte flex flex-col gap-3 p-5">
            @csrf
            <label class="block">
                <span class="etiquette">Nouvelle carte</span>
                <input name="name" required maxlength="120" placeholder="Plan du site de la mine" class="champ">
                @error('name')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <button class="bouton-primaire">Créer la carte</button>
        </form>
        @endcan
    </div>
</x-intranet-layout>
