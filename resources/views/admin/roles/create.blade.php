@extends('layouts.app')

@section('titre', 'Administration · Créer un Rôle')

@section('styles')
<style>
    .carte-form {
        background: var(--graphite-card);
        border: 1px solid var(--graphite-line);
        border-radius: 12px;
        padding: 1.75rem;
        max-width: 900px;
    }

    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-size: .85rem; font-weight: 500; color: var(--texte-att); margin-bottom: .4rem; }
    .form-input {
        width: 100%;
        padding: .65rem .85rem;
        background: var(--graphite-soft);
        border: 1px solid var(--graphite-line);
        border-radius: 8px;
        color: var(--texte-clair);
        font-family: 'Inter', sans-serif;
        font-size: .88rem;
    }
    .form-input:focus { outline: none; border-color: var(--ambre); }

    .module-group {
        background: var(--graphite-soft);
        border: 1px solid var(--graphite-line);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .module-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: .92rem;
        font-weight: 600;
        color: var(--ambre-clair);
        margin: 0 0 .75rem;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .perm-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: .65rem;
    }

    .perm-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .84rem;
        color: var(--texte-clair);
        cursor: pointer;
    }

    .perm-item input { accent-color: var(--ambre); width: 16px; height: 16px; }
</style>
@endsection

@section('contenu')
<div style="margin-bottom: 1.5rem;">
    <a href="{{ route('admin.roles.index') }}" style="color:var(--texte-att);font-size:.85rem;">← Retour aux rôles</a>
    <h2 style="font-family:'Space Grotesk',sans-serif;margin:.5rem 0 0;">Créer un nouveau rôle</h2>
</div>

<div class="carte-form">
    <form method="POST" action="{{ route('admin.roles.store') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="nom">Nom du rôle *</label>
            <input type="text" id="nom" name="nom" class="form-input" value="{{ old('nom') }}" required placeholder="Ex : Superviseur HSE">
        </div>

        <div class="form-group">
            <label class="form-label" for="slug">Identifiant système (Slug)</label>
            <input type="text" id="slug" name="slug" class="form-input" value="{{ old('slug') }}" placeholder="Ex : superviseur_hse (laisser vide pour auto-générer)">
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-input" rows="3" placeholder="Rôle et responsabilités attribués à ce groupe..."></textarea>
        </div>

        <div style="margin-top: 1.75rem; margin-bottom: 1rem;">
            <h3 style="font-family:'Space Grotesk',sans-serif;font-size:1.05rem;color:var(--texte-clair);margin:0 0 .35rem;">Permissions attribuées</h3>
            <p style="font-size:.82rem;color:var(--texte-att);margin:0;">Cochez les droits d'accès associés à ce rôle.</p>
        </div>

        @foreach($permissions as $module => $modulePerms)
        <div class="module-group">
            <div class="module-title">Module : {{ ucfirst($module) }}</div>
            <div class="perm-grid">
                @foreach($modulePerms as $perm)
                <label class="perm-item">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" {{ in_array($perm->id, old('permissions', [])) ? 'checked' : '' }}>
                    {{ $perm->nom }}
                </label>
                @endforeach
            </div>
        </div>
        @endforeach

        <div style="display:flex;justify-content:flex-end;gap:1rem;margin-top:1.5rem;">
            <a href="{{ route('admin.roles.index') }}" class="btn" style="background:var(--graphite-soft);border:1px solid var(--graphite-line);color:var(--texte-att);padding:.6rem 1.1rem;border-radius:8px;">Annuler</a>
            <button type="submit" class="btn" style="background:var(--ambre);color:var(--graphite);font-weight:600;padding:.6rem 1.25rem;border-radius:8px;border:none;">Créer le rôle</button>
        </div>
    </form>
</div>
@endsection
