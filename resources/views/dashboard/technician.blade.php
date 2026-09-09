@extends('layouts.portal')

@section('titre', 'Mes Tickets')
@section('sous-titre', 'Vue technicien - Tickets assignés et file d\'attente')

@section('contenu')
<div class="p-6 space-y-6">
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-primary-600/20 flex items-center justify-center border border-primary-600/30">
                    <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['mes_tickets'] }}</div>
            <div class="text-sm text-gray-400">Mes tickets actifs</div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-red-600/20 flex items-center justify-center border border-red-600/30">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['tickets_critiques'] }}</div>
            <div class="text-sm text-gray-400">SLA dépassés</div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-blue-600/20 flex items-center justify-center border border-blue-600/30">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['en_attente_assignment'] }}</div>
            <div class="text-sm text-gray-400">En attente d'assignation</div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-green-600/20 flex items-center justify-center border border-green-600/30">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['resolus_aujourdhui'] }}</div>
            <div class="text-sm text-gray-400">Résolus aujourd'hui</div>
        </div>
    </div>

    <!-- Mes Tickets Assignés -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Mes Tickets Assignés
            </h3>
            <a href="{{ route('tickets.index') }}" class="text-sm text-primary-400 hover:text-primary-300 transition">
                Voir tout →
            </a>
        </div>

        <div class="space-y-3">
            @forelse($myTickets as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}" 
                   class="block p-4 rounded-lg bg-dark-900 hover:bg-dark-700 transition border border-dark-700 hover:border-primary-600 
                          {{ $ticket->sla_depasse ? 'border-l-4 border-l-red-600' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-semibold text-white truncate">{{ $ticket->titre }}</h4>
                                @if($ticket->sla_depasse)
                                    <span class="px-2 py-0.5 bg-red-600/20 border border-red-600/30 text-red-400 text-xs rounded-full flex-shrink-0">
                                        SLA Dépassé
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-400 mb-3">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    {{ $ticket->reference }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{ $ticket->demandeur->name }}
                                </span>
                                @if($ticket->categorie)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        {{ $ticket->categorie->nom }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-{{ $ticket->statut->couleur ?? 'gray' }}-600/20 border border-{{ $ticket->statut->couleur ?? 'gray' }}-600/30 text-{{ $ticket->statut->couleur ?? 'gray' }}-400 text-xs rounded-full">
                                    {{ $ticket->statut->nom }}
                                </span>
                                <span class="px-2 py-1 bg-{{ $ticket->priorite->couleur ?? 'gray' }}-600/20 border border-{{ $ticket->priorite->couleur ?? 'gray' }}-600/30 text-{{ $ticket->priorite->couleur ?? 'gray' }}-400 text-xs rounded-full">
                                    {{ $ticket->priorite->nom }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 mb-2">{{ $ticket->created_at->diffForHumans() }}</div>
                            <button class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg transition">
                                Traiter
                            </button>
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-12 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-lg font-medium">Aucun ticket assigné</p>
                    <p class="text-sm mt-1">Vous êtes à jour! 🎉</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- File d'attente équipe -->
    @if($teamTickets->count() > 0)
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                File d'attente équipe
                <span class="px-2 py-1 bg-blue-600/20 border border-blue-600/30 text-blue-400 text-sm rounded-full">
                    {{ $teamTickets->count() }}
                </span>
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($teamTickets as $ticket)
                <div class="p-4 rounded-lg bg-dark-900 border border-dark-700 hover:border-blue-600 transition">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-white truncate mb-1">{{ $ticket->titre }}</h4>
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <span>{{ $ticket->reference }}</span>
                                <span>•</span>
                                <span>{{ $ticket->demandeur->name }}</span>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-{{ $ticket->priorite->couleur ?? 'gray' }}-600/20 border border-{{ $ticket->priorite->couleur ?? 'gray' }}-600/30 text-{{ $ticket->priorite->couleur ?? 'gray' }}-400 text-xs rounded-full flex-shrink-0">
                            {{ $ticket->priorite->nom }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-500">{{ $ticket->created_at->diffForHumans() }}</span>
                        <a href="{{ route('tickets.show', $ticket) }}" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition">
                            Prendre en charge
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@push('styles')
<style>
.stat-card {
    @apply bg-dark-800 rounded-xl p-6 border border-dark-700;
}
</style>
@endpush
@endsection
