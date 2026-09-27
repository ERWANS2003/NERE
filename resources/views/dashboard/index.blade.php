@extends('layouts.portal')

@section('titre', 'Tableau de bord')

@section('styles')
    <style>
        /* Dashboard Header */
        .portail-intro {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            align-items: flex-end;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .sur-titre {
            margin: 0 0 0.5rem;
            color: #ffd700;
            font: 500 0.7rem 'Open Sans', sans-serif;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .portail-intro h2 {
            margin: 0;
            font: 700 clamp(1.5rem, 3vw, 2.35rem) 'Open Sans', sans-serif;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .portail-intro p {
            max-width: 560px;
            color: rgba(255, 255, 255, 0.7);
            margin: 0.5rem 0 0;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .service-card {
            position: relative;
            min-height: 150px;
            padding: 1.25rem;
            background: rgba(0, 0, 0, 0.24);
            border: 1px solid rgba(255, 215, 0, 0.1);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .service-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 6px 16px rgba(255, 215, 0, 0.15);
        }

        .service-card .service-count {
            position: absolute;
            right: 1.25rem;
            top: 1.25rem;
            color: #ffd700;
            font: 700 1.5rem 'Open Sans', sans-serif;
        }

        .service-card .service-icon {
            color: #ffd700;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 0.05em;
        }

        .service-card h3 {
            margin: 0.5rem 0 0.3rem;
            font: 600 1.05rem 'Open Sans', sans-serif;
            color: #fff;
        }

        .service-card p {
            margin: 0;
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.8rem;
        }

        .service-card .service-categories {
            margin-top: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            line-height: 1.5;
        }

        /* Special KPIs */
        .special-kpis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin: 0 0 2rem;
        }

        .special-kpi {
            background: rgba(0, 0, 0, 0.24);
            border: 1px solid rgba(255, 215, 0, 0.1);
            border-radius: 10px;
            padding: 1.25rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.6s ease-out;
        }

        .special-kpi h3 {
            margin: 0 0 1rem;
            font: 600 0.95rem 'Open Sans', sans-serif;
            color: #ffd700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.85rem;
        }

        .special-kpi-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.85rem;
        }

        .special-kpi-row:last-child {
            border-bottom: 0;
        }

        .special-kpi-row span:first-child {
            color: rgba(255, 255, 255, 0.7);
        }

        .special-kpi-row span:last-child {
            color: #fff;
            font-weight: 600;
        }

        /* KPI Grid - Using stat-card classes */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .chart-card {
            background: rgba(0, 0, 0, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.6s ease-out;
        }

        .chart-card.full {
            grid-column: 1 / -1;
        }

        .chart-card h2 {
            font: 600 1rem 'Open Sans', sans-serif;
            margin: 0 0 1.25rem;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .chart-wrap {
            position: relative;
            height: 260px;
        }

        .chart-wrap.tall {
            height: 300px;
        }

        /* Recent Tickets */
        .derniers-tickets {
            grid-column: 1 / -1;
        }

        .ticket-ligne {
            display: grid;
            grid-template-columns: 1.1fr 2fr 1fr 1fr 1fr;
            gap: 0.75rem;
            align-items: center;
            padding: 0.875rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.85rem;
            transition: background-color 0.15s ease;
            text-decoration: none;
            color: inherit;
        }

        .ticket-ligne:hover {
            background-color: rgba(255, 215, 0, 0.03);
        }

        .ticket-ligne:last-child {
            border-bottom: none;
        }

        .ticket-ligne .reference {
            color: #ffd700;
            font-family: 'Open Sans', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .ticket-ligne .secondaire {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
        }

        .ticket-ligne .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 700px) {
            .ticket-ligne {
                grid-template-columns: 1fr 1fr;
            }
            .ticket-ligne > :nth-child(2) {
                grid-column: 1 / -1;
                grid-row: 1;
            }
        }

        @media (max-width: 860px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .portail-intro {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection

@section('contenu')

    <div class="portail-intro">
        <div>
            <p class="sur-titre">Néré Mining · portail de services</p>
            <h2>Que souhaitez-vous faire ?</h2>
            <p>Une demande unique, dirigée automatiquement vers le bon service et suivie jusqu'à sa résolution.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn-mining-primary">Créer une demande</a>
    </div>

    <div class="services-grid">
        @foreach ($departements as $departement)
            @php($categoriesService = $departement->teams->flatMap->categories->pluck('nom')->take(4))
            <a class="service-card" href="{{ route('tickets.create') }}?departement={{ $departement->id }}">
                <span class="service-count">{{ $departement->tickets_count }}</span>
                <div class="service-icon">{{ strtoupper(substr($departement->nom, 0, 2)) }}</div>
                <h3>{{ $departement->nom }}</h3>
                <p>{{ $departement->teams_count }} équipe{{ $departement->teams_count > 1 ? 's' : '' }} de traitement</p>
                <div class="service-categories">{{ $categoriesService->implode(' · ') }}</div>
            </a>
        @endforeach
    </div>

    <div class="special-kpis">
        @foreach ($serviceKpis as $service => $indicateurs)
            @if ($departements->contains('nom', $service) || auth()->user()->hasRole('admin'))
                <div class="special-kpi"><h3>{{ $service }} · indicateurs</h3>@foreach ($indicateurs as $libelle => $valeur)<div class="special-kpi-row"><span>{{ $libelle }}</span><span>{{ number_format($valeur) }}</span></div>@endforeach</div>
            @endif
        @endforeach
    </div>

    <div class="kpi-grid">
        <div class="stat-card stat-gold">
            <div class="stat-label">Total tickets</div>
            <div class="stat-value">{{ number_format($stats['total_tickets']) }}</div>
        </div>
        <div class="stat-card stat-bleu">
            <div class="stat-label">Tickets ouverts</div>
            <div class="stat-value">{{ number_format($stats['tickets_ouverts']) }}</div>
        </div>
        <div class="stat-card stat-crimson">
            <div class="stat-label">En attente</div>
            <div class="stat-value">{{ number_format($stats['tickets_en_attente']) }}</div>
        </div>
        <div class="stat-card stat-verde">
            <div class="stat-label">Résolus</div>
            <div class="stat-value">{{ number_format($stats['tickets_resolus']) }}</div>
        </div>
        <div class="stat-card stat-crimson">
            <div class="stat-label">Critiques ouverts</div>
            <div class="stat-value">{{ number_format($stats['tickets_critiques']) }}</div>
        </div>
        <div class="stat-card stat-crimson">
            <div class="stat-label">SLA dépassés</div>
            <div class="stat-value">{{ number_format($stats['tickets_sla_depasse']) }}</div>
        </div>
        <div class="stat-card stat-verde">
            <div class="stat-label">Techniciens disponibles</div>
            <div class="stat-value">{{ number_format($stats['techniciens_disponibles']) }}</div>
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

        <div class="chart-card derniers-tickets">
            <h2>Derniers tickets</h2>
            @forelse ($derniersTickets as $ticket)
                <a class="ticket-ligne" href="{{ route('tickets.show', $ticket) }}">
                    <span class="reference">{{ $ticket->reference }}</span>
                    <span>{{ Str::limit($ticket->titre, 55) }}</span>
                    <span class="secondaire">{{ $ticket->site?->nom ?? 'Tous sites' }}</span>
                    <span class="badge" style="background:{{ $ticket->statut?->couleur ?? '#a9aeb4' }}22;color:{{ $ticket->statut?->couleur ?? '#a9aeb4' }};">{{ $ticket->statut?->nom ?? '—' }}</span>
                    <span class="secondaire">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                </a>
            @empty
                <p style="color:var(--texte-att);margin:0;">Aucun ticket enregistré.</p>
            @endforelse
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        // Light Blue chart defaults
        Chart.defaults.color = 'rgba(255, 255, 255, 0.7)';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        Chart.defaults.font.family = "'Open Sans', system-ui, sans-serif";
        Chart.defaults.font.size = 13;

        // Néré Mining color palette
        const palette = [
            '#ffd700',  // Gold
            '#d32f2f',  // Crimson
            '#ffa726',  // Orange
            '#66bb6a',  // Green
            '#42a5f5',  // Blue
            '#ab47bc'   // Purple
        ];

        const doughnutDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: { size: 12 }
                    },
                },
            },
        };

        const barDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false, borderColor: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, color: 'rgba(255, 255, 255, 0.7)' },
                    grid: { color: 'rgba(255, 255, 255, 0.05)' }
                },
            },
        };

        new Chart(document.getElementById('chart-statut'), {
            type: 'doughnut',
            data: {
                labels: @json($ticketsParStatut->keys()),
                datasets: [{
                    data: @json($ticketsParStatut->values()),
                    backgroundColor: palette,
                    borderWidth: 2,
                    borderColor: 'rgba(0, 0, 0, 0.3)',
                    hoverOffset: 8,
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
                    backgroundColor: palette.map(c => c + 'dd'),
                    borderRadius: 8,
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
                    backgroundColor: '#ffd700',
                    borderRadius: 8,
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
                    borderColor: '#ffd700',
                    backgroundColor: 'rgba(255, 215, 0, 0.15)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffd700',
                    pointBorderColor: 'rgba(0, 0, 0, 0.3)',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#ffd700',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffd700',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255, 215, 0, 0.3)',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Tickets: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, borderColor: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: 'rgba(255, 255, 255, 0.7)' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                },
            },
        });
    </script>
@endpush
