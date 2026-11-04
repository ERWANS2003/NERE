@extends('admin.layout')

@section('title', 'Vue d’ensemble')

@section('content')
<section class="admin-dashboard-hero">
    <div>
        <p class="admin-dashboard-kicker">Centre de contrôle</p>
        <h2>Bonjour {{ str(auth()->user()->name)->before(' ') }},</h2>
        <p>Retrouvez les indicateurs essentiels et accédez rapidement aux opérations courantes de l’intranet.</p>
    </div>
    <div class="admin-dashboard-hero-actions">
        <a href="{{ route('admin.users.create') }}" class="bouton-primaire">
            <x-icone nom="user-check" /> Ajouter un utilisateur
        </a>
        <a href="{{ route('admin.health') }}" class="bouton-secondaire">
            <x-icone nom="server" /> Vérifier le serveur
        </a>
    </div>
</section>

<section aria-labelledby="admin-key-figures" class="mt-6">
    <div class="admin-section-heading">
        <div>
            <p class="admin-section-kicker">Aujourd’hui</p>
            <h2 id="admin-key-figures">Indicateurs clés</h2>
        </div>
        <a href="{{ route('admin.users.index') }}">Voir les utilisateurs <x-icone nom="arrow-right" /></a>
    </div>

    <div class="admin-stat-grid">
        @foreach([
            ['Utilisateurs', $totalUsers, 'users', route('admin.users.index'), 'nere'],
            ['Comptes actifs', $activeUsers, 'user-check', route('admin.users.index', ['status' => 'active']), 'success'],
            ['Comptes inactifs', $inactiveUsers, 'user-x', route('admin.users.index', ['status' => 'inactive']), 'muted'],
            ['Administrateurs', $administrators, 'shield', route('admin.users.index'), 'gold'],
            ['Départements', $departments, 'building-2', route('intranet.departments.manage.index'), 'blue'],
            ['Rôles', $roles, 'user-cog', route('admin.roles.index'), 'purple'],
        ] as [$label, $count, $icon, $url, $tone])
            <a href="{{ $url }}" class="admin-stat-card" data-tone="{{ $tone }}">
                <span class="admin-stat-icon"><x-icone :nom="$icon" /></span>
                <span class="admin-stat-content">
                    <span class="admin-stat-value">{{ number_format($count, 0, ',', ' ') }}</span>
                    <span class="admin-stat-label">{{ $label }}</span>
                </span>
                <x-icone nom="chevron-right" class="admin-stat-arrow" />
            </a>
        @endforeach
    </div>
</section>

<div class="admin-dashboard-columns">
    <section aria-labelledby="admin-quick-access">
        <div class="admin-section-heading">
            <div>
                <p class="admin-section-kicker">Raccourcis</p>
                <h2 id="admin-quick-access">Actions fréquentes</h2>
            </div>
        </div>
        <div class="admin-quick-grid">
            @foreach([
                ['Gérer les utilisateurs', 'Créer, activer et rattacher les comptes.', 'users', route('admin.users.index')],
                ['Configurer les droits', 'Administrer les rôles et permissions.', 'shield-check', route('admin.roles.index')],
                ['Organiser les services', 'Départements, responsables et équipes.', 'building-2', route('intranet.departments.manage.index')],
                ['Activer les modules', 'Adapter les fonctions disponibles.', 'puzzle', route('admin.modules.index')],
            ] as [$label, $description, $icon, $url])
                <a href="{{ $url }}" class="admin-quick-card">
                    <span class="admin-quick-icon"><x-icone :nom="$icon" /></span>
                    <span class="min-w-0">
                        <span class="admin-quick-title">{{ $label }}</span>
                        <span class="admin-quick-description">{{ $description }}</span>
                    </span>
                    <x-icone nom="arrow-right" class="admin-quick-arrow" />
                </a>
            @endforeach
        </div>
    </section>

    <aside class="admin-scope-card">
        <span class="admin-scope-icon"><x-icone nom="shield" /></span>
        <p class="admin-section-kicker">Sécurité</p>
        <h2>Portée des droits</h2>
        <p>Les administrateurs système gèrent les comptes, rôles, départements et paramètres de l’application.</p>
        <div class="admin-scope-rule"></div>
        <p class="admin-scope-note"><x-icone nom="info" /> Le département « Administration » reste un département métier ordinaire.</p>
        <a href="{{ route('admin.roles.index') }}">Consulter les rôles <x-icone nom="arrow-right" /></a>
    </aside>
</div>
@endsection
