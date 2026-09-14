@extends('layouts.portal')

@section('titre', 'Rapports & Statistiques')
@section('sous-titre', 'Vue synthétique de la performance du support')

@section('contenu')
<div class="px-6 py-4 space-y-6">

    <!-- Export Actions -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Télécharger les Rapports</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('reports.export.pdf') }}" 
               class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Télécharger PDF
            </a>
            <a href="{{ route('reports.export.excel') }}" 
               class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Télécharger Excel
            </a>
            <a href="{{ route('reports.export.csv') }}" 
               class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Télécharger CSV
            </a>
            <button onclick="window.print()" 
                    class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H9a2 2 0 01-2-2v-4a2 2 0 012-2h6a2 2 0 012 2v4a2 2 0 01-2 2zm0 0h2a2 2 0 002-2v-4a2 2 0 00-2-2m0 0H9m0 0V7a1 1 0 011-1h2a1 1 0 011 1v4"></path>
                </svg>
                Imprimer
            </button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Temps Moyen -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Temps Moyen de Résolution</p>
                    <p class="text-3xl font-bold text-white mt-2">
                        {{ $tempsMoyenResolution !== null ? number_format($tempsMoyenResolution, 1) . 'h' : '—' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Satisfaction -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Satisfaction Moyenne</p>
                    <p class="text-3xl font-bold text-yellow-400 mt-2">
                        {{ $tauxSatisfaction?->moyenne !== null ? number_format($tauxSatisfaction->moyenne, 1) . '/5' : '—' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Dans les délais -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Dans les Délais</p>
                    <p class="text-3xl font-bold text-green-400 mt-2">
                        {{ number_format($respectSla['dans_les_delais']) }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- SLA Dépassés -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">SLA Dépassés</p>
                    <p class="text-3xl font-bold text-red-400 mt-2">
                        {{ number_format($respectSla['sla_depasse']) }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-red-600/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M6.343 3.665c.886-.887 2.318-.887 3.536 0l8.94 8.94c1.218 1.218 1.218 2.65 0 3.868l-8.94 8.94c-1.218 1.218-2.65 1.218-3.868 0l-8.94-8.94c-1.218-1.218-1.218-2.65 0-3.868l8.94-8.94z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tickets par Mois -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Tickets par Mois</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="chart-month"></canvas>
            </div>
        </div>

        <!-- Tickets par Site -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Tickets par Site</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="chart-site"></canvas>
            </div>
        </div>

        <!-- Tickets par Catégorie -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Tickets par Catégorie</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="chart-category"></canvas>
            </div>
        </div>

        <!-- Performance Techniciens -->
        <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Performance des Techniciens</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-dark-900/50 border-b border-dark-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-300">Technicien</th>
                            <th class="px-4 py-2 text-center text-gray-300">Traités</th>
                            <th class="px-4 py-2 text-center text-gray-300">Temps Moyen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700">
                        @forelse($performanceTechniciens as $tech)
                        <tr class="hover:bg-dark-700/30 transition">
                            <td class="px-4 py-3 text-white">{{ $tech->technicien }}</td>
                            <td class="px-4 py-3 text-center text-gray-300">{{ $tech->total_traites }}</td>
                            <td class="px-4 py-3 text-center text-gray-300">
                                {{ $tech->temps_moyen_heures !== null ? number_format($tech->temps_moyen_heures, 1) . 'h' : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-center text-gray-500">Aucune donnée disponible</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent History -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Historique Récent</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-dark-900/50 border-b border-dark-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-gray-300">Date</th>
                        <th class="px-4 py-2 text-left text-gray-300">Ticket</th>
                        <th class="px-4 py-2 text-left text-gray-300">Action</th>
                        <th class="px-4 py-2 text-left text-gray-300">Utilisateur</th>
                        <th class="px-4 py-2 text-left text-gray-300">Détail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-700">
                    @forelse($historiques as $historique)
                    <tr class="hover:bg-dark-700/30 transition">
                        <td class="px-4 py-3 text-gray-400 whitespace-nowrap">
                            {{ $historique->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('tickets.show', $historique->ticket) }}" 
                               class="text-primary-400 hover:text-primary-300 font-mono text-sm">
                                {{ $historique->ticket?->reference ?? '—' }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-white">{{ $historique->action }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ $historique->utilisateur?->name ?? 'Système' }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $historique->ancienne_valeur ?? '—' }} → {{ $historique->nouvelle_valeur ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-center text-gray-500">Aucun historique</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartColors = {
        primary: '#f59e0b',
        success: '#10b981',
        danger: '#ef4444',
        info: '#3b82f6',
        warning: '#f59e0b',
    };

    // Charts configuration
    const monthCtx = document.getElementById('chart-month');
    if (monthCtx) {
        new Chart(monthCtx, {
            type: 'line',
            data: {
                labels: @json($ticketsParMois->pluck('periode')),
                datasets: [{
                    label: 'Tickets',
                    data: @json($ticketsParMois->pluck('total')),
                    borderColor: chartColors.primary,
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const siteCtx = document.getElementById('chart-site');
    if (siteCtx) {
        new Chart(siteCtx, {
            type: 'bar',
            data: {
                labels: @json($ticketsParSite->pluck('site')),
                datasets: [{
                    label: 'Tickets',
                    data: @json($ticketsParSite->pluck('total')),
                    backgroundColor: [chartColors.primary, chartColors.info, chartColors.success, chartColors.warning],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const categoryCtx = document.getElementById('chart-category');
    if (categoryCtx) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: @json($ticketsParCategorie->pluck('categorie')),
                datasets: [{
                    data: @json($ticketsParCategorie->pluck('total')),
                    backgroundColor: [
                        '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6',
                        '#ec4899', '#f97316', '#06b6d4'
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#d1d5db' } }
                }
            }
        });
    }
</script>
@endsection
