@extends('layouts.app')

@section('titre', 'Administration · Rôles et Permissions')

@section('styles')
<style>
    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.25rem;
        margin-top: 1.5rem;
    }

    .role-card {
        background: var(--graphite-card);
        border: 1px solid var(--graphite-line);
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .role-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: .75rem;
    }

    .role-nom {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--texte-clair);
        margin: 0;
    }

    .role-slug {
        font-family: 'JetBrains Mono', monospace;
        font-size: .7rem;
        color: var(--ambre-clair);
        background: rgba(224,165,47,.1);
        padding: .15rem .5rem;
        border-radius: 4px;
    }

    .role-desc {
        font-size: .83rem;
        color: var(--texte-att);
        margin-bottom: 1rem;
        line-height: 1.4;
    }

    .permissions-tags {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-bottom: 1.25rem;
    }

    .perm-tag {
        font-size: .72rem;
        padding: .15rem .5rem;
        border-radius: 999px;
        background: var(--graphite-soft);
        border: 1px solid var(--graphite-line);
        color: var(--texte-att);
    }

    .role-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid var(--graphite-line);
    }

    .btn-sm {
        padding: .35rem .75rem;
        font-size: .8rem;
    }
</style>
@endsection

@section('contenu')
<div class="page-actions">
    <div>
        <p style="margin:0;color:var(--ambre-clair);font:500 .68rem 'JetBrains Mono',monospace;letter-spacing:.1em;text-transform:uppercase;">Gestion des Rôles & Sécurité</p>
        <h2 style="margin:.35rem 0 0;font-family:'Space Grotesk',sans-serif;font-size:1.35rem;">Rôles d'utilisateurs et Autorisations</h2>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary" style="background:var(--ambre);color:var(--graphite);font-weight:600;padding:.6rem 1.1rem;border-radius:8px;">
        ➕ Nouveau Rôle
    </a>
</div>

<div class="roles-grid">
    @foreach($roles as $role)
    <div class="role-card">
        <div>
            <div class="role-header">
                <h3 class="role-nom">{{ $role->nom }}</h3>
                <span class="role-slug">{{ $role->slug }}</span>
            </div>
            <p class="role-desc">{{ $role->description ?? 'Aucune description spécifique.' }}</p>

            <div style="font-size:.78rem;color:var(--texte-att);margin-bottom:.5rem;font-weight:500;">
                Permissions attribuées ({{ $role->permissions->count() }}) :
            </div>
            <div class="permissions-tags">
                @forelse($role->permissions as $perm)
                    <span class="perm-tag">{{ $perm->nom }}</span>
                @empty
                    <span class="perm-tag" style="font-style:italic;">Aucune permission spécifique</span>
                @endforelse
            </div>
        </div>

        <div class="role-actions">
            <span style="font-size:.78rem;color:var(--texte-att);">
                <strong>{{ $role->users_count }}</strong> utilisateur(s)
            </span>
            <div style="display:flex;gap:.5rem;">
                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-secondaire btn-sm" style="background:var(--graphite-soft);border:1px solid var(--graphite-line);color:var(--texte-clair);border-radius:6px;">Éditer</a>
                @if(! in_array($role->slug, ['admin', 'dsi'], true) && $role->users_count === 0)
                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Supprimer le rôle {{ $role->nom }} ?');" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm" style="background:rgba(217,54,46,0.15);color:#f09090;border:1px solid rgba(217,54,46,0.3);border-radius:6px;">Supprimer</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
