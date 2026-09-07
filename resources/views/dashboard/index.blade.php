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
        .kpi.sla { --kpi-accent: #d9362e; }
        .kpi.vert { --kpi-accent: var(--vert); }
        .kpi.bleu { --kpi-accent: var(--bleu); }

        .portail-intro { display:flex; justify-content:space-between; gap:1.5rem; align-items:flex-end; margin-bottom:1.5rem; }
        .sur-titre { margin:0 0 .4rem; color:var(--ambre-clair); font:500 .68rem 'JetBrains Mono', monospace; letter-spacing:.1em; text-transform:uppercase; }
        .portail-intro h2 { margin:0; font:700 clamp(1.5rem, 3vw, 2.35rem) 'Space Grotesk', sans-serif; color:var(--ivoire); }
        .portail-intro p { max-width:560px; color:var(--texte-att); margin:.5rem 0 0; line-height:1.6; }
        .services-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:.85rem; margin-bottom:1.75rem; }
        .service-card { position:relative; min-height:145px; padding:1.05rem; border:1px solid var(--graphite-line); border-radius:10px; background:linear-gradient(145deg,#2a323b,#222830); transition:transform .18s,border-color .18s; }
        .service-card:hover { transform:translateY(-2px); border-color:var(--ambre); }
        .service-card h3 { margin:.6rem 0 .3rem; font:600 1rem 'Space Grotesk',sans-serif; }
        .service-card p { margin:0; color:var(--texte-att); font-size:.75rem; }
        .service-card .service-count { position:absolute; right:1rem; top:1rem; color:var(--ambre-clair); font:700 1.3rem 'Space Grotesk',sans-serif; }
        .service-card .service-categories { margin-top:.8rem; color:#c8cbd0; font-size:.72rem; line-height:1.5; }
        .special-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; margin:0 0 1.75rem; }
        .special-kpi { background:var(--graphite-soft); border:1px solid var(--graphite-line); border-radius:10px; padding:1rem; }
        .special-kpi h3 { margin:0 0 .75rem; font:600 .9rem 'Space Grotesk',sans-serif; color:var(--ambre-clair); }
        .special-kpi-row { display:flex; justify-content:space-between; gap:1rem; padding:.35rem 0; border-bottom:1px solid var(--graphite-line); font-size:.78rem; }
        .special-kpi-row:last-child { border-bottom:0; }
        .special-kpi-row span:last-child { color:var(--texte-clair); font-weight:600; }

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

        .derniers-tickets {
            grid-column: 1 / -1;
        }

        .ticket-ligne {
            display: grid;
            grid-template-columns: 1.1fr 2fr 1fr 1fr 1fr;
            gap: .75rem;
            align-items: center;
            padding: .75rem 0;
            border-bottom: 1px solid var(--graphite-line);
            font-size: .82rem;
        }

        .ticket-ligne:last-child { border-bottom: none; }
        .ticket-ligne .reference { color: var(--ambre-clair); font-family: 'JetBrains Mono', monospace; font-size: .75rem; }
        .ticket-ligne .secondaire { color: var(--texte-att); font-size: .76rem; }

        @media (max-width: 700px) {
            .ticket-ligne { grid-template-columns: 1fr 1fr; }
            .ticket-ligne > :nth-child(2) { grid-column: 1 / -1; grid-row: 1; }
        }

        @media (max-width: 860px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
@endsection

@section('contenu')

    <div class="portail-intro">
        <div>
            <p class="sur-titre">Néré Mining · portail de services</p>
            <h2>Que souhaitez-vous faire ?</h2>
            <p>Une demande unique, dirigée automatiquement vers le bon service et suivie jusqu'à sa résolution.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn btn-primaire">Créer une demande</a>
    </div>

    <div class="services-grid">
        @foreach ($departements as $departement)
            @php($categoriesService = $departement->teams->flatMap->categories->pluck('nom')->take(4))
            <a class="service-card" href="{{ route('tickets.create') }}?departement={{ $departement->id }}">
                <span class="service-count">{{ $departement->tickets_count }}</span>
                <div style="color:var(--ambre-clair);font-size:1.2rem;">{{ strtoupper(substr($departement->nom, 0, 2)) }}</div>
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
        <div class="kpi">
            <div class="kpi-label">Total tickets</div>
            <div class="kpi-valeur">{{ number_format($stats['total_tickets']) }}</div>
        </div>
        <div class="kpi bleu">
            <div class="kpi-label">Tickets ouverts</div>
            <div class="kpi-valeur">{{ number_format($stats['tickets_ouverts']) }}</div>
        </div>
        <div class="kpi sla">
            <div class="kpi-label">En attente</div>
            <div class="kpi-valeur">{{ number_format($stats['tickets_en_attente']) }}</div>
        </div>
        <div class="kpi vert">
            <div class="kpi-label">Résolus</div>
            <div class="kpi-valeur">{{ number_format($stats['tickets_resolus']) }}</div>
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
        Chart.defaults.color = '#a9aeb4';
        Chart.defaults.borderColor = '#383f47';
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

        const palette = ['#e0a52f', '#ffc247', '#d9362e', '#8f2020', '#f0c96a', '#b94b35'];

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
                    backgroundColor: '#e0a52f',
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
                    borderColor: '#e0a52f',
                    backgroundColor: 'rgba(200,150,62,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#e0a52f',
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
