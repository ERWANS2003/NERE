@extends('layouts.app')

@section('titre', 'Mon Département — ' . $departement->nom)

@section('styles')
<style>
    /* ---- Page variables ---- */
    :root {
        --dept-accent: var(--ambre);
        --dept-accent-bg: rgba(224,165,47,.10);
    }

    /* ---- Hero carte département ---- */
    .dept-hero {
        background: linear-gradient(135deg, var(--graphite-card) 0%, rgba(224,165,47,.06) 100%);
        border: 1px solid var(--graphite-line);
        border-radius: 14px;
        padding: 1.75rem 2rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .dept-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(224,165,47,.12) 0%, transparent 70%);
        pointer-events: none;
    }

    .dept-icon {
        width: 64px; height: 64px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--ambre), #8f6a26);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 1.8rem;
    }

    .dept-hero-infos h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 .35rem;
        color: var(--texte-clair);
    }

    .dept-hero-infos p {
        margin: 0;
        font-size: .85rem;
        color: var(--texte-att);
    }

    .dept-stats {
        margin-left: auto;
        display: flex;
        gap: 1.5rem;
        flex-shrink: 0;
    }

    .dept-stat {
        text-align: center;
    }

    .dept-stat .valeur {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--ambre-clair);
        line-height: 1;
    }

    .dept-stat .libelle {
        font-size: .72rem;
        color: var(--texte-att);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-top: .2rem;
    }

    /* ---- Tabs ---- */
    .tabs-bar {
        display: flex;
        gap: .25rem;
        border-bottom: 1px solid var(--graphite-line);
        margin-bottom: 1.75rem;
    }

    .tab-btn {
        padding: .65rem 1.25rem;
        font-size: .87rem;
        font-weight: 500;
        color: var(--texte-att);
        border: none;
        background: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        transition: color .15s, border-color .15s;
        margin-bottom: -1px;
    }

    .tab-btn:hover { color: var(--texte-clair); }
    .tab-btn.active { color: var(--ambre-clair); border-bottom-color: var(--ambre-clair); }

    /* ---- Grid ---- */
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }

    /* ---- Cards ---- */
    .card {
        background: var(--graphite-card);
        border: 1px solid var(--graphite-line);
        border-radius: 12px;
        padding: 1.5rem;
    }

    .card-titre {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        color: var(--texte-clair);
        margin: 0 0 1.25rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .card-titre svg { color: var(--ambre); }

    /* ---- Table membres ---- */
    table { width: 100%; border-collapse: collapse; font-size: .85rem; }
    th { padding: .5rem .75rem; text-align: left; color: var(--texte-att); font-weight: 500; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--graphite-line); }
    td { padding: .7rem .75rem; border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: middle; }
    tr:last-child td { border-bottom: 0; }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .6rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .badge-technicien { background: rgba(74,144,217,.15); color: #81b9f5; }
    .badge-actif { background: rgba(63,166,107,.15); color: #7fd4a0; }
    .badge-inactif { background: rgba(217,54,46,.15); color: #f09090; }
    .badge-role { background: rgba(224,165,47,.12); color: var(--ambre-clair); }

    /* ---- Forms ---- */
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; font-size: .8rem; font-weight: 500; color: var(--texte-att); margin-bottom: .4rem; }
    .form-input {
        width: 100%;
        padding: .6rem .85rem;
        background: var(--graphite-soft);
        border: 1px solid var(--graphite-line);
        border-radius: 8px;
        color: var(--texte-clair);
        font-family: 'Inter', sans-serif;
        font-size: .88rem;
        transition: border-color .15s;
    }
    .form-input:focus { outline: none; border-color: var(--ambre); }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 700px) { .form-row { grid-template-columns: 1fr; } }

    /* ---- Buttons ---- */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .6rem 1.1rem;
        border-radius: 8px;
        font-size: .85rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: opacity .15s, transform .1s;
        text-decoration: none;
    }

    .btn:hover { opacity: .9; transform: translateY(-1px); }
    .btn:active { transform: translateY(0); }

    .btn-primary { background: var(--ambre); color: var(--graphite); font-weight: 600; }
    .btn-danger  { background: rgba(217,54,46,.15); color: #f09090; border: 1px solid rgba(217,54,46,.25); }
    .btn-sm      { padding: .3rem .7rem; font-size: .78rem; }

    /* ---- Equipe card ---- */
    .equipe-card {
        background: var(--graphite-soft);
        border: 1px solid var(--graphite-line);
        border-radius: 10px;
        padding: 1.1rem 1.25rem;
        margin-bottom: 1rem;
    }

    .equipe-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .75rem;
    }

    .equipe-card-nom {
        font-weight: 600;
        font-size: .95rem;
        color: var(--texte-clair);
    }

    .equipe-membres-liste {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: .75rem;
    }

    .equipe-membre-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: var(--graphite-card);
        border: 1px solid var(--graphite-line);
        border-radius: 999px;
        padding: .2rem .75rem .2rem .5rem;
        font-size: .78rem;
    }

    .equipe-membre-chip .avatar-mini {
        width: 22px; height: 22px;
        border-radius: 6px;
        background: linear-gradient(135deg, var(--ambre), #8f6a26);
        display: flex; align-items: center; justify-content: center;
        font-size: .6rem; font-weight: 700;
        color: var(--graphite);
        flex-shrink: 0;
    }

    /* ---- Tab panels ---- */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ---- Empty state ---- */
    .empty-state {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--texte-att);
        font-size: .9rem;
    }

    /* ---- Erreurs ---- */
    .erreur-liste {
        background: rgba(217,54,46,.1);
        border: 1px solid rgba(217,54,46,.3);
        border-radius: 8px;
        padding: .75rem 1rem;
        margin-bottom: 1rem;
        font-size: .82rem;
        color: #f09090;
    }

    .erreur-liste ul { margin: .35rem 0 0 1rem; }

    /* Responsive hero */
    @media (max-width: 700px) {
        .dept-hero { flex-direction: column; align-items: flex-start; }
        .dept-stats { margin-left: 0; }
    }
</style>
@endsection

@section('contenu')

{{-- ── Hero département ───────────────────────────────── --}}
<div class="dept-hero">
    <div class="dept-icon">🏢</div>
    <div class="dept-hero-infos">
        <h2>Département {{ $departement->nom }}</h2>
        <p>
            Code : <strong>{{ $departement->code ?? '—' }}</strong>
            @if($departement->directeur)
                &nbsp;·&nbsp; Directeur : <strong>{{ $departement->directeur->name }}</strong>
            @endif
        </p>
        @if($departement->description)
            <p style="margin-top:.4rem;">{{ $departement->description }}</p>
        @endif
    </div>
    <div class="dept-stats">
        <div class="dept-stat">
            <div class="valeur">{{ $totalMembres ?? $membres->total() }}</div>
            <div class="libelle">Membres</div>
        </div>
        <div class="dept-stat">
            <div class="valeur">{{ $teams->count() }}</div>
            <div class="libelle">Équipes</div>
        </div>
        <div class="dept-stat">
            <div class="valeur">{{ $totalActifs ?? 0 }}</div>
            <div class="libelle">Actifs</div>
        </div>
    </div>
</div>

{{-- ── Tabs ────────────────────────────────────────────── --}}
<div class="tabs-bar">
    <button class="tab-btn active" data-tab="membres" id="tab-membres-btn">
        👥 Membres ({{ $totalMembres ?? $membres->total() }})
    </button>
    <button class="tab-btn" data-tab="equipes" id="tab-equipes-btn">
        🏷️ Équipes ({{ $teams->count() }})
    </button>
    <button class="tab-btn" data-tab="creer" id="tab-creer-btn">
        ➕ Créer un membre
    </button>
</div>

{{-- ── Tab : Membres ───────────────────────────────────── --}}
<div class="tab-panel active" id="panel-membres">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:1rem;">
            <div class="card-titre" style="margin:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Membres du département
            </div>

            <form method="GET" action="{{ route('department.index') }}" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un membre…" class="form-input" style="padding:.4rem .7rem;font-size:.82rem;width:200px;">
                <select name="per_page" class="form-input" style="padding:.4rem .7rem;font-size:.82rem;width:auto;">
                    <option value="10" @selected(($perPage ?? 20) == 10)>10 / page</option>
                    <option value="20" @selected(($perPage ?? 20) == 20)>20 / page</option>
                    <option value="50" @selected(($perPage ?? 20) == 50)>50 / page</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                @if(request()->hasAny(['q', 'per_page']))
                    <a href="{{ route('department.index') }}" class="btn btn-sm" style="background:var(--graphite-soft);border:1px solid var(--graphite-line);color:var(--texte-att);">Réinitialiser</a>
                @endif
            </form>
        </div>

        @if($membres->isEmpty())
            <div class="empty-state">Aucun membre ne correspond à votre recherche.</div>
        @else
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Poste</th>
                            <th>Équipes</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($membres as $membre)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:.6rem;">
                                    <div class="avatar" style="width:32px;height:32px;font-size:.75rem;border-radius:7px;">
                                        {{ strtoupper(substr($membre->name, 0, 2)) }}
                                    </div>
                                    {{ $membre->name }}
                                    @if($membre->est_technicien)
                                        <span class="badge badge-technicien">Tech</span>
                                    @endif
                                </div>
                            </td>
                            <td style="color:var(--texte-att);">{{ $membre->email }}</td>
                            <td>
                                @if($membre->role)
                                    <span class="badge badge-role">{{ $membre->role->nom }}</span>
                                @else
                                    <span style="color:var(--texte-att);">—</span>
                                @endif
                            </td>
                            <td style="color:var(--texte-att);">{{ $membre->poste ?? '—' }}</td>
                            <td>
                                @forelse($membre->teams as $t)
                                    <span class="badge badge-role" style="margin-right:.2rem;">{{ $t->nom }}</span>
                                @empty
                                    <span style="color:var(--texte-att);">—</span>
                                @endforelse
                            </td>
                            <td>
                                @if($membre->actif)
                                    <span class="badge badge-actif">Actif</span>
                                @else
                                    <span class="badge badge-inactif">Inactif</span>
                                @endif
                            </td>
                            <td>
                                @if($membre->actif && $membre->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('department.members.deactivate', $membre) }}"
                                          onsubmit="return confirm('Désactiver {{ $membre->name }} ?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-danger btn-sm">Désactiver</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.25rem;flex-wrap:wrap;gap:1rem;">
                <div style="font-size:0.82rem;color:var(--texte-att);">
                    Affichage de {{ $membres->firstItem() ?? 0 }} à {{ $membres->lastItem() ?? 0 }} sur {{ $membres->total() }} membres
                </div>
                <div class="pagination">
                    {{ $membres->links('vendor.pagination.tickets') }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ── Tab : Équipes ───────────────────────────────────── --}}
<div class="tab-panel" id="panel-equipes">

    @if($teams->isEmpty())
        <div class="card">
            <div class="empty-state">
                Aucune équipe n'est encore configurée pour ce département.<br>
                <small>Demandez à l'administrateur de créer une équipe.</small>
            </div>
        </div>
    @else
        @foreach($teams as $team)
        <div class="equipe-card">
            <div class="equipe-card-header">
                <div class="equipe-card-nom">{{ $team->nom }}</div>
                <span style="font-size:.78rem;color:var(--texte-att);">
                    {{ $team->techniciens->count() }} membre(s)
                </span>
            </div>

            {{-- Membres actuels --}}
            @if($team->techniciens->isNotEmpty())
            <div class="equipe-membres-liste">
                @foreach($team->techniciens as $tech)
                <div class="equipe-membre-chip">
                    <div class="avatar-mini">{{ strtoupper(substr($tech->name, 0, 2)) }}</div>
                    {{ $tech->name }}
                    @if($tech->pivot->chef_equipe)
                        <span style="color:var(--ambre-clair);font-size:.65rem;">★ Chef</span>
                    @endif
                    <form method="POST"
                          action="{{ route('department.teams.members.remove', [$team, $tech]) }}"
                          onsubmit="return confirm('Retirer {{ $tech->name }} de l\'équipe ?')"
                          style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                title="Retirer"
                                style="background:none;border:none;cursor:pointer;color:#f09090;font-size:.85rem;padding:0;line-height:1;">×</button>
                    </form>
                </div>
                @endforeach
            </div>
            @else
            <p style="font-size:.82rem;color:var(--texte-att);margin-bottom:.75rem;">Aucun membre dans cette équipe.</p>
            @endif

            {{-- Formulaire : Ajouter un membre existant --}}
            <form method="POST"
                  action="{{ route('department.teams.members.add', $team) }}"
                  style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
                @csrf
                <div class="form-group" style="margin:0;flex:1;min-width:180px;">
                    <label class="form-label" for="user_{{ $team->id }}">Ajouter un membre du département</label>
                    <select name="user_id" id="user_{{ $team->id }}" class="form-input" required>
                        <option value="">— Choisir un membre —</option>
                        @foreach(($tousMembresDepartement ?? $membres)->where('actif', true) as $m)
                            @if(! $team->techniciens->contains($m->id))
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->role?->nom ?? 'Sans rôle' }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="display:flex;align-items:center;gap:.4rem;">
                        <input type="checkbox" name="chef_equipe" value="1" style="accent-color:var(--ambre);">
                        Chef d'équipe
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Ajouter</button>
            </form>
        </div>
        @endforeach
    @endif
</div>

{{-- ── Tab : Créer un membre ───────────────────────────── --}}
<div class="tab-panel" id="panel-creer">
    <div class="card">
        <div class="card-titre">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/>
                <line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
            Créer un nouveau membre — <span style="color:var(--ambre-clair);">{{ $departement->nom }}</span>
        </div>

        {{-- Erreurs de validation --}}
        @if($errors->any())
            <div class="erreur-liste">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('department.members.store') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Nom complet *</label>
                    <input type="text" id="name" name="name" class="form-input"
                           value="{{ old('name') }}" required placeholder="Prénom NOM">
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Adresse email *</label>
                    <input type="email" id="email" name="email" class="form-input"
                           value="{{ old('email') }}" required placeholder="prenom.nom@nere-mining.bf">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" class="form-input"
                           required placeholder="8 caractères minimum">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-input" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="role_id">Rôle *</label>
                    <select id="role_id" name="role_id" class="form-input" required>
                        <option value="">— Sélectionner un rôle —</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="site_id">Site minier</label>
                    <select id="site_id" name="site_id" class="form-input">
                        <option value="">— Aucun site spécifique —</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                {{ $site->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="poste">Intitulé du poste</label>
                    <input type="text" id="poste" name="poste" class="form-input"
                           value="{{ old('poste') }}" placeholder="Ex : Ingénieur HSE, Technicien IT…">
                </div>
                <div class="form-group">
                    <label class="form-label" for="matricule">Matricule</label>
                    <input type="text" id="matricule" name="matricule" class="form-input"
                           value="{{ old('matricule') }}" placeholder="Ex : NM-2026-042">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="telephone">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" class="form-input"
                           value="{{ old('telephone') }}" placeholder="+226 XX XX XX XX">
                </div>
                <div class="form-group">
                    <label class="form-label" for="team_id">Affecter directement à une équipe</label>
                    <select id="team_id" name="team_id" class="form-input">
                        <option value="">— Pas d'équipe pour l'instant —</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                                {{ $team->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:.75rem;margin-top:.25rem;">
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.87rem;cursor:pointer;">
                    <input type="checkbox" name="est_technicien" value="1"
                           {{ old('est_technicien') ? 'checked' : '' }}
                           style="accent-color:var(--ambre);width:16px;height:16px;">
                    Marquer comme technicien / agent (éligible à l'affectation des tickets)
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;gap:.75rem;">
                <button type="reset" class="btn" style="background:var(--graphite-soft);border:1px solid var(--graphite-line);color:var(--texte-att);">
                    Réinitialiser
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Créer le compte
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Gestion des onglets
    (function () {
        const btns   = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.tab-panel');

        function activate(tabName) {
            btns.forEach(b => b.classList.toggle('active', b.dataset.tab === tabName));
            panels.forEach(p => p.classList.toggle('active', p.id === 'panel-' + tabName));
        }

        btns.forEach(btn => {
            btn.addEventListener('click', () => activate(btn.dataset.tab));
        });

        // Si des erreurs de validation → ouvrir l'onglet "Créer"
        @if($errors->any())
            activate('creer');
        @endif
    })();
</script>
@endpush
