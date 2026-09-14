@extends('layouts.portal')

@section('titre', 'Recherche Avancée')
@section('sous-titre', 'Filtres avancés et sauvegarde de recherches')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Recherche Avancée</h1>
            <p class="text-sm text-gray-400 mt-1">Filtrez et recherchez les tickets avec plusieurs critères</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-800 transition">
            Vue Liste
        </a>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Filters Sidebar -->
        <div class="lg:col-span-1">
            <form method="GET" action="{{ route('search.index') }}" class="bg-dark-800 border border-dark-700 rounded-xl p-6 space-y-4 sticky top-6">
                <!-- Search Text -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Recherche</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Ref, titre, description…"
                        class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Statut</label>
                    <select name="status_id" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                        <option value="">-- Tous --</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" @selected($filters['status_id'] == $status->id)>{{ $status->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Priorité</label>
                    <select name="priority_id" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                        <option value="">-- Toutes --</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->id }}" @selected($filters['priority_id'] == $priority->id)>{{ $priority->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Catégorie</label>
                    <select name="category_id" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                        <option value="">-- Toutes --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['category_id'] == $category->id)>{{ $category->nom }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Assigned To -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Assigné à</label>
                    <select name="assigned_to" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                        <option value="">-- Tous --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected($filters['assigned_to'] == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Created By -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Créé par</label>
                    <select name="created_by" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                        <option value="">-- Tous --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected($filters['created_by'] == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Date de création</label>
                    <div class="space-y-2">
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                            class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition" placeholder="De">
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                            class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition" placeholder="À">
                    </div>
                </div>

                <!-- Sort -->
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Tri</label>
                    <select name="sort" class="w-full px-3 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                        <option value="latest" @selected($filters['sort'] === 'latest')>Plus récent</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Plus ancien</option>
                        <option value="priority" @selected($filters['sort'] === 'priority')>Priorité haute</option>
                        <option value="unresolved" @selected($filters['sort'] === 'unresolved')>Non résolu</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="space-y-2 pt-2 border-t border-dark-700">
                    <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                        Rechercher
                    </button>
                    <a href="{{ route('search.index') }}" class="block text-center px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-700 transition text-sm">
                        Réinitialiser
                    </a>
                </div>

                <!-- Save Search -->
                <div class="pt-2 border-t border-dark-700">
                    <button type="button" onclick="document.getElementById('saveSearchModal').classList.remove('hidden')"
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">
                        💾 Sauvegarder la recherche
                    </button>
                </div>
            </form>

            <!-- Saved Searches -->
            @if($savedSearches->count() > 0)
                <div class="mt-6 bg-dark-800 border border-dark-700 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-white mb-3">Recherches Sauvegardées</h3>
                    <div class="space-y-2">
                        @foreach($savedSearches as $search)
                            <form method="POST" action="{{ route('search.load', $search) }}" class="flex items-center gap-2">
                                @csrf
                                <button type="submit" class="flex-1 px-3 py-2 text-sm bg-dark-700 hover:bg-dark-600 text-white rounded-lg transition text-left truncate">
                                    🔖 {{ $search->name }}
                                </button>
                                <form method="POST" action="{{ route('search.delete', $search) }}" class="inline" onsubmit="return confirm('Supprimer?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-2 text-red-400 hover:text-red-300 transition">
                                        ✕
                                    </button>
                                </form>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Results -->
        <div class="lg:col-span-3">
            <!-- Results Info -->
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-400">
                    {{ $tickets->total() }} ticket{{ $tickets->total() !== 1 ? 's' : '' }} trouvé{{ $tickets->total() !== 1 ? 's' : '' }}
                </p>
            </div>

            <!-- Results Table -->
            @if($tickets->isEmpty())
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p>Aucun ticket ne correspond à vos critères.</p>
                </div>
            @else
                <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-dark-700 bg-dark-700/50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Référence</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Titre</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Priorité</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Assigné</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Créé</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-dark-700">
                                @foreach($tickets as $ticket)
                                    <tr class="hover:bg-dark-700/30 transition">
                                        <td class="px-6 py-4 text-sm text-gray-300">
                                            <span class="font-mono">{{ $ticket->reference }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-white hover:text-primary-400 transition">
                                                {{ Str::limit($ticket->titre, 50) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium
                                                @if($ticket->status?->est_final) bg-green-900/30 text-green-400
                                                @elseif($ticket->status?->slug === 'en-cours') bg-blue-900/30 text-blue-400
                                                @else bg-gray-900/30 text-gray-400 @endif">
                                                {{ $ticket->status?->nom ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded
                                                @if($ticket->priorite?->niveau >= 3) bg-red-900/30 text-red-400
                                                @elseif($ticket->priorite?->niveau >= 2) bg-yellow-900/30 text-yellow-400
                                                @else bg-green-900/30 text-green-400 @endif">
                                                {{ $ticket->priorite?->nom ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-400">{{ $ticket->assignee?->name ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-400">{{ $ticket->created_at->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 text-right text-sm">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-400 hover:text-blue-300 transition">
                                                Voir
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-dark-700">
                        {{ $tickets->links('pagination::simple-bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Save Search Modal -->
<div id="saveSearchModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-white mb-4">Sauvegarder cette recherche</h3>
        <form method="POST" action="{{ route('search.save') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="filters" value="{{ json_encode($filters) }}">
            <div>
                <label class="block text-sm font-medium text-white mb-2">Nom <span class="text-red-400">*</span></label>
                <input type="text" name="name" placeholder="Ex: Mes tickets en attente" required
                    class="w-full px-4 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('saveSearchModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-700 transition">
                    Annuler
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                    Sauvegarder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Search JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="q"]');
    if (!searchInput) return;

    let debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) return;

        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(`/search/quick?q=${encodeURIComponent(query)}`);
                const results = await response.json();
                // Could implement typeahead dropdown here
            } catch (error) {
                console.error('Search failed:', error);
            }
        }, 300);
    });
});
</script>
@endsection
