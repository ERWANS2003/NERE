@extends('layouts.portal')

@section('titre', 'Gestion des Actifs')
@section('sous-titre', 'Inventaire des équipements et ressources')

@section('contenu')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Actifs ({{ $assets->total() }})</h1>
            <p class="text-sm text-gray-400 mt-1">Gestion du parc informatique et ressources</p>
        </div>
        <a href="{{ route('assets.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
            + Nouvel Actif
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('assets.index') }}" class="bg-dark-800 border border-dark-700 rounded-xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Recherche</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, code, série…" 
                    class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white placeholder-gray-500 focus:border-primary-500 transition">
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Type</label>
                <select name="type" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                    <option value="">Tous les types</option>
                    @foreach(\App\Models\AssetType::orderBy('nom')->get() as $type)
                        <option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->nom }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Statut</label>
                <select name="statut" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                    <option value="">Tous les statuts</option>
                    <option value="En stock" @selected(request('statut') === 'En stock')>En stock</option>
                    <option value="En service" @selected(request('statut') === 'En service')>En service</option>
                    <option value="En maintenance" @selected(request('statut') === 'En maintenance')>En maintenance</option>
                    <option value="Hors service" @selected(request('statut') === 'Hors service')>Hors service</option>
                    <option value="Réformé" @selected(request('statut') === 'Réformé')>Réformé</option>
                </select>
            </div>

            <!-- Department -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Département</label>
                <select name="departement" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Departement::where('actif', true)->orderBy('nom')->get() as $dept)
                        <option value="{{ $dept->id }}" @selected(request('departement') == $dept->id)>{{ $dept->nom }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 items-end">
                <button type="submit" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    Filtrer
                </button>
                @if(request()->hasAny(['q', 'type', 'statut', 'departement']))
                    <a href="{{ route('assets.index') }}" class="flex-1 px-3 py-2 border border-dark-700 text-white rounded-lg text-sm hover:bg-dark-700 transition text-center">
                        Réinitialiser
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
        @if($assets->isEmpty())
            <div class="p-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p>Aucun actif trouvé.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-dark-700 bg-dark-700/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Département</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700">
                        @foreach($assets as $asset)
                            <tr class="hover:bg-dark-700/30 transition">
                                <td class="px-6 py-4 text-sm text-gray-300">
                                    <span class="font-mono text-xs bg-dark-700 px-2 py-1 rounded">{{ $asset->code_inventaire }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('assets.show', $asset) }}" class="text-white hover:text-primary-400 transition">
                                        {{ $asset->nom }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">{{ $asset->type?->nom ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium
                                        @if($asset->statut === 'En service') bg-green-900/30 text-green-400
                                        @elseif($asset->statut === 'En stock') bg-blue-900/30 text-blue-400
                                        @elseif($asset->statut === 'En maintenance') bg-yellow-900/30 text-yellow-400
                                        @elseif($asset->statut === 'Hors service') bg-red-900/30 text-red-400
                                        @else bg-gray-900/30 text-gray-400 @endif">
                                        {{ $asset->statut }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">{{ $asset->utilisateur?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-400">{{ $asset->departement?->nom ?? '-' }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex gap-2 justify-end">
                                        <a href="{{ route('assets.show', $asset) }}" class="text-blue-400 hover:text-blue-300 transition">
                                            Voir
                                        </a>
                                        <a href="{{ route('assets.edit', $asset) }}" class="text-gray-400 hover:text-white transition">
                                            Modifier
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-dark-700 flex items-center justify-between">
                <div class="text-sm text-gray-400">
                    Affichage {{ $assets->firstItem() }}-{{ $assets->lastItem() }} sur {{ $assets->total() }}
                </div>
                {{ $assets->links('pagination::simple-bootstrap-4') }}
            </div>
        @endif
    </div>
</div>
@endsection
