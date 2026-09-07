@extends('layouts.app')

@section('titre', 'Administration · Équipes')
@section('styles') @include('partials.module-styles') @endsection

@section('contenu')
<div class="page-actions">
    <div><p style="margin:0;color:var(--ambre-clair);font:500 .68rem 'JetBrains Mono',monospace;letter-spacing:.1em;text-transform:uppercase;">Routage des demandes</p><p style="margin:.35rem 0 0;color:var(--texte-att);font-size:.88rem;">Une équipe reçoit les catégories de son service et ses techniciens.</p></div>
    <a class="btn btn-secondaire" href="{{ route('admin.settings.categories') }}">Gérer les catégories</a>
</div>
<div class="grille-2">
    <div class="module-card">
        <h2>Équipes de traitement</h2>
        <div class="table-wrap"><table class="tickets"><thead><tr><th>Équipe</th><th>Service</th><th>Membres</th><th>Catégories</th><th>Tickets</th></tr></thead><tbody>
        @forelse($departements as $departement)
            @php($teamsDuService = $teams->where('departement_id', $departement->id))
            @if($teamsDuService->isNotEmpty())
                <tr><th colspan="5" style="color:var(--ambre-clair);background:rgba(224,165,47,.08);">{{ $departement->nom }}</th></tr>
                @foreach($teamsDuService as $team)
                    <tr><td><strong>{{ $team->nom }}</strong></td><td>{{ $departement->nom }}</td><td>{{ $team->techniciens->count() }}</td><td>{{ $team->categories_count }}</td><td>{{ $team->tickets_count }}</td></tr>
                @endforeach
            @endif
        @empty <tr><td colspan="5">Aucune équipe configurée.</td></tr> @endforelse
        </tbody></table></div>
    </div>
    <div class="module-card">
        <h2>Créer une équipe</h2>
        <form method="POST" action="{{ route('admin.settings.teams.store') }}">
            @csrf
            <div class="champ"><label for="nom">Nom de l'équipe</label><input id="nom" name="nom" required placeholder="Ex. : Équipe HSE"></div>
            <div class="champ" style="margin-top:.75rem;"><label for="departement_id">Service</label><select id="departement_id" name="departement_id" required><option value="">Choisir un service</option>@foreach($departements as $departement)<option value="{{ $departement->id }}">{{ $departement->nom }}</option>@endforeach</select></div>
            <div class="champ" style="margin-top:.75rem;"><label for="description">Description</label><textarea id="description" name="description" rows="3"></textarea></div>
            <button class="btn btn-primaire" style="margin-top:1rem;" type="submit">Créer l'équipe</button>
        </form>
    </div>
</div>

@foreach($teams as $team)
<div class="module-card" style="margin-top:1.25rem;">
    <h2>{{ $team->nom }} <span style="color:var(--texte-att);font:400 .8rem Inter,sans-serif;">· {{ $team->departement?->nom }}</span></h2>
    <form method="POST" action="{{ route('admin.settings.teams.update', $team) }}">
        @csrf @method('PUT')
        <div class="form-grille">
            <div class="champ"><label for="team-name-{{ $team->id }}">Nom</label><input id="team-name-{{ $team->id }}" name="nom" value="{{ $team->nom }}" required></div>
            <div class="champ"><label for="team-department-{{ $team->id }}">Service</label><select id="team-department-{{ $team->id }}" name="departement_id" required>@foreach($departements as $departement)<option value="{{ $departement->id }}" @selected($team->departement_id === $departement->id)>{{ $departement->nom }}</option>@endforeach</select></div>
        </div>
        <div class="champ" style="margin-top:.75rem;"><label for="team-users-{{ $team->id }}">Techniciens membres</label><select id="team-users-{{ $team->id }}" name="user_ids[]" multiple size="5">@foreach($users as $user)<option value="{{ $user->id }}" data-departement="{{ $user->departement_id }}" @selected($team->techniciens->contains('id', $user->id))>{{ $user->name }} · {{ $user->departement?->nom ?? 'Sans service' }}</option>@endforeach</select><small style="display:block;color:var(--texte-att);margin-top:.35rem;">Seuls les techniciens actifs du service sont acceptés.</small></div>
        <div class="champ" style="margin-top:.75rem;"><label for="team-categories-{{ $team->id }}">Catégories traitées</label><select id="team-categories-{{ $team->id }}" name="category_ids[]" multiple size="6">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($team->categories->contains('id', $category->id))>{{ $category->nom }}{{ $category->team_id && $category->team_id !== $team->id ? ' · autre équipe' : '' }}</option>@endforeach</select><small style="display:block;color:var(--texte-att);margin-top:.35rem;">Les catégories sélectionnées seront routées vers cette équipe, y compris celles d’une autre équipe.</small></div>
        <div class="champ" style="margin-top:.75rem;"><label for="team-description-{{ $team->id }}">Description</label><textarea id="team-description-{{ $team->id }}" name="description" rows="2">{{ $team->description }}</textarea></div>
        <button class="btn btn-secondaire btn-sm" style="margin-top:1rem;" type="submit">Enregistrer l'équipe</button>
    </form>
    <form method="POST" action="{{ route('admin.settings.teams.destroy', $team) }}" style="display:inline-block;margin-top:.5rem;" onsubmit="return confirm('Supprimer cette équipe ?');">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">Supprimer l'équipe</button></form>
</div>
@endforeach
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[id^="team-department-"]').forEach(select => {
        const users = document.getElementById('team-users-' + select.id.replace('team-department-', ''));
        const filter = () => Array.from(users.options).forEach(option => { option.hidden = option.dataset.departement !== select.value; if (option.hidden) option.selected = false; });
        select.addEventListener('change', filter); filter();
    });
</script>
@endpush
