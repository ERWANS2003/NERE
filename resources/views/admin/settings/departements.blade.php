@extends('layouts.app')

@section('titre', 'Administration · Services')
@section('styles') @include('partials.module-styles') @endsection

@section('contenu')
<div class="page-actions">
    <div><p style="margin:0;color:var(--ambre-clair);font:500 .68rem 'JetBrains Mono',monospace;letter-spacing:.1em;text-transform:uppercase;">Configuration ESM</p><p style="margin:.35rem 0 0;color:var(--texte-att);font-size:.88rem;">Les services structurent les droits, les équipes et les tableaux de bord.</p></div>
    <a class="btn btn-primaire" href="{{ route('admin.settings.teams') }}">Gérer les équipes</a>
</div>
<div class="grille-2">
    <div class="module-card">
        <h2>Services de l'entreprise</h2>
        <div class="table-wrap"><table class="tickets"><thead><tr><th>Service</th><th>Code</th><th>Équipes</th><th>Utilisateurs</th><th>Tickets</th><th></th></tr></thead><tbody>
        @forelse($departements as $departement)
            <tr><td><strong>{{ $departement->nom }}</strong></td><td class="ref">{{ $departement->code }}</td><td>{{ $departement->teams_count }}</td><td>{{ $departement->users_count }}</td><td>{{ $departement->tickets_count }}</td><td><form method="POST" action="{{ route('admin.settings.departments.destroy', $departement) }}" onsubmit="return confirm('Supprimer ce service ?');">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Supprimer</button></form></td></tr>
        @empty <tr><td colspan="6">Aucun service configuré.</td></tr> @endforelse
        </tbody></table></div>
    </div>
    <div class="module-card">
        <h2>Ajouter un service</h2>
        <p>Un service doit ensuite recevoir une équipe et des catégories.</p>
        <form method="POST" action="{{ route('admin.settings.departments.store') }}">
            @csrf
            <div class="champ"><label for="nom">Nom du service</label><input id="nom" name="nom" required placeholder="Ex. : HSE"></div>
            <div class="champ" style="margin-top:.75rem;"><label for="code">Code court</label><input id="code" name="code" maxlength="10" required placeholder="Ex. : HSE"></div>
            <div class="champ" style="margin-top:.75rem;"><label for="description">Description</label><textarea id="description" name="description" rows="3"></textarea></div>
            <button class="btn btn-primaire" style="margin-top:1rem;" type="submit">Créer le service</button>
        </form>
    </div>
</div>
@endsection
