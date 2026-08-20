@extends('layouts.app')

@section('titre', 'Tickets')

@section('styles')
    @include('tickets.partials.styles')
@endsection

@section('contenu')

    <div class="page-actions">
        <p style="margin:0; color:var(--texte-att); font-size:0.88rem;">
            {{ $tickets->total() }} ticket{{ $tickets->total() > 1 ? 's' : '' }}
        </p>
        <a href="{{ route('tickets.create') }}" class="btn btn-primaire">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Nouveau ticket
        </a>
    </div>

    <form method="GET" action="{{ route('tickets.index') }}" class="carte filtres">
        <div class="champ">
            <label for="q">Recherche</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Référence, titre, description…">
        </div>
        <div class="champ">
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
                <option value="">Tous</option>
                @foreach ($statuts as $statut)
                    <option value="{{ $statut->id }}" @selected(request('statut') == $statut->id)>{{ $statut->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="priorite">Priorité</label>
            <select id="priorite" name="priorite">
                <option value="">Toutes</option>
                @foreach ($priorites as $priorite)
                    <option value="{{ $priorite->id }}" @selected(request('priorite') == $priorite->id)>{{ $priorite->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="categorie">Catégorie</label>
            <select id="categorie" name="categorie">
                <option value="">Toutes</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected(request('categorie') == $categorie->id)>{{ $categorie->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="champ">
            <label for="site">Site</label>
            <select id="site" name="site">
                <option value="">Tous</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->nom }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn btn-secondaire">Filtrer</button>
            @if (request()->hasAny(['q', 'statut', 'priorite', 'categorie', 'site']))
                <a href="{{ route('tickets.index') }}" class="btn btn-secondaire">Réinitialiser</a>
            @endif
        </div>
    </form>

    <div class="carte table-wrap">
        @if ($tickets->isEmpty())
            <div class="vide">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--texte-att)" stroke-width="1.5" style="opacity:0.5"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                <p>Aucun ticket trouvé.</p>
                <a href="{{ route('tickets.create') }}" class="btn btn-primaire">Créer un ticket</a>
            </div>
        @else
            <table class="tickets">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Titre</th>
                        <th>Statut</th>
                        <th>Priorité</th>
                        <th>Catégorie</th>
                        <th>Site</th>
                        <th>Technicien</th>
                        <th>Créé le</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="ref">{{ $ticket->reference }}</a>
                                @if ($ticket->sla_depasse || $ticket->estEnRetard())
                                    <span class="badge badge-sla" title="SLA dépassé">SLA</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" style="color:var(--texte-clair);">
                                    {{ Str::limit($ticket->titre, 50) }}
                                </a>
                            </td>
                            <td>
                                @if ($ticket->statut)
                                    <span class="badge" style="background:{{ $ticket->statut->couleur }}22; color:{{ $ticket->statut->couleur }}; border:1px solid {{ $ticket->statut->couleur }}44;">
                                        {{ $ticket->statut->nom }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($ticket->priorite)
                                    <span class="badge" style="background:{{ $ticket->priorite->couleur }}22; color:{{ $ticket->priorite->couleur }}; border:1px solid {{ $ticket->priorite->couleur }}44;">
                                        {{ $ticket->priorite->nom }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $ticket->categorie?->nom ?? '—' }}</td>
                            <td>{{ $ticket->site?->nom ?? '—' }}</td>
                            <td>{{ $ticket->technicien?->name ?? '—' }}</td>
                            <td style="color:var(--texte-att); white-space:nowrap;">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination">
                {{ $tickets->links('vendor.pagination.tickets') }}
            </div>
        @endif
    </div>

@endsection
