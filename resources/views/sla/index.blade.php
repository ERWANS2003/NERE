@extends('layouts.portal')

@section('titre', 'Gestion des SLA')
@section('sous-titre', 'Accord de niveaux de service')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Gestion des SLA</h1>
            <p class="text-sm text-gray-400 mt-1">Accord de niveau de service par priorité</p>
        </div>
        <a href="{{ route('sla.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
            + Nouveau SLA
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Total SLA</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ $stats['total_sla'] }}</p>
                </div>
                <div class="text-3xl">📋</div>
            </div>
        </div>

        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">SLA Actifs</p>
                    <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['active_sla'] }}</p>
                </div>
                <div class="text-3xl">✓</div>
            </div>
        </div>

        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Tickets en Alerte</p>
                    <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['tickets_sla_warning'] }}</p>
                </div>
                <div class="text-3xl">⚠️</div>
            </div>
        </div>

        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">SLA Dépassés</p>
                    <p class="text-2xl font-bold text-red-400 mt-1">{{ $stats['tickets_sla_breached'] }}</p>
                </div>
                <div class="text-3xl">🚨</div>
            </div>
        </div>
    </div>

    <!-- SLA Table -->
    <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden">
        @if($slas->isEmpty())
            <div class="p-12 text-center text-gray-400">
                <p>Aucun SLA configuré.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-dark-700 bg-dark-700/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Priorité</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Site</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Réponse</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Résolution</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Tickets</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700">
                        @foreach($slas as $sla)
                            <tr class="hover:bg-dark-700/30 transition">
                                <td class="px-6 py-4 text-sm text-white font-medium">{{ $sla->nom }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium
                                        @if($sla->priorite?->niveau >= 3) bg-red-900/30 text-red-400
                                        @elseif($sla->priorite?->niveau >= 2) bg-yellow-900/30 text-yellow-400
                                        @else bg-green-900/30 text-green-400 @endif">
                                        {{ $sla->priorite?->nom ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">{{ $sla->site?->nom ?? 'Global' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-300">
                                    <span class="font-mono">{{ $sla->temps_reponse_heures }}h</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-300">
                                    <span class="font-mono">{{ $sla->temps_resolution_heures }}h</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">{{ $sla->tickets->count() }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium
                                        @if($sla->actif) bg-green-900/30 text-green-400
                                        @else bg-gray-900/30 text-gray-400 @endif">
                                        {{ $sla->actif ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex gap-2 justify-end">
                                        <a href="{{ route('sla.show', $sla) }}" class="text-blue-400 hover:text-blue-300 transition">
                                            Voir
                                        </a>
                                        <a href="{{ route('sla.edit', $sla) }}" class="text-gray-400 hover:text-white transition">
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
            <div class="px-6 py-4 border-t border-dark-700">
                {{ $slas->links('pagination::simple-bootstrap-4') }}
            </div>
        @endif
    </div>
</div>
@endsection
