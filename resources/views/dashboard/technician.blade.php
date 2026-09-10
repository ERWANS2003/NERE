@extends('layouts.portal-minimal')

@section('titre', 'Mes Tickets')
@section('sous-titre', 'Vue technicien - Tickets assignés et file d\'attente')

@section('contenu')
<div class="p-6 space-y-6">
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-primary-600/20 flex items-center justify-center border border-primary-600/30">
                    <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['mes_tickets'] ?? 0 }}</div>
            <div class="text-sm text-gray-400">Mes tickets actifs</div>
        </div>

        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-red-600/20 flex items-center justify-center border border-red-600/30">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['tickets_critiques'] ?? 0 }}</div>
            <div class="text-sm text-gray-400">Critiques (SLA dépassé)</div>
        </div>

        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-600/20 flex items-center justify-center border border-yellow-600/30">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['en_attente_assignment'] ?? 0 }}</div>
            <div class="text-sm text-gray-400">En attente d'assignation</div>
        </div>

        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-green-600/20 flex items-center justify-center border border-green-600/30">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['resolus_aujourdhui'] ?? 0 }}</div>
            <div class="text-sm text-gray-400">Résolus aujourd'hui</div>
        </div>
    </div>

    <!-- My Tickets -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 8a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Mes tickets assignés
        </h3>
        <div class="space-y-3">
            @forelse($myTickets ?? [] as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}" 
                   class="block p-4 rounded-lg bg-dark-900 hover:bg-dark-700 transition border border-dark-700 hover:border-primary-600">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-semibold text-white truncate">{{ $ticket->titre ?? 'Titre' }}</h4>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-400">
                                <span>{{ $ticket->reference ?? 'REF' }}</span>
                            </div>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            {{ $ticket->created_at?->diffForHumans() ?? '—' }}
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <p>Aucun ticket assigné</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
