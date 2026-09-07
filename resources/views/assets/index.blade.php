@extends('layouts.app')

@section('titre', 'Gestion des Actifs (CMDB)')
@section('styles') @include('partials.module-styles') @endsection

@section('contenu')
<div class="page-actions">
    <div>
        <p style="margin:0;color:var(--ambre-clair);font:500 .68rem 'JetBrains Mono',monospace;letter-spacing:.1em;text-transform:uppercase;">Gestion de Parc / CMDB</p>
        <h2 style="margin:.35rem 0 0;font-family:'Space Grotesk',sans-serif;font-size:1.35rem;">Inventaire des Actifs</h2>
    </div>
    <p style="margin:0;color:var(--texte-att);font-size:.88rem;">{{ $assets->total() }} actif{{ $assets->total() > 1 ? 's' : '' }} référencé{{ $assets->total() > 1 ? 's' : '' }}</p>
</div>

<form method="GET" action="{{ route('assets.index') }}" class="carte filtres" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
    <div class="champ" style="flex:1;min-width:200px;">
        <label for="q">Recherche</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, code inventaire, n° série, marque, modèle…">
    </div>
    <div class="champ">
        <label for="statut">Statut</label>
        <select id="statut" name="statut">
            <option value="">Tous les statuts</option>
            @foreach(['En stock','En service','En maintenance','Hors service','Réformé'] as $statut)
                <option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ $statut }}</option>
            @endforeach
        </select>
    </div>
    <div class="champ">
        <label for="per_page">Par page</label>
        <select id="per_page" name="per_page">
            <option value="10" @selected(($perPage ?? 20) == 10)>10 par page</option>
            <option value="20" @selected(($perPage ?? 20) == 20)>20 par page</option>
            <option value="25" @selected(($perPage ?? 20) == 25)>25 par page</option>
            <option value="50" @selected(($perPage ?? 20) == 50)>50 par page</option>
            <option value="100" @selected(($perPage ?? 20) == 100)>100 par page</option>
        </select>
    </div>
    <div style="display:flex;gap:.5rem;">
        <button class="btn btn-secondaire" type="submit">Filtrer</button>
        @if(request()->hasAny(['q', 'statut', 'per_page']))
            <a href="{{ route('assets.index') }}" class="btn btn-secondaire">Réinitialiser</a>
        @endif
    </div>
</form>

<div class="carte table-wrap" style="margin-top:1.25rem;">
    @if($assets->isEmpty())
        <div class="vide" style="padding:2.5rem;text-align:center;color:var(--texte-att);">
            <p>Aucun actif trouvé correspondant aux critères.</p>
        </div>
    @else
        <table class="tickets">
            <thead>
                <tr>
                    <th>Inventaire</th>
                    <th>Actif</th>
                    <th>Type</th>
                    <th>Utilisateur / Service</th>
                    <th>Site</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assets as $asset)
                <tr>
                    <td class="ref">{{ $asset->code_inventaire }}</td>
                    <td>
                        <strong>{{ $asset->nom }}</strong>
                        <br><small style="color:var(--texte-att);">{{ $asset->marque }} {{ $asset->modele }} {{ $asset->numero_serie ? '· S/N: '.$asset->numero_serie : '' }}</small>
                    </td>
                    <td>{{ $asset->type?->nom ?? '—' }}</td>
                    <td>
                        {{ $asset->utilisateur?->name ?? ($asset->departement?->nom ?? 'Non affecté') }}
                    </td>
                    <td>{{ $asset->site?->nom ?? '—' }}</td>
                    <td>
                        <span class="badge" style="background:rgba(224,165,47,.12);color:var(--ambre-clair);">
                            {{ $asset->statut }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.25rem;flex-wrap:wrap;gap:1rem;">
            <div style="font-size:0.82rem;color:var(--texte-att);">
                Affichage de {{ $assets->firstItem() ?? 0 }} à {{ $assets->lastItem() ?? 0 }} sur {{ $assets->total() }} résultats
            </div>
            <div class="pagination">
                {{ $assets->links('vendor.pagination.tickets') }}
            </div>
        </div>
    @endif
</div>
@endsection
