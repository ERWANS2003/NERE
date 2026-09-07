@extends('layouts.app')

@section('titre', 'Tickets')

@section('styles')
    @include('tickets.partials.styles')
    .tickets-intro { display:flex; justify-content:space-between; align-items:flex-end; gap:1.5rem; margin-bottom:1rem; }
    .tickets-intro h2 { margin:0; font:700 clamp(1.45rem, 3vw, 2rem) 'Space Grotesk', sans-serif; color:var(--ivoire); }
    .tickets-intro p:last-child { margin:.45rem 0 0; color:var(--texte-att); font-size:.86rem; }
    .sur-titre { margin:0 0 .35rem; color:var(--ambre-clair); font:500 .68rem 'JetBrains Mono', monospace; letter-spacing:.1em; text-transform:uppercase; }
    .ticket-raccourcis { display:flex; gap:.5rem; overflow-x:auto; padding:.15rem 0 1.2rem; scrollbar-width:thin; }
    .raccourci { display:inline-flex; align-items:center; gap:.4rem; white-space:nowrap; padding:.48rem .7rem; border:1px solid var(--graphite-line); border-radius:999px; color:var(--texte-att); font-size:.76rem; transition:color .15s, border-color .15s, background .15s; }
    .raccourci:hover, .raccourci.active { color:var(--ambre-clair); border-color:rgba(224,165,47,.5); background:rgba(224,165,47,.1); }
    .raccourci span { color:var(--texte-clair); font-weight:600; }
    @media (max-width:700px) { .tickets-intro { align-items:flex-start; flex-direction:column; } .tickets-intro .btn { width:100%; justify-content:center; } }
@endsection

@section('contenu')

    <div class="tickets-intro">
        <div>
            <p class="sur-titre">Suivi des demandes</p>
            <h2>{{ auth()->user()->hasRole('demandeur') ? 'Mes demandes' : 'Demandes de mon service' }}</h2>
            <p>Retrouvez l'état de vos demandes et échangez avec l'équipe qui les traite.</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="btn btn-primaire">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Créer une demande
        </a>
    </div>

    <div class="ticket-raccourcis" aria-label="Filtrer par statut">
        <a href="{{ route('tickets.index') }}" class="raccourci {{ !request('statut') ? 'active' : '' }}">Toutes <span>{{ $tickets->total() }}</span></a>
        @foreach ($statuts as $statut)
            <a href="{{ route('tickets.index', ['statut' => $statut->id]) }}" class="raccourci {{ request('statut') == $statut->id ? 'active' : '' }}">{{ $statut->nom }}</a>
        @endforeach
    </div>

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
            <label for="departement">Service</label>
            <select id="departement" name="departement">
                <option value="">Tous</option>
                @foreach ($departements as $departement)
                    <option value="{{ $departement->id }}" @selected(request('departement') == $departement->id)>{{ $departement->nom }}</option>
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
        <div class="champ">
            <label for="archives">Archives / Traités</label>
            <select id="archives" name="archives">
                <option value="" @selected(request('archives') === '')>Actifs uniquement</option>
                <option value="avec" @selected(request('archives') === 'avec')>Actifs et archivés</option>
                <option value="uniquement" @selected(request('archives') === 'uniquement')>Archivés uniquement</option>
            </select>
        </div>
        <div class="champ">
            <label for="per_page">Par page</label>
            <select id="per_page" name="per_page">
                <option value="10" @selected($perPage == 10)>10 par page</option>
                <option value="20" @selected($perPage == 20)>20 par page</option>
                <option value="25" @selected($perPage == 25)>25 par page</option>
                <option value="50" @selected($perPage == 50)>50 par page</option>
                <option value="100" @selected($perPage == 100)>100 par page</option>
            </select>
        </div>
        <div style="display:flex; gap:0.5rem; align-items:flex-end;">
            <button type="submit" class="btn btn-secondaire">Filtrer</button>
            @if (request()->hasAny(['q', 'statut', 'priorite', 'departement', 'categorie', 'site', 'archives', 'per_page']))
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr style="{{ $ticket->trashed() ? 'opacity: 0.65; background: rgba(255,0,0,0.03);' : '' }}">
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="ref">{{ $ticket->reference }}</a>
                                @if ($ticket->trashed())
                                    <span class="badge" style="background:rgba(217,54,46,0.2);color:#f09090;">Archivé</span>
                                @endif
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
                            <td>
                                @if ($ticket->trashed())
                                    @if (auth()->user()->hasRole('admin'))
                                        <form method="POST" action="{{ route('tickets.restore', $ticket->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondaire" style="padding:.2rem .5rem; font-size:.75rem;">Restaurer</button>
                                        </form>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" style="display:inline;" onsubmit="return confirm('Archiver ce ticket ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn" style="padding:.2rem .5rem; font-size:.75rem; background:rgba(217,54,46,0.15); color:#f09090; border:1px solid rgba(217,54,46,0.3);">Archiver</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.25rem; flex-wrap:wrap; gap:1rem;">
                <div style="font-size:0.82rem; color:var(--texte-att);">
                    Affichage de {{ $tickets->firstItem() ?? 0 }} à {{ $tickets->lastItem() ?? 0 }} sur {{ $tickets->total() }} résultats
                </div>
                <div class="pagination">
                    {{ $tickets->links('vendor.pagination.tickets') }}
                </div>
            </div>
        @endif
    </div>

@endsection
