@extends('layouts.app')

@section('titre', $article->titre)
@section('styles') @include('partials.module-styles') @endsection

@section('contenu')
<div class="page-actions"><a class="btn btn-secondaire" href="{{ route('knowledge.index') }}">← Retour aux articles</a></div>
<div class="grille-2"><article class="module-card"><h2>{{ $article->titre }}</h2><p style="font-size:.8rem;">Publié par {{ $article->auteur?->name ?? 'Support' }} · {{ number_format($article->vues) }} vue{{ $article->vues > 1 ? 's' : '' }}</p><div class="description" style="margin-top:1.25rem;">{{ $article->contenu }}</div></article><aside class="module-card"><h2>Informations</h2><ul class="meta-liste"><li><span class="label">Catégorie</span><span>{{ $article->categorie?->nom ?? 'Général' }}</span></li><li><span class="label">Mots-clés</span><span>{{ $article->mots_cles ?: '—' }}</span></li><li><span class="label">Utilité</span><span>{{ number_format($article->utile_count) }}</span></li></ul></aside></div>
@endsection
