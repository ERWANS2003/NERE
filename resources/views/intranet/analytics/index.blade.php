@php
    $breadcrumbs = [
        ['label' => 'Analytics'],
    ];
    $iconesKpi = [
        'inbox' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
        'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'hourglass' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
@endphp

<x-intranet-layout title="Analytics" :breadcrumbs="$breadcrumbs">

    {{-- En-tête + filtre période --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Tableau de bord analytique</h1>
            <p class="text-graphite-600">{{ $scopeLabel }} · Suivi des demandes et des effectifs</p>
        </div>

        <div class="flex flex-col items-start gap-3 sm:items-end">
            <form method="GET" data-live-search class="flex items-center gap-2">
                <label for="periode" class="text-sm text-graphite-600">Période</label>
                <select id="periode" name="periode" class="champ py-2">
                    @foreach($periodes as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected($jours === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </form>
            <div class="flex flex-wrap items-center gap-2" data-auto-refresh data-interval="60000">
                <a href="{{ route('intranet.analytics.export.pdf', ['periode' => $jours]) }}" class="bouton-secondaire">
                    PDF
                </a>
                <a href="{{ route('intranet.analytics.export.xlsx', ['periode' => $jours]) }}" class="bouton-secondaire">
                    Excel
                </a>
                <a href="{{ route('intranet.analytics.export.csv', ['periode' => $jours]) }}" class="bouton-secondaire">
                    CSV
                </a>
                <a href="{{ route('intranet.analytics.export.json', ['periode' => $jours]) }}" class="bouton-secondaire">
                    JSON
                </a>
                <a href="{{ route('intranet.analytics', ['periode' => $jours]) }}" class="bouton-secondaire">
                    Actualiser
                </a>
                <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">
                    Suspendre l’actualisation
                </button>
                <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
            </div>
        </div>
    </div>

    @if($isAdmin)
        <section class="mb-8" aria-labelledby="analytics-overview-heading">
            <h2 id="analytics-overview-heading" class="mb-3 text-lg font-bold text-graphite-900">Vue d’ensemble de l’entreprise</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach([
                    ['label' => 'Départements actifs', 'value' => $overview['departements']],
                    ['label' => 'Employés actifs', 'value' => $overview['effectif']],
                    ['label' => 'Demandes enregistrées', 'value' => $overview['demandes']],
                ] as $item)
                    <div class="rounded-xl border border-graphite-200 bg-white p-5">
                        <p class="text-sm font-medium text-graphite-500">{{ $item['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-graphite-900">{{ number_format($item['value'], 0, ',', ' ') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- KPI cards --}}
    <h2 class="mb-3 text-lg font-bold text-graphite-900">Activité sur les {{ $periodes[$jours] }}</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach($kpis as $kpi)
            <div class="bg-white rounded-lg border border-graphite-200 p-5">
                <div class="flex items-start justify-between">
                    <div class="w-10 h-10 bg-nere-50 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconesKpi[$kpi['icon']] ?? $iconesKpi['inbox'] }}"/>
                        </svg>
                    </div>
                    @if(!empty($kpi['variation']))
                        <span class="flex items-center gap-1 text-xs font-semibold {{ $kpi['variation']['sens'] === 'hausse' ? 'text-green-600' : 'text-nere-600' }}">
                            {{ $kpi['variation']['sens'] === 'hausse' ? '▲' : '▼' }} {{ $kpi['variation']['pourcentage'] }} %
                        </span>
                    @endif
                </div>
                <div class="mt-4 text-3xl font-bold text-graphite-900">{{ $kpi['valeur'] }}</div>
                <div class="text-sm text-graphite-500 mt-1">{{ $kpi['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Graphiques --}}
    <div
        class="grid grid-cols-1 lg:grid-cols-2 gap-6"
        x-data="analyticsCharts(@js($charts))"
    >
        <div class="bg-white rounded-lg border border-graphite-200 p-6">
            <h2 class="font-bold text-graphite-900 mb-4">Demandes par semaine</h2>
            <div class="h-64"><canvas x-ref="parSemaine"></canvas></div>
        </div>

        <div class="bg-white rounded-lg border border-graphite-200 p-6">
            <h2 class="font-bold text-graphite-900 mb-4">Répartition par statut</h2>
            <div class="h-64"><canvas x-ref="parStatut"></canvas></div>
        </div>

        <div class="bg-white rounded-lg border border-graphite-200 p-6">
            <h2 class="font-bold text-graphite-900 mb-4">{{ $isAdmin ? 'Demandes par département' : 'Demandes dans vos départements' }}</h2>
            <div class="h-64"><canvas x-ref="parDepartement"></canvas></div>
        </div>

        <div class="bg-white rounded-lg border border-graphite-200 p-6">
            <h2 class="font-bold text-graphite-900 mb-4">{{ $isAdmin ? 'Effectifs par département' : 'Effectifs de vos départements' }}</h2>
            <div class="h-64"><canvas x-ref="effectifs"></canvas></div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Palette NÉRÉ réutilisée par les graphiques.
        const NERE = {
            rouge: '#c83530',
            or: '#f0b52b',
            graphite: '#515c66',
            vert: '#4f7f3a',
            rougeClair: 'rgba(200, 53, 48, 0.12)',
        };

        function analyticsCharts(data) {
            return {
                init() {
                    if (typeof window.Chart === 'undefined') {
                        console.error('Chart.js non chargé');
                        return;
                    }

                    // Demandes par semaine (ligne)
                    new window.Chart(this.$refs.parSemaine, {
                        type: 'line',
                        data: {
                            labels: data.parSemaine.labels,
                            datasets: [{
                                label: 'Demandes',
                                data: data.parSemaine.data,
                                borderColor: NERE.rouge,
                                backgroundColor: NERE.rougeClair,
                                fill: true,
                                tension: 0.3,
                            }],
                        },
                        options: this.baseOptions(),
                    });

                    // Répartition par statut (anneau)
                    new window.Chart(this.$refs.parStatut, {
                        type: 'doughnut',
                        data: {
                            labels: data.parStatut.labels,
                            datasets: [{
                                data: data.parStatut.data,
                                backgroundColor: [NERE.vert, NERE.rouge, NERE.or],
                            }],
                        },
                        options: { responsive: true, maintainAspectRatio: false, animation: false },
                    });

                    // Demandes par département (barres horizontales)
                    new window.Chart(this.$refs.parDepartement, {
                        type: 'bar',
                        data: {
                            labels: data.parDepartement.labels,
                            datasets: [{ label: 'Demandes', data: data.parDepartement.data, backgroundColor: NERE.rouge }],
                        },
                        options: { ...this.baseOptions(), indexAxis: 'y' },
                    });

                    // Effectifs par département (barres)
                    new window.Chart(this.$refs.effectifs, {
                        type: 'bar',
                        data: {
                            labels: data.effectifs.labels,
                            datasets: [{ label: 'Employés', data: data.effectifs.data, backgroundColor: NERE.graphite }],
                        },
                        options: this.baseOptions(),
                    });
                },
                baseOptions() {
                    return {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    };
                },
            };
        }
    </script>
    @endpush

</x-intranet-layout>
