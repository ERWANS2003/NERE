@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Cartes']];
@endphp

<x-intranet-layout title="Cartes de supervision" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Supervision réseau" description="Des plans (site, bâtiment, schéma réseau) où chaque équipement apparaît avec la couleur de son état." />
    @include('intranet.monitor.partials.nav')

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($maps as $map)
            <a href="{{ route('intranet.monitor.maps.show', $map) }}" class="carte block p-5 hover:border-nere-300">
                <p class="font-semibold text-graphite-900">{{ $map->name }}</p>
                <p class="mt-1 text-sm text-graphite-600">{{ $map->items_count }} équipement(s){{ $map->background_path ? ' · avec plan' : '' }}</p>
            </a>
        @endforeach
        <form method="POST" action="{{ route('intranet.monitor.maps.store') }}" class="carte flex flex-col gap-3 p-5">
            @csrf
            <label class="block">
                <span class="etiquette">Nouvelle carte</span>
                <input name="name" required maxlength="120" placeholder="Plan du site de la mine" class="champ">
                @error('name')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
            <button class="bouton-primaire">Créer la carte</button>
        </form>
    </div>
</x-intranet-layout>
