@extends('layouts.portal')

@section('titre', 'Détails du SLA')
@section('sous-titre', 'Accord de niveau de service')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $sla->nom }}</h1>
            <p class="text-sm text-gray-400 mt-1">
                Priorité: <span class="font-medium text-white">{{ $sla->priorite?->nom ?? 'N/A' }}</span>
                @if($sla->site)
                    | Site: <span class="font-medium text-white">{{ $sla->site->nom }}</span>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('sla.edit', $sla) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Modifier
            </a>
            <a href="{{ route('sla.index') }}" class="px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-800 transition">
                Retour
            </a>
        </div>
    </div>

    <!-- SLA Configuration -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Key Metrics -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Métriques</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-dark-700 rounded-lg p-4">
                        <p class="text-sm text-gray-400">Temps de Réponse</p>
                        <p class="text-2xl font-bold text-primary-400 mt-2">{{ $sla->temps_reponse_heures }}h</p>
                        <p class="text-xs text-gray-500 mt-1">Avant première réponse</p>
                    </div>
                    <div class="bg-dark-700 rounded-lg p-4">
                        <p class="text-sm text-gray-400">Temps de Résolution</p>
                        <p class="text-2xl font-bold text-primary-400 mt-2">{{ $sla->temps_resolution_heures }}h</p>
                        <p class="text-xs text-gray-500 mt-1">Délai de résolution</p>
                    </div>
                </div>
            </div>

            <!-- Conformité SLA -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Conformité SLA</h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm text-gray-400">Taux de Conformité</p>
                            <p class="text-lg font-bold text-white">{{ $stats['percentage_met'] }}%</p>
                        </div>
                        <div class="w-full bg-dark-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-500 to-green-400 h-full rounded-full transition-all" 
                                 style="width: {{ $stats['percentage_met'] }}%"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-green-900/20 border border-green-800 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-green-400">{{ $stats['met'] }}</p>
                            <p class="text-xs text-green-300 mt-1">Respectés</p>
                        </div>
                        <div class="bg-red-900/20 border border-red-800 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-red-400">{{ $stats['breached'] }}</p>
                            <p class="text-xs text-red-300 mt-1">Dépassés</p>
                        </div>
                        <div class="bg-blue-900/20 border border-blue-800 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-blue-400">{{ $stats['total'] }}</p>
                            <p class="text-xs text-blue-300 mt-1">Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tickets sous ce SLA -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Tickets Récents ({{ $sla->tickets->count() }})</h2>
                @if($sla->tickets->isEmpty())
                    <p class="text-gray-400">Aucun ticket sous ce SLA.</p>
                @else
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($sla->tickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="block p-3 bg-dark-700 hover:bg-dark-600 rounded-lg transition">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-mono text-primary-400">{{ $ticket->reference }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ Str::limit($ticket->titre, 60) }}</p>
                                    </div>
                                    <span class="inline-block px-2 py-1 text-xs font-medium rounded
                                        @if($ticket->sla_depasse) bg-red-900/30 text-red-400
                                        @else bg-green-900/30 text-green-400 @endif">
                                        {{ $ticket->sla_depasse ? '🚨 Dépassé' : '✓ OK' }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar: Status & Actions -->
        <div class="space-y-4">
            <!-- Status -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Statut</h2>
                <span class="inline-block px-4 py-2 rounded-full text-sm font-medium
                    @if($sla->actif) bg-green-900/30 text-green-400
                    @else bg-gray-900/30 text-gray-400 @endif">
                    {{ $sla->actif ? '✓ Actif' : '× Inactif' }}
                </span>
            </div>

            <!-- Info -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Informations</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Créé le</p>
                        <p class="text-sm text-white">{{ $sla->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Modifié le</p>
                        <p class="text-sm text-white">{{ $sla->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Actions</h2>
                <form method="POST" action="{{ route('sla.destroy', $sla) }}" class="space-y-2" onsubmit="return confirm('Êtes-vous sûr?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                        Supprimer ce SLA
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
