@extends('layouts.app')

@section('titre', 'Tableau de bord')

@section('styles')
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .kpi {
            background: var(--graphite-card);
            border: 1px solid var(--graphite-line);
            border-radius: 12px;
            padding: 1.15rem 1.25rem;
            position: relative;
            overflow: hidden;
        }

        .kpi::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--kpi-accent, var(--ambre));
        }

        .kpi-label {
            font-size: 0.78rem;
            color: var(--texte-att);
            margin-bottom: 0.45rem;
        }

        .kpi-valeur {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .kpi.critique { --kpi-accent: var(--rouge); }
        .kpi.sla { --kpi-accent: #e07b39; }
        .kpi.vert { --kpi-accent: var(--vert); }
        .kpi.bleu { --kpi-accent: var(--bleu); }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .chart-card {
            background: var(--graphite-card);
            border: 1px solid var(--graphite-line);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .chart-card.full {
            grid-column: 1 / -1;
        }

        .chart-card h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0 0 1rem;
            color: var(--texte-clair);
        }

        .chart-wrap {
            position: relative;
            height: 260px;
        }

        .chart-wrap.tall {
            height: 300px;
        }

        @media (max-width: 860px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
@endsection

@section('contenu')

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-label">Total tickets</div>
            <div class="kpi-valeur">{{ number_format($stats['total_tickets']) }}</div>
        </div>
        <div class="kpi bleu">
            <div class="kpi-label">Tickets ouverts</div>
            <div class="kpi-valeur">{{ number_format($stats['tickets_ouverts']) }}</div>
        </div>
        <div class="kpi critique">
            <div class="kpi-label">Critiques ouverts</div>
            <div class="kpi-valeur">{{ number_format($stats['tickets_critiques']) }}</div>
        </div>
        <div class="kpi sla">
            <div class="kpi-label">SLA dépassés</div>
            <div class="kpi-valeur">{{ number_format($stats['tickets_sla_depasse']) }}</div>
        </div>
        <div class="kpi vert">
            <div class="kpi-label">Techniciens disponibles</div>
            <div class="kpi-valeur">{{ number_format($stats['techniciens_disponibles']) }}</div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card">
            <h2>Tickets par statut</h2>
            <div class="chart-wrap">
                <canvas id="chart-statut"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h2>Tickets par priorité</h2>
            <div class="chart-wrap">
                <canvas id="chart-priorite"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h2>Tickets par site</h2>
            <div class="chart-wrap">
                <canvas id="chart-site"></canvas>
            </div>
        </div>

        <div class="chart-card full">
            <h2>Évolution mensuelle (12 derniers mois)</h2>
            <div class="chart-wrap tall">
                <canvas id="chart-evolution"></canvas>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.color = '#a9aeb4';
        Chart.defaults.borderColor = '#383f47';
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

        const palette = ['#c8963e', '#e0b563', '#3fa66b', '#4a90d9', '#d64545', '#9b7fd4', '#e07b39'];

        const doughnutDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' },
                },
            },
        };

        const barDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
            },
        };

        new Chart(document.getElementById('chart-statut'), {
            type: 'doughnut',
            data: {
                labels: @json($ticketsParStatut->keys()),
                datasets: [{
                    data: @json($ticketsParStatut->values()),
                    backgroundColor: palette,
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: doughnutDefaults,
        });

        new Chart(document.getElementById('chart-priorite'), {
            type: 'bar',
            data: {
                labels: @json($ticketsParPriorite->keys()),
                datasets: [{
                    data: @json($ticketsParPriorite->values()),
                    backgroundColor: palette.map(c => c + 'cc'),
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            },
            options: barDefaults,
        });

        new Chart(document.getElementById('chart-site'), {
            type: 'bar',
            data: {
                labels: @json($ticketsParSite->keys()),
                datasets: [{
                    data: @json($ticketsParSite->values()),
                    backgroundColor: '#c8963e',
                    borderRadius: 6,
                }],
            },
            options: {
                ...barDefaults,
                indexAxis: 'y',
            },
        });

        new Chart(document.getElementById('chart-evolution'), {
            type: 'line',
            data: {
                labels: @json($evolutionMensuelle->keys()),
                datasets: [{
                    label: 'Tickets créés',
                    data: @json($evolutionMensuelle->values()),
                    borderColor: '#c8963e',
                    backgroundColor: 'rgba(200,150,62,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#c8963e',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    </script>
@endpush
