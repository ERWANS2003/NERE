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

<section id="centre-alertes" aria-labelledby="admin-alert-center" class="mt-6">
    <div class="admin-section-heading">
        <div><p class="admin-section-kicker">À traiter</p><h2 id="admin-alert-center">Centre d’alertes</h2></div>
        <a href="{{ route('admin.health') }}">Santé détaillée <x-icone nom="arrow-right" /></a>
    </div>
    @if($adminAlerts)
        <div class="grid gap-3 lg:grid-cols-2">
            @foreach($adminAlerts as $alert)
                <a href="{{ $alert['url'] }}" class="flex items-start gap-3 rounded-xl border bg-white p-4 {{ $alert['severity'] === 'critical' ? 'border-red-200' : 'border-amber-200' }}">
                    <span class="mt-0.5 {{ $alert['severity'] === 'critical' ? 'text-red-700' : 'text-amber-700' }}"><x-icone nom="triangle-alert" /></span>
                    <span><strong class="block text-sm">{{ $alert['title'] }}</strong><span class="mt-1 block text-xs text-gray-600">{{ $alert['message'] }}</span></span>
                    <x-icone nom="chevron-right" class="ml-auto text-gray-400" />
                </a>
            @endforeach
        </div>
    @else
        <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800"><x-icone nom="circle-check" /><span><strong class="block text-sm">Aucune alerte active</strong><span class="text-xs">Les contrôles techniques et les SLA ne demandent aucune action immédiate.</span></span></div>
    @endif
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

    <aside class="space-y-4">
    <div class="admin-scope-card">
        <span class="admin-scope-icon"><x-icone nom="shield" /></span>
        <p class="admin-section-kicker">Sécurité</p>
        <h2>Portée des droits</h2>
        <p>Les administrateurs système gèrent les comptes, rôles, départements et paramètres de l’application.</p>
        <div class="admin-scope-rule"></div>
        <p class="admin-scope-note"><x-icone nom="info" /> Le département « Administration » reste un département métier ordinaire.</p>
        <a href="{{ route('admin.roles.index') }}">Consulter les rôles <x-icone nom="arrow-right" /></a>
    </div>
    <div class="carte">
        <div class="flex items-center justify-between"><h2 class="font-semibold">Activité récente</h2><a href="{{ route('admin.audit.index') }}" class="text-xs font-semibold text-nere-700">Tout voir</a></div>
        <div class="mt-3 divide-y divide-gray-100">
            @forelse($recentAuditLogs as $log)<div class="py-2"><p class="text-xs font-semibold text-gray-800">{{ $log->action }}</p><p class="mt-1 text-[11px] text-gray-500">{{ $log->actor?->name ?? 'Système' }} · {{ $log->created_at->diffForHumans() }}</p></div>@empty<p class="py-3 text-xs text-gray-500">Le journal commencera à se remplir aux prochaines opérations.</p>@endforelse
        </div>
    </div>
    </aside>
</div>
@endsection
