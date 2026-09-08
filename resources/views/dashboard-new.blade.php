@extends('layouts.app-new')

@section('titre', 'Tableau de Bord')

@section('contenu')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stats Cards -->
        <div class="card">
            <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 48px; height: 48px; background-color: var(--accent-200); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="tickets" size="lg" style="color: var(--accent-600);" />
                </div>
                <div>
                    <p style="margin: 0; font-size: var(--font-size-xs); color: var(--text-500); text-transform: uppercase; letter-spacing: 0.05em;">Tickets Ouverts</p>
                    <p style="margin: 0; font-size: var(--font-size-xl); font-weight: 700; color: var(--text-900);">
                        {{ \App\Models\Ticket::where('statut_id', '!=', 7)->count() ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 48px; height: 48px; background-color: rgba(16, 185, 129, 0.1); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="status-resolved" size="lg" style="color: var(--status-success);" />
                </div>
                <div>
                    <p style="margin: 0; font-size: var(--font-size-xs); color: var(--text-500); text-transform: uppercase; letter-spacing: 0.05em;">Tickets Résolus</p>
                    <p style="margin: 0; font-size: var(--font-size-xl); font-weight: 700; color: var(--text-900);">
                        {{ \App\Models\Ticket::where('statut_id', 6)->count() ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 48px; height: 48px; background-color: var(--accent-200); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="assets" size="lg" style="color: var(--accent-600);" />
                </div>
                <div>
                    <p style="margin: 0; font-size: var(--font-size-xs); color: var(--text-500); text-transform: uppercase; letter-spacing: 0.05em;">Actifs</p>
                    <p style="margin: 0; font-size: var(--font-size-xl); font-weight: 700; color: var(--text-900);">
                        {{ \App\Models\Asset::count() ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 48px; height: 48px; background-color: rgba(217, 119, 6, 0.1); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="alert" size="lg" style="color: var(--status-warning);" />
                </div>
                <div>
                    <p style="margin: 0; font-size: var(--font-size-xs); color: var(--text-500); text-transform: uppercase; letter-spacing: 0.05em;">Alertes</p>
                    <p style="margin: 0; font-size: var(--font-size-xl); font-weight: 700; color: var(--text-900);">
                        {{ \App\Models\Ticket::whereIn('priorite_id', [1, 2])->count() ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="margin: 0 0 var(--spacing-md) 0; font-size: var(--font-size-lg); font-weight: 600; color: var(--text-900);">
            Bienvenue dans le Système de Gestion Intégré Néré Mining
        </h3>
        <p style="margin: 0; color: var(--text-600); line-height: 1.6;">
            Ce portail centralise la gestion complète des opérations minières : tickets de maintenance, gestion des actifs, 
            planification des équipes, conformité HSE et rapports de production. Accédez aux modules selon vos permissions.
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--spacing-lg); margin-top: var(--spacing-lg);">
            @can('tickets.view')
                <a href="{{ route('tickets.index') }}" style="padding: var(--spacing-lg); background-color: var(--surface-light); border-radius: var(--radius-lg); border: 1px solid var(--border-200); text-decoration: none; transition: all var(--transition-fast); display: flex; align-items: center; gap: var(--spacing-md);">
                    <div style="width: 40px; height: 40px; background-color: var(--accent-200); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <x-icon name="tickets" style="color: var(--accent-600);" />
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: var(--text-900);">Tickets</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: var(--font-size-sm); color: var(--text-600);">Gérer les demandes et incidents</p>
                    </div>
                </a>
            @endcan

            @can('assets.view')
                <a href="{{ route('assets.index') }}" style="padding: var(--spacing-lg); background-color: var(--surface-light); border-radius: var(--radius-lg); border: 1px solid var(--border-200); text-decoration: none; transition: all var(--transition-fast); display: flex; align-items: center; gap: var(--spacing-md);">
                    <div style="width: 40px; height: 40px; background-color: var(--accent-200); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <x-icon name="assets" style="color: var(--accent-600);" />
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: var(--text-900);">Actifs</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: var(--font-size-sm); color: var(--text-600);">Suivi des équipements</p>
                    </div>
                </a>
            @endcan

            @can('safety.view')
                <a href="#" style="padding: var(--spacing-lg); background-color: var(--surface-light); border-radius: var(--radius-lg); border: 1px solid var(--border-200); text-decoration: none; transition: all var(--transition-fast); display: flex; align-items: center; gap: var(--spacing-md);">
                    <div style="width: 40px; height: 40px; background-color: rgba(220, 38, 38, 0.1); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <x-icon name="safety" style="color: var(--status-critical);" />
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: var(--text-900);">Sécurité</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: var(--font-size-sm); color: var(--text-600);">Incidents et conformité HSE</p>
                    </div>
                </a>
            @endcan

            @can('reports.view')
                <a href="{{ route('reports.index') }}" style="padding: var(--spacing-lg); background-color: var(--surface-light); border-radius: var(--radius-lg); border: 1px solid var(--border-200); text-decoration: none; transition: all var(--transition-fast); display: flex; align-items: center; gap: var(--spacing-md);">
                    <div style="width: 40px; height: 40px; background-color: var(--accent-200); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <x-icon name="reports" style="color: var(--accent-600);" />
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 600; color: var(--text-900);">Rapports</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: var(--font-size-sm); color: var(--text-600);">Statistiques et analyses</p>
                    </div>
                </a>
            @endcan
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <h3 style="margin: 0 0 var(--spacing-lg) 0; font-size: var(--font-size-lg); font-weight: 600; color: var(--text-900);">
            Activité Récente
        </h3>
        
        @forelse(\App\Models\Ticket::latest()->take(5)->get() as $ticket)
            <div style="padding: var(--spacing-md) 0; border-bottom: 1px solid var(--border-200); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="margin: 0; font-weight: 500; color: var(--text-900);">{{ $ticket->titre }}</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: var(--font-size-sm); color: var(--text-500);">{{ $ticket->created_at->diffForHumans() }}</p>
                </div>
                <x-status-badge :status="$ticket->statut?->slug ?? 'new'" type="status" />
            </div>
        @empty
            <p style="margin: 0; color: var(--text-500); font-size: var(--font-size-sm);">Aucune activité récente</p>
        @endforelse
    </div>
@endsection
