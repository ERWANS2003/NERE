@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Rapports']];
@endphp

<x-intranet-layout title="Rapport de disponibilité" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Supervision réseau" description="Disponibilité, pannes et temps de réponse par équipement et par capteur, à exporter pour la direction." />
    @include('intranet.monitor.partials.nav')

    <form method="GET" action="{{ route('intranet.monitor.reports') }}" class="carte mb-6 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5" x-data="{ periode: @js($filters['periode']) }">
        <label class="block">
            <span class="etiquette">Groupe</span>
            <select name="groupe" class="champ">
                <option value="">Tous les équipements</option>
                @foreach($groups as $id => $path)
                    <option value="{{ $id }}" @selected((string) ($filters['groupe'] ?? '') === (string) $id)>{{ $path }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="etiquette">Période</span>
            <select name="periode" x-model="periode" class="champ">
                @foreach($ranges as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block" x-show="periode === 'perso'" x-cloak>
            <span class="etiquette">Du</span>
            <input type="date" name="du" value="{{ $filters['du'] ?? '' }}" class="champ" :disabled="periode !== 'perso'">
            @error('du')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="block" x-show="periode === 'perso'" x-cloak>
            <span class="etiquette">Au</span>
            <input type="date" name="au" value="{{ $filters['au'] ?? '' }}" class="champ" :disabled="periode !== 'perso'">
            @error('au')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        <div class="flex flex-wrap items-end gap-2">
            <button class="bouton-primaire">Afficher</button>
            <button name="format" value="csv" class="bouton-secondaire">CSV</button>
            <button name="format" value="pdf" class="bouton-secondaire">PDF</button>
        </div>
    </form>

    <section class="mb-6" aria-labelledby="tendances">
        <h2 id="tendances" class="mb-3 font-semibold text-graphite-900">Tendances des 7 derniers jours</h2>
        <div class="grid gap-4 lg:grid-cols-3">
            @foreach($trends as $trendTitle => $trendRows)
                <div class="carte overflow-hidden">
                    <h3 class="border-b border-graphite-100 px-4 py-2.5 text-sm font-semibold text-graphite-800">{{ $trendTitle }}</h3>
                    <ol class="divide-y divide-graphite-100 text-sm">
                        @forelse($trendRows as [$sensor, $value])
                            <li class="flex items-center justify-between gap-3 px-4 py-2">
                                <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="min-w-0 truncate hover:text-nere-700">{{ $sensor->device->name }} · {{ $sensor->name }}</a>
                                <span class="shrink-0 font-semibold text-graphite-900">{{ $value }}</span>
                            </li>
                        @empty
                            <li class="px-4 py-3 text-graphite-600">Rien à signaler.</li>
                        @endforelse
                    </ol>
                </div>
            @endforeach
        </div>
    </section>

    @include('intranet.monitor.partials.report-table')
</x-intranet-layout>
