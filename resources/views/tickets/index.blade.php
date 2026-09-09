@extends('layouts.portal')

@section('titre', 'Mes Tickets')
@section('sous-titre', auth()->user()->hasRole('demandeur') ? 'Mes demandes de support' : 'Gestion des tickets')

@section('contenu')
<div class="p-6 space-y-6">
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="text-2xl font-bold text-white">{{ $tickets->total() }}</div>
            <div class="text-sm text-gray-400">Total tickets</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-blue-400">
                {{ \App\Models\Ticket::where('ticket_status_id', 1)->count() }}
            </div>
            <div class="text-sm text-gray-400">Ouverts</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-orange-400">
                {{ \App\Models\Ticket::where('ticket_status_id', 2)->count() }}
            </div>
            <div class="text-sm text-gray-400">En cours</div>
        </div>
        <div class="stat-card">
            <div class="text-2xl font-bold text-red-400">
                {{ \App\Models\Ticket::whereHas('priorite', fn($q) => $q->where('niveau', '>=', 3))->count() }}
            </div>
            <div class="text-sm text-gray-400">Urgents</div>
        </div>
    </div>

    <!-- Status Filters -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <a href="{{ route('tickets.index') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition {{ !request('statut') ? 'bg-primary-600 text-white' : 'bg-dark-800 text-gray-400 hover:bg-dark-700 border border-dark-700' }}">
            Tous
            <span class="px-2 py-0.5 bg-black/20 rounded-full text-xs">{{ $tickets->total() }}</span>
        </a>
        @foreach ($statuts as $statut)
            <a href="{{ route('tickets.index', ['statut' => $statut->id]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap {{ request('statut') == $statut->id ? 'text-white border-2' : 'bg-dark-800 text-gray-400 hover:bg-dark-700 border border-dark-700' }}"
               style="{{ request('statut') == $statut->id ? 'background:' . $statut->couleur . '44; border-color:' . $statut->couleur : '' }}">
                {{ $statut->nom }}
            </a>
        @endforeach
    </div>

    <!-- Filters Card -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <form method="GET" action="{{ route('tickets.index') }}" class="grid grid-cols-4 gap-4">
            <div>
                <label for="q" class="block text-sm font-medium text-gray-400 mb-2">Recherche</label>
                <input type="search" 
                       id="q" 
                       name="q" 
                       value="{{ request('q') }}" 
                       placeholder="Référence, titre..." 
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
            </div>

            <div>
                <label for="priorite" class="block text-sm font-medium text-gray-400 mb-2">Priorité</label>
                <select id="priorite" 
                        name="priorite"
                        class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                    <option value="">Toutes</option>
                    @foreach ($priorites as $priorite)
                        <option value="{{ $priorite->id }}" @selected(request('priorite') == $priorite->id)>{{ $priorite->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="categorie" class="block text-sm font-medium text-gray-400 mb-2">Catégorie</label>
                <select id="categorie" 
                        name="categorie"
                        class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                    <option value="">Toutes</option>
                    @foreach ($categories as $categorie)
                        <option value="{{ $categorie->id }}" @selected(request('categorie') == $categorie->id)>{{ $categorie->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="site" class="block text-sm font-medium text-gray-400 mb-2">Site</label>
                <select id="site" 
                        name="site"
                        class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 outline-none transition">
                    <option value="">Tous</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-4 flex items-center gap-2">
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Filtrer
                </button>
                
                @if (request()->hasAny(['q', 'statut', 'priorite', 'departement', 'categorie', 'site']))
                    <a href="{{ route('tickets.index') }}" 
                       class="inline-flex items-center gap-2 px-6 py-2 bg-dark-700 hover:bg-dark-600 text-gray-300 rounded-lg font-medium transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
        @if ($tickets->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="text-lg font-semibold text-white mb-2">Aucun ticket trouvé</h3>
                <p class="text-gray-400 mb-4">Essayez de modifier vos filtres ou créez un nouveau ticket</p>
                <a href="{{ route('tickets.create') }}" 
                   class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Créer un ticket
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-dark-900 border-b border-dark-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Référence</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Titre</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Priorité</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Catégorie</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Technicien</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Créé le</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700">
                        @foreach ($tickets as $ticket)
                            <tr class="hover:bg-dark-700 transition {{ $ticket->trashed() ? 'opacity-50' : '' }}">
                                <td class="px-6 py-4">
                                    <a href="{{ route('tickets.show', $ticket) }}" 
                                       class="font-mono text-primary-400 hover:text-primary-300 font-semibold">
                                        {{ $ticket->reference }}
                                    </a>
                                    @if ($ticket->sla_depasse || $ticket->estEnRetard())
                                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 bg-red-600/20 border border-red-600/30 text-red-400 text-xs rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            SLA
                                        </span>
                                    @endif
                                    @if ($ticket->trashed())
                                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 bg-gray-600/20 border border-gray-600/30 text-gray-400 text-xs rounded-full">
                                            Archivé
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('tickets.show', $ticket) }}" 
                                       class="text-white hover:text-primary-400 transition">
                                        {{ Str::limit($ticket->titre, 50) }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($ticket->statut)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              style="background:{{ $ticket->statut->couleur }}22; color:{{ $ticket->statut->couleur }}; border:1px solid {{ $ticket->statut->couleur }}44;">
                                            {{ $ticket->statut->nom }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($ticket->priorite)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              style="background:{{ $ticket->priorite->couleur }}22; color:{{ $ticket->priorite->couleur }}; border:1px solid {{ $ticket->priorite->couleur }}44;">
                                            {{ $ticket->priorite->nom }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-300">{{ $ticket->categorie?->nom ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if($ticket->technicien)
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center flex-shrink-0">
                                                <span class="text-white font-semibold text-xs">{{ substr($ticket->technicien->name, 0, 2) }}</span>
                                            </div>
                                            <span class="text-gray-300">{{ $ticket->technicien->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-500">Non assigné</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-400 text-sm whitespace-nowrap">
                                    {{ $ticket->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('tickets.show', $ticket) }}" 
                                           class="p-2 text-blue-400 hover:bg-blue-600/20 rounded-lg transition" title="Voir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        
                                        @if (!$ticket->trashed())
                                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" 
                                                  onsubmit="return confirm('Archiver ce ticket ?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="p-2 text-red-400 hover:bg-red-600/20 rounded-lg transition" title="Archiver">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @elseif (auth()->user()->hasRole('admin'))
                                            <form method="POST" action="{{ route('tickets.restore', $ticket->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="p-2 text-green-400 hover:bg-green-600/20 rounded-lg transition" title="Restaurer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($tickets->hasPages())
                <div class="px-6 py-4 border-t border-dark-700 bg-dark-900">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            Affichage de {{ $tickets->firstItem() ?? 0 }} à {{ $tickets->lastItem() ?? 0 }} sur {{ $tickets->total() }} résultats
                        </div>
                        <div>
                            {{ $tickets->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

</div>

@push('styles')
<style>
.stat-card {
    @apply bg-dark-800 rounded-xl p-4 border border-dark-700;
}
</style>
@endpush
@endsection
