@extends('layouts.portal')

@section('titre', 'Tickets')
@section('sous-titre', auth()->user()->hasRole('demandeur') ? 'Vos demandes et leur avancement' : 'Centre de service et de support')

@section('styles')
    <style>
        .page-header {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out;
        }

        @media (min-width: 768px) {
            .page-header {
                flex-direction: row;
                align-items: flex-end;
                justify-content: space-between;
            }
        }

        .page-header h1 {
            font: 700 clamp(1.75rem, 3vw, 2rem) var(--font-sans);
            color: #fff;
            margin: 0.5rem 0 0;
            letter-spacing: -0.02em;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.7);
            margin-top: 0.5rem;
        }

        .kpis-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out 0.1s;
            animation-fill-mode: both;
        }

        @media (min-width: 1280px) {
            .kpis-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .filters-card {
            background: rgba(0, 0, 0, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.6s ease-out 0.2s;
            animation-fill-mode: both;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            align-items: end;
        }

        @media (min-width: 640px) {
            .filters-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .filters-grid {
                grid-template-columns: 2fr 1fr 1fr 1fr auto;
            }
        }

        .form-group label {
            display: block;
            font: 700 0.7rem var(--font-sans);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #ffd700;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            border-color: rgba(255, 215, 0, 0.4);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 0.75rem;
            width: 1rem;
            height: 1rem;
            color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper .form-control {
            padding-left: 2.5rem;
        }

        .tickets-card {
            background: rgba(0, 0, 0, 0.24);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out 0.3s;
            animation-fill-mode: both;
        }

        .tickets-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        @media (min-width: 640px) {
            .tickets-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .tickets-header h2 {
            font: 600 1.1rem var(--font-sans);
            color: #fff;
            margin: 0;
        }

        .tickets-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            margin: 0.25rem 0 0;
        }

        .ticket-row {
            display: block;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: background-color 0.15s ease;
            text-decoration: none;
            color: inherit;
        }

        .ticket-row:hover {
            background-color: rgba(255, 215, 0, 0.05);
        }

        .ticket-row:last-child {
            border-bottom: none;
        }

        .ticket-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        @media (min-width: 1024px) {
            .ticket-content {
                flex-direction: row;
                align-items: center;
                gap: 1.5rem;
            }
        }

        .ticket-ref {
            flex-shrink: 0;
        }

        @media (min-width: 1024px) {
            .ticket-ref {
                width: 8rem;
            }
        }

        .ticket-ref-code {
            font: 700 0.75rem var(--font-sans), monospace;
            color: #ffd700;
        }

        .ticket-ref-date {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 0.25rem;
        }

        .ticket-main {
            flex: 1;
            min-width: 0;
        }

        .ticket-title {
            font: 600 0.95rem var(--font-sans);
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ticket-description {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ticket-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        @media (min-width: 1024px) {
            .ticket-badges {
                width: 14rem;
            }
        }

        .ticket-department {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.5);
        }

        @media (min-width: 1024px) {
            .ticket-department {
                width: 10rem;
            }
        }

        .ticket-arrow {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .empty-state {
            padding: 4rem 1.5rem;
            text-align: center;
        }

        .empty-icon {
            width: 3.5rem;
            height: 3.5rem;
            margin: 0 auto;
            border-radius: 1rem;
            background: rgba(255, 215, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 215, 0, 0.6);
        }

        .empty-icon svg {
            width: 1.75rem;
            height: 1.75rem;
        }

        .empty-state h3 {
            font: 700 1.05rem var(--font-sans);
            color: #fff;
            margin: 1rem 0 0;
        }

        .empty-state p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 0.25rem;
        }

        .link-advanced {
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffd700;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }

        .link-advanced:hover {
            opacity: 0.8;
        }

        .link-reset {
            display: inline-block;
            margin-top: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffd700;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }

        .link-reset:hover {
            opacity: 0.8;
        }

        .opacity-60 {
            opacity: 0.6;
        }
    </style>
@endsection

@section('contenu')
<div style="padding: 1.5rem;">
    <section class="page-header">
        <div>
            <p class="sur-titre">Centre de service</p>
            <h1>{{ auth()->user()->hasRole('demandeur') ? 'Mes demandes' : 'File de tickets' }}</h1>
            <p>Recherchez, priorisez et suivez les demandes de vos Ã©quipes.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn-mining-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nouveau ticket
        </a>
    </section>

    <section class="kpis-row">
        @php($ticketKpis = [['label' => 'Total', 'value' => $ticketStats['total'], 'class' => 'stat-gold'], ['label' => 'Ouverts', 'value' => $ticketStats['ouverts'], 'class' => 'stat-bleu'], ['label' => 'En cours', 'value' => $ticketStats['en_cours'], 'class' => 'stat-gold'], ['label' => 'Urgents', 'value' => $ticketStats['urgents'], 'class' => 'stat-crimson']])
        @foreach($ticketKpis as $kpi)
            <div class="stat-card {{ $kpi['class'] }}">
                <div class="stat-label">{{ $kpi['label'] }}</div>
                <div class="stat-value">{{ number_format($kpi['value']) }}</div>
            </div>
        @endforeach
    </section>

    <section class="filters-card">
        <form method="GET" action="{{ route('tickets.index') }}" class="filters-grid">
            <div class="form-group">
                <label for="q">Recherche</label>
                <div class="search-wrapper">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0a7 7 0 0114 0z"></path></svg>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="RÃ©fÃ©rence, titre ou description" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut" class="form-control">
                    <option value="">Tous</option>
                    @foreach($statuts as $statut)
                        <option value="{{ $statut->id }}" @selected(request('statut') == $statut->id)>{{ $statut->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="priorite">PrioritÃ©</label>
                <select id="priorite" name="priorite" class="form-control">
                    <option value="">Toutes</option>
                    @foreach($priorites as $priorite)
                        <option value="{{ $priorite->id }}" @selected(request('priorite') == $priorite->id)>{{ $priorite->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="site">Site</label>
                <select id="site" name="site" class="form-control">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->nom }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-mining-primary" style="margin-top: 1.75rem;">Filtrer</button>
        </form>
        @if(request()->hasAny(['q', 'statut', 'priorite', 'categorie', 'site', 'departement']))
            <a href="{{ route('tickets.index') }}" class="link-reset">RÃ©initialiser les filtres</a>
        @endif
    </section>

    <section class="tickets-card">
        <div class="tickets-header">
            <div>
                <h2>Demandes enregistrÃ©es</h2>
                <p>{{ $tickets->total() }} rÃ©sultat{{ $tickets->total() > 1 ? 's' : '' }} Â· triÃ©s du plus rÃ©cent au plus ancien</p>
            </div>
            <a href="{{ route('search.index') }}" class="link-advanced">Recherche avancÃ©e</a>
        </div>
        @if($tickets->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3>Aucun ticket trouvÃ©</h3>
                <p>Modifiez vos filtres ou crÃ©ez une nouvelle demande.</p>
            </div>
        @else
            <div>
                @foreach($tickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="ticket-row {{ $ticket->trashed() ? 'opacity-60' : '' }}">
                        <div class="ticket-content">
                            <div class="ticket-ref">
                                <span class="ticket-ref-code">{{ $ticket->reference }}</span>
                                <p class="ticket-ref-date">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="ticket-main">
                                <p class="ticket-title">{{ $ticket->titre }}</p>
                                <p class="ticket-description">{{ Str::limit($ticket->description, 100) }}</p>
                            </div>
                            <div class="ticket-badges">
                                @if($ticket->statut)
                                    <span class="badge" style="background:{{ $ticket->statut->couleur }}22;color:{{ $ticket->statut->couleur }}">{{ $ticket->statut->nom }}</span>
                                @endif
                                @if($ticket->priorite)
                                    <span class="badge" style="background:{{ $ticket->priorite->couleur }}22;color:{{ $ticket->priorite->couleur }}">{{ $ticket->priorite->nom }}</span>
                                @endif
                            </div>
                            <div class="ticket-department">{{ $ticket->departement?->nom ?? 'PÃ©rimÃ¨tre gÃ©nÃ©ral' }}</div>
                            <svg class="ticket-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </a>
                @endforeach
            </div>
            @if($tickets->hasPages())
                <div style="padding: 1.25rem 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.08);">
                    {{ $tickets->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
