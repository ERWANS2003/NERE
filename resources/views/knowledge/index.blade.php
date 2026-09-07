@extends('layouts.app')

@section('titre', 'Base de Connaissances')
@section('styles') @include('partials.module-styles') @endsection

@section('contenu')
<div class="page-actions">
    <div>
        <p style="margin:0;color:var(--ambre-clair);font:500 .68rem 'JetBrains Mono',monospace;letter-spacing:.1em;text-transform:uppercase;">Documentation & Procédures</p>
        <h2 style="margin:.35rem 0 0;font-family:'Space Grotesk',sans-serif;font-size:1.35rem;">Base de Connaissances</h2>
    </div>
    <p style="margin:0;color:var(--texte-att);font-size:.88rem;">{{ $articles->total() }} article{{ $articles->total() > 1 ? 's' : '' }} disponible{{ $articles->total() > 1 ? 's' : '' }}</p>
</div>

<form method="GET" action="{{ route('knowledge.index') }}" class="carte filtres" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
    <div class="champ" style="flex:1;min-width:220px;">
        <label for="q">Recherche d'article</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Titre, procédure, mot-clé…">
    </div>
    <div class="champ">
        <label for="per_page">Par page</label>
        <select id="per_page" name="per_page">
            <option value="10" @selected(($perPage ?? 15) == 10)>10 par page</option>
            <option value="15" @selected(($perPage ?? 15) == 15)>15 par page</option>
            <option value="25" @selected(($perPage ?? 15) == 25)>25 par page</option>
            <option value="50" @selected(($perPage ?? 15) == 50)>50 par page</option>
        </select>
    </div>
    <div style="display:flex;gap:.5rem;">
        <button class="btn btn-secondaire" type="submit">Rechercher</button>
        @if(request()->hasAny(['q', 'per_page']))
            <a href="{{ route('knowledge.index') }}" class="btn btn-secondaire">Réinitialiser</a>
        @endif
    </div>
</form>

<div class="module-card" style="margin-top:1.25rem;">
    <h2>Articles publiés</h2>
    @if($articles->isEmpty())
        <div class="vide" style="padding:2.5rem;text-align:center;color:var(--texte-att);">
            <p>Aucun article publié ne correspond à votre recherche.</p>
        </div>
    @else
        <div class="article-list">
            @foreach($articles as $article)
            <a href="{{ route('knowledge.show', $article) }}" style="display:block;padding:1rem;border-bottom:1px solid var(--graphite-line);text-decoration:none;">
                <h3 style="margin:0 0 .35rem;font-size:1rem;color:var(--texte-clair);">{{ $article->titre }}</h3>
                <p style="margin:0 0 .5rem;font-size:.85rem;color:var(--texte-att);">{{ Str::limit(strip_tags($article->contenu), 180) }}</p>
                <small style="color:var(--texte-att);">
                    {{ $article->categorie?->nom ?? 'Général' }} · {{ number_format($article->vues) }} vue{{ $article->vues > 1 ? 's' : '' }}
                </small>
            </a>
            @endforeach
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.25rem;flex-wrap:wrap;gap:1rem;">
            <div style="font-size:0.82rem;color:var(--texte-att);">
                Affichage de {{ $articles->firstItem() ?? 0 }} à {{ $articles->lastItem() ?? 0 }} sur {{ $articles->total() }} articles
            </div>
            <div class="pagination">
                {{ $articles->links('vendor.pagination.tickets') }}
            </div>
        </div>
    @endif
</div>

<div class="module-card" style="margin-top:1.5rem;">
    <h2>Publier une fiche procédure / article</h2>
    <form method="POST" action="{{ route('knowledge.store') }}">
        @csrf
        <div class="champ">
            <label for="titre">Titre de l'article *</label>
            <input id="titre" name="titre" value="{{ old('titre') }}" required placeholder="Ex : Comment réinitialiser son mot de passe VPN">
        </div>
        <div class="champ" style="margin-top:.85rem;">
            <label for="contenu">Contenu explicatif *</label>
            <textarea id="contenu" name="contenu" rows="5" required placeholder="Étapes à suivre...">{{ old('contenu') }}</textarea>
        </div>
        <div class="champ" style="margin-top:.85rem;">
            <label for="mots_cles">Mots-clés (séparés par une virgule)</label>
            <input id="mots_cles" name="mots_cles" value="{{ old('mots_cles') }}" placeholder="vpn, reinitialisation, acces, reseau">
        </div>
        <button class="btn btn-primaire" style="margin-top:1.1rem;" type="submit">Publier l'article</button>
    </form>
</div>
@endsection
