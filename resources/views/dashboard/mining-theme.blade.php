@extends('layouts.portal')

@section('titre', 'Tableau de bord')
@section('sous-titre', 'Bienvenue sur votre portail ITSM Néré Mining')

@section('contenu')
<div class="p-6 space-y-6 bg-charcoal-900 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header with welcome message -->
        <div class="fade-in-up">
            <div class="mining-card border-gold-500/30 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gold-400 mb-2">Bienvenue, {{ auth()->user()->name }}</h1>
                        <p class="text-charcoal-400">Gérez vos tickets et demandes de service</p>
                    </div>
                    <div class="hidden md:block">
                        <div class="w-20 h-20 rounded-lg bg-gradient-to-br from-gold-500 to-crimson-500 flex items-center justify-center shadow-gold-glow">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 slide-in-left">
            <!-- Tickets Ouverts -->
            <x-mining-stat
                title="Tickets Ouverts"
                :value="$stats['open_tickets'] ?? 0"
                :trend="5"
                color="gold"
                class="hover-lift"
            >
                <x:slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </x:slot>
            </x-mining-stat>

            <!-- Tickets Résolus -->
            <x-mining-stat
                title="Résolus Aujourd'hui"
                :value="$stats['resolved_today'] ?? 0"
                :trend="12"
                color="green"
                class="hover-lift"
            >
                <x:slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </x:slot>
            </x-mining-stat>

            <!-- En Attente -->
            <x-mining-stat
                title="En Attente"
                :value="$stats['pending'] ?? 0"
                :trend="-8"
                color="crimson"
                class="hover-lift"
            >
                <x:slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </x:slot>
            </x-mining-stat>

            <!-- Temps Moyen -->
            <x-mining-stat
                title="Temps Moyen"
                value="2h 45m"
                :trend="3"
                color="blue"
                class="hover-lift"
            >
                <x:slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </x:slot>
            </x-mining-stat>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column - Recent Tickets -->
            <div class="lg:col-span-2 space-y-6 slide-in-left" style="animation-delay: 0.1s;">
                <!-- Recent Tickets -->
                <x-mining-card accent="gold">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gold-400">Tickets Récents</h3>
                        <a href="{{ route('tickets.index') }}" class="text-sm text-gold-300 hover:text-gold-400 transition">Voir tout →</a>
                    </div>

                    <div class="space-y-3">
                        @forelse($recentTickets ?? [] as $ticket)
                            <div class="ticket-card">
                                <div class="ticket-card-header">
                                    <div class="ticket-card-title flex items-center gap-2">
                                        <span class="text-gold-400 font-mono text-sm">#{{ $ticket->id }}</span>
                                        <span>{{ $ticket->titre }}</span>
                                    </div>
                                    <x-mining-badge variant="gold" size="sm">
                                        {{ $ticket->statut?->nom ?? 'N/A' }}
                                    </x-mining-badge>
                                </div>
                                <p class="ticket-card-body">{{ Str::limit($ticket->description, 100) }}</p>
                                <div class="ticket-card-footer">
                                    <span>{{ $ticket->created_at->diffForHumans() }}</span>
                                    <span class="text-gold-400">{{ $ticket->priorite?->nom ?? 'Normal' }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="empty-state-title">Aucun ticket</p>
                                <p class="empty-state-description">Vous n'avez pas de tickets récents</p>
                                <a href="{{ route('tickets.create') }}" class="inline-block mt-2">
                                    <x-mining-button variant="gold" size="sm">
                                        Créer un ticket
                                    </x-mining-button>
                                </a>
                            </div>
                        @endforelse
                    </div>
                </x-mining-card>

                <!-- Quick Actions -->
                <x-mining-card accent="crimson">
                    <h3 class="text-lg font-bold text-crimson-400 mb-4">Actions Rapides</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('tickets.create') }}" class="group">
                            <x-mining-button variant="gold" size="md" class="w-full group-hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Nouveau Ticket</span>
                            </x-mining-button>
                        </a>
                        <a href="{{ route('search.index') }}" class="group">
                            <x-mining-button variant="outline" size="md" class="w-full group-hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <span>Rechercher</span>
                            </x-mining-button>
                        </a>
                        <a href="{{ route('knowledge.index') }}" class="group">
                            <x-mining-button variant="secondary" size="md" class="w-full group-hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <span>Base de Connaissances</span>
                            </x-mining-button>
                        </a>
                        <a href="{{ route('templates.index') }}" class="group">
                            <x-mining-button variant="secondary" size="md" class="w-full group-hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Modèles</span>
                            </x-mining-button>
                        </a>
                    </div>
                </x-mining-card>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="space-y-6 slide-in-right" style="animation-delay: 0.2s;">
                
                <!-- Priority Distribution -->
                <x-mining-card accent="gold">
                    <h3 class="text-lg font-bold text-gold-400 mb-4">Distribution Priorités</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-charcoal-400">Critique</span>
                            <div class="flex-1 mx-2 h-2 bg-charcoal-800 rounded-full overflow-hidden">
                                <div class="h-full bg-crimson-500 rounded-full" style="width: {{ ($stats['critical'] ?? 0) * 10 }}%"></div>
                            </div>
                            <span class="text-crimson-400 font-bold">{{ $stats['critical'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-charcoal-400">Haut</span>
                            <div class="flex-1 mx-2 h-2 bg-charcoal-800 rounded-full overflow-hidden">
                                <div class="h-full bg-orange-500 rounded-full" style="width: {{ ($stats['high'] ?? 0) * 10 }}%"></div>
                            </div>
                            <span class="text-orange-400 font-bold">{{ $stats['high'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-charcoal-400">Normal</span>
                            <div class="flex-1 mx-2 h-2 bg-charcoal-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gold-500 rounded-full" style="width: {{ ($stats['medium'] ?? 0) * 10 }}%"></div>
                            </div>
                            <span class="text-gold-400 font-bold">{{ $stats['medium'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-charcoal-400">Bas</span>
                            <div class="flex-1 mx-2 h-2 bg-charcoal-800 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ ($stats['low'] ?? 0) * 10 }}%"></div>
                            </div>
                            <span class="text-blue-400 font-bold">{{ $stats['low'] ?? 0 }}</span>
                        </div>
                    </div>
                </x-mining-card>

                <!-- Support Info -->
                <x-mining-card accent="crimson">
                    <div class="text-center py-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-crimson-500 to-gold-500 flex items-center justify-center mx-auto mb-3 shadow-crimson-glow">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold text-white mb-1">Besoin d'Aide ?</h3>
                        <p class="text-sm text-charcoal-400 mb-3">Consultez notre base de connaissances ou contactez le support</p>
                        <a href="mailto:support@neremingin.local" class="inline-block">
                            <x-mining-button variant="crimson" size="sm">
                                Contacter le Support
                            </x-mining-button>
                        </a>
                    </div>
                </x-mining-card>

            </div>
        </div>

    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="{{ asset('css/mining-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/mining-components.css') }}">
@endpush

@endsection
