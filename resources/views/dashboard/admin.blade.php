@extends('layouts.portal')

@section('titre', 'Tableau de Bord Admin')
@section('sous-titre', 'Vue d\'ensemble système')

@section('contenu')
<div class="p-6 space-y-6">
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Tickets -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-blue-600/20 flex items-center justify-center border border-blue-600/30">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <span class="text-xs text-gray-500">Total</span>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['total_tickets'] }}</div>
            <div class="text-sm text-gray-400">Tickets système</div>
        </div>

        <!-- Tickets Ouverts -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-primary-600/20 flex items-center justify-center border border-primary-600/30">
                    <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-xs text-gray-500">Actifs</span>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['tickets_ouverts'] }}</div>
            <div class="text-sm text-gray-400">Tickets ouverts</div>
        </div>

        <!-- Critiques -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-red-600/20 flex items-center justify-center border border-red-600/30">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <span class="text-xs text-gray-500">Urgent</span>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['tickets_critiques'] }}</div>
            <div class="text-sm text-gray-400">Tickets critiques</div>
        </div>

        <!-- Users -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-green-600/20 flex items-center justify-center border border-green-600/30">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <span class="text-xs text-gray-500">Actifs</span>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['utilisateurs_actifs'] }}</div>
            <div class="text-sm text-gray-400">Utilisateurs</div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="stat-card">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-cyan-600/20 flex items-center justify-center border border-cyan-600/30">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white">{{ $stats['techniciens_disponibles'] }}</div>
                    <div class="text-sm text-gray-400">Techniciens disponibles</div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-600/20 flex items-center justify-center border border-orange-600/30">
                    <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white">{{ $stats['sla_depasse'] }}</div>
                    <div class="text-sm text-gray-400">SLA dépassés</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Recent Tickets -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Tickets récents
            </h3>
            <div class="space-y-3">
                @forelse($recentTickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="block p-3 rounded-lg bg-dark-900 hover:bg-dark-700 transition border border-dark-700 hover:border-primary-600">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-white truncate">{{ $ticket->titre }}</span>
                                    @if($ticket->sla_depasse)
                                        <span class="px-2 py-0.5 bg-red-600/20 border border-red-600/30 text-red-400 text-xs rounded-full">SLA</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-400">
                                    <span>{{ $ticket->reference }}</span>
                                    <span>•</span>
                                    <span>{{ $ticket->demandeur?->name ?? 'Utilisateur supprimé' }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="px-2 py-1 border text-xs rounded-full inline-flex items-center"
                                      style="background-color: {{ $ticket->priorite?->couleur ?? '#666666' }}22; color: {{ $ticket->priorite?->couleur ?? '#999999' }}; border-color: {{ $ticket->priorite?->couleur ?? '#666666' }}44;">
                                    {{ $ticket->priorite?->nom ?? 'Standard' }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $ticket->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p>Aucun ticket récent</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Tickets par Département -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Distribution par département
            </h3>
            <div class="space-y-3">
                @forelse($ticketsParDepartement as $dept => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-300">{{ $dept }}</span>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 bg-dark-700 rounded-full w-32">
                                <div class="h-full bg-primary-600 rounded-full" style="width: {{ ($count / $ticketsParDepartement->max()) * 100 }}%"></div>
                            </div>
                            <span class="text-sm font-semibold text-white w-8 text-right">{{ $count }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <p>Aucune donnée disponible</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Actions rapides</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 p-4 bg-dark-900 hover:bg-dark-700 rounded-lg border border-dark-700 hover:border-primary-600 transition">
                <svg class="w-8 h-8 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <div>
                    <div class="font-medium text-white">Utilisateurs</div>
                    <div class="text-xs text-gray-400">Gérer</div>
                </div>
            </a>

            <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-3 p-4 bg-dark-900 hover:bg-dark-700 rounded-lg border border-dark-700 hover:border-primary-600 transition">
                <svg class="w-8 h-8 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <div>
                    <div class="font-medium text-white">Rôles</div>
                    <div class="text-xs text-gray-400">Permissions</div>
                </div>
            </a>

            <a href="{{ route('tickets.index') }}" class="flex items-center gap-3 p-4 bg-dark-900 hover:bg-dark-700 rounded-lg border border-dark-700 hover:border-primary-600 transition">
                <svg class="w-8 h-8 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <div>
                    <div class="font-medium text-white">Tous les tickets</div>
                    <div class="text-xs text-gray-400">Consulter</div>
                </div>
            </a>

            <a href="{{ route('admin.settings.departments') }}" class="flex items-center gap-3 p-4 bg-dark-900 hover:bg-dark-700 rounded-lg border border-dark-700 hover:border-primary-600 transition">
                <svg class="w-8 h-8 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <div>
                    <div class="font-medium text-white">Paramètres</div>
                    <div class="text-xs text-gray-400">Configurer</div>
                </div>
            </a>
        </div>
    </div>

</div>

@push('styles')
<style>
.stat-card {
    @apply bg-dark-800 rounded-xl p-6 border border-dark-700;
}
</style>
@endpush
@endsection
