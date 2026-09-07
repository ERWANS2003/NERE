@extends('layouts.app')

@section('titre', 'Gestion des Utilisateurs')
@section('styles') @include('partials.module-styles') @endsection

@section('contenu')
<div class="page-actions">
    <div>
        <p style="margin:0;color:var(--ambre-clair);font:500 .68rem 'JetBrains Mono',monospace;letter-spacing:.1em;text-transform:uppercase;">Administration</p>
        <h2 style="margin:.35rem 0 0;font-family:'Space Grotesk',sans-serif;font-size:1.35rem;">Gestion des Utilisateurs</h2>
    </div>
    <p style="margin:0;color:var(--texte-att);font-size:.88rem;">{{ $users->total() }} utilisateur{{ $users->total() > 1 ? 's' : '' }} enregistré(s)</p>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="carte filtres" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
    <div class="champ" style="flex:1;min-width:200px;">
        <label for="q">Recherche</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Nom, email, matricule, poste…">
    </div>
    <div class="champ">
        <label for="role_id">Rôle</label>
        <select id="role_id" name="role_id">
            <option value="">Tous les rôles</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>{{ $role->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="champ">
        <label for="departement_id">Service</label>
        <select id="departement_id" name="departement_id">
            <option value="">Tous les services</option>
            @foreach($departements as $departement)
                <option value="{{ $departement->id }}" @selected(request('departement_id') == $departement->id)>{{ $departement->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="champ">
        <label for="site_id">Site minier</label>
        <select id="site_id" name="site_id">
            <option value="">Tous les sites</option>
            @foreach($sites as $site)
                <option value="{{ $site->id }}" @selected(request('site_id') == $site->id)>{{ $site->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="champ">
        <label for="actif">Statut</label>
        <select id="actif" name="actif">
            <option value="">Tous</option>
            <option value="1" @selected(request('actif') === '1')>Actifs uniquement</option>
            <option value="0" @selected(request('actif') === '0')>Inactifs uniquement</option>
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
    <div style="display:flex;gap:.5rem;">
        <button class="btn btn-secondaire" type="submit">Filtrer</button>
        @if(request()->hasAny(['q', 'role_id', 'departement_id', 'site_id', 'actif', 'per_page']))
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondaire">Réinitialiser</a>
        @endif
    </div>
</form>

<div class="carte table-wrap" style="margin-top:1.25rem;">
    <table class="tickets">
        <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Rôle</th>
                <th>Service</th>
                <th>Site</th>
                <th>Poste / Matricule</th>
                <th>État</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <strong>{{ $user->name }}</strong>
                    <br><small style="color:var(--texte-att);">{{ $user->email }}</small>
                </td>
                <td>
                    @if($user->role)
                        <span class="badge" style="background:rgba(224,165,47,.12);color:var(--ambre-clair);">{{ $user->role->nom }}</span>
                    @else
                        —
                    @endif
                </td>
                <td>{{ $user->departement?->nom ?? '—' }}</td>
                <td>{{ $user->site?->nom ?? '—' }}</td>
                <td style="font-size:.82rem;color:var(--texte-att);">
                    {{ $user->poste ?? '—' }}
                    @if($user->matricule)<br><span style="font-family:'JetBrains Mono',monospace;font-size:.75rem;">{{ $user->matricule }}</span>@endif
                </td>
                <td>
                    <span class="badge" style="background:{{ $user->actif ? 'rgba(63,166,107,.12)' : 'rgba(214,69,69,.12)' }};color:{{ $user->actif ? '#7fd4a0' : '#f0a0a0' }};">
                        {{ $user->actif ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit" @disabled(!$user->actif)>
                            Désactiver
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:2rem;color:var(--texte-att);">Aucun utilisateur trouvé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.25rem;flex-wrap:wrap;gap:1rem;">
        <div style="font-size:0.82rem;color:var(--texte-att);">
            Affichage de {{ $users->firstItem() ?? 0 }} à {{ $users->lastItem() ?? 0 }} sur {{ $users->total() }} résultats
        </div>
        <div class="pagination">
            {{ $users->links('vendor.pagination.tickets') }}
        </div>
    </div>
</div>

<div class="module-card" style="margin-top:1.5rem;">
    <h2>Créer un nouvel utilisateur</h2>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="form-grille">
            <div class="champ">
                <label for="name">Nom complet *</label>
                <input id="name" name="name" required placeholder="Prénom NOM">
            </div>
            <div class="champ">
                <label for="email">Adresse Email *</label>
                <input id="email" name="email" type="email" required placeholder="nom@nere-mining.bf">
            </div>
            <div class="champ">
                <label for="password">Mot de passe initial *</label>
                <input id="password" name="password" type="password" minlength="8" required>
            </div>
            <div class="champ">
                <label for="role_id">Rôle *</label>
                <select id="role_id" name="role_id" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="champ">
                <label for="departement_id">Service / Département</label>
                <select id="departement_id" name="departement_id">
                    <option value="">Sans service</option>
                    @foreach($departements as $departement)
                        <option value="{{ $departement->id }}">{{ $departement->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="champ">
                <label for="site_id">Site minier</label>
                <select id="site_id" name="site_id">
                    <option value="">Sans site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->nom }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label style="display:flex;gap:.5rem;align-items:center;margin-top:1rem;font-size:.85rem;cursor:pointer;">
            <input type="checkbox" name="est_technicien" value="1" style="accent-color:var(--ambre);">
            Cet utilisateur est un technicien / agent (éligible à l'affectation des demandes)
        </label>
        <button class="btn btn-primaire" style="margin-top:1rem;" type="submit">Créer l'utilisateur</button>
    </form>
</div>
@endsection
