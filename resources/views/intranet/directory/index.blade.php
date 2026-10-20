@php
    $breadcrumbs = [
        ['label' => 'Annuaire'],
    ];
@endphp

<x-intranet-layout title="Annuaire" :breadcrumbs="$breadcrumbs">

    {{-- En-tête --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-graphite-900 mb-2">
            Annuaire des employés
        </h1>
        <p class="text-graphite-600">
            Trouvez rapidement les coordonnées de vos collègues
        </p>
    </div>

    {{-- Barre de recherche --}}
    <form method="GET" data-live-search class="bg-white rounded-lg border border-graphite-200 p-4 mb-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Rechercher par nom, email ou poste..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-graphite-300 text-sm text-graphite-900 placeholder:text-graphite-400 focus:border-nere-600 focus:ring-2 focus:ring-nere-600/20"
                >
            </div>
            {{-- Conserver le filtre département actif lors d'une recherche --}}
            @if(request('department'))
                <input type="hidden" name="department" value="{{ request('department') }}">
            @endif
            <button type="submit" class="bouton-primaire">
                Rechercher
            </button>
            @if(request('search') || request('department'))
                <a href="{{ route('intranet.directory') }}" class="bouton-secondaire">
                    Réinitialiser
                </a>
            @endif
        </div>
    </form>

    {{-- Filtres département (pills) --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('intranet.directory', array_filter(['search' => request('search')])) }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium transition
           {{ !request('department') ? 'bg-nere-600 text-white' : 'bg-white text-graphite-700 border border-graphite-200 hover:bg-graphite-50' }}">
            Tous
        </a>
        @foreach($departments as $dept)
            <a href="{{ route('intranet.directory', array_filter(['search' => request('search'), 'department' => $dept->id])) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium transition
               {{ request('department') == $dept->id ? 'bg-nere-600 text-white' : 'bg-white text-graphite-700 border border-graphite-200 hover:bg-graphite-50' }}">
                {{ $dept->name }}
            </a>
        @endforeach
    </div>

    {{-- Résultats --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-graphite-500">
            {{ $employees->total() }} employé(s) trouvé(s)
        </p>
    </div>

    @if($employees->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($employees as $employee)
                <x-employee-card :employee="$employee" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $employees->links() }}
        </div>
    @else
        <div class="bg-white rounded-lg border border-graphite-200 p-12 text-center">
            <svg class="w-16 h-16 text-graphite-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-graphite-600 mb-2">Aucun employé trouvé</p>
            <p class="text-sm text-graphite-500">Essayez de modifier vos critères de recherche</p>
        </div>
    @endif

</x-intranet-layout>
