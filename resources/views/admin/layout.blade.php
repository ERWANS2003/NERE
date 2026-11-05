<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-32.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1b2126">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-icon-192.png') }}">
    <title>@yield('title') - {{ $siteName ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell">
    @php
        $isSystemAdmin = auth()->user()->is_super_admin;
        $userInitials = collect(preg_split('/\s+/', trim(auth()->user()->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');

        $navigationGroups = [];

        if ($isSystemAdmin) {
            $navigationGroups[] = [
                'label' => 'Pilotage',
                'items' => [
                    ['label' => 'Vue d’ensemble', 'icon' => 'gauge', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard']],
                    ['label' => 'Centre d’alertes', 'icon' => 'triangle-alert', 'route' => 'admin.dashboard', 'fragment' => 'centre-alertes', 'match' => []],
                    ['label' => 'Rapports', 'icon' => 'chart-column', 'route' => 'admin.reports.index', 'match' => ['admin.reports.*']],
                    ['label' => 'Journal d’audit', 'icon' => 'list-checks', 'route' => 'admin.audit.index', 'match' => ['admin.audit.*']],
                    ['label' => 'Santé du serveur', 'icon' => 'server', 'route' => 'admin.health', 'match' => ['admin.health*']],
                ],
            ];
            $navigationGroups[] = [
                'label' => 'Organisation',
                'items' => [
                    ['label' => 'Utilisateurs', 'icon' => 'users', 'route' => 'admin.users.index', 'match' => ['admin.users.*']],
                    ['label' => 'Rôles et droits', 'icon' => 'user-cog', 'route' => 'admin.roles.index', 'match' => ['admin.roles.*']],
                    ['label' => 'Départements', 'icon' => 'building-2', 'route' => 'intranet.departments.manage.index', 'match' => ['intranet.departments.manage.*']],
                    ['label' => 'Congés', 'icon' => 'calendar-check', 'route' => 'admin.time-off.index', 'match' => ['admin.time-off.*']],
                ],
            ];
            $contentItems = [
                ['label' => 'Modules', 'icon' => 'puzzle', 'route' => 'admin.modules.index', 'match' => ['admin.modules.*']],
                ['label' => 'Ressources', 'icon' => 'folder-open', 'route' => 'admin.resources.index', 'match' => ['admin.resources.*']],
                ['label' => 'Tickets support', 'icon' => 'ticket', 'route' => 'admin.tickets.index', 'match' => ['admin.tickets.*']],
                ['label' => 'Annonces', 'icon' => 'megaphone', 'route' => 'admin.announcements.index', 'match' => ['admin.announcements.*']],
                ['label' => 'Calendrier', 'icon' => 'calendar-days', 'route' => 'admin.calendar.index', 'match' => ['admin.calendar.*']],
            ];
            if (app(\App\Support\IntranetModuleRegistry::class)->enabled('knowledge_base')) {
                $contentItems[] = ['label' => 'Base de connaissances', 'icon' => 'book-open', 'route' => 'intranet.knowledge-base.index', 'match' => ['intranet.knowledge-base.*']];
            }
            if (app(\App\Support\IntranetModuleRegistry::class)->enabled('forms')) {
                $contentItems[] = ['label' => 'Formulaires et services', 'icon' => 'file-text', 'route' => 'intranet.forms.index', 'match' => ['intranet.forms.*']];
            }
            $navigationGroups[] = ['label' => 'Contenus et services', 'items' => $contentItems];
            $navigationGroups[] = [
                'label' => 'Configuration',
                'items' => [
                    ['label' => 'Paramètres', 'icon' => 'settings', 'route' => 'admin.settings', 'match' => ['admin.settings*', 'admin.ldap.*']],
                    ['label' => 'Avis utilisateurs', 'icon' => 'inbox', 'route' => 'admin.feedback.index', 'match' => ['admin.feedback.*']],
                ],
            ];
        } else {
            $navigationGroups[] = [
                'label' => 'Organisation',
                'items' => [
                    ['label' => 'Départements', 'icon' => 'building-2', 'route' => 'intranet.departments.manage.index', 'match' => ['intranet.departments.manage.*']],
                ],
            ];
        }
    @endphp

    <div class="admin-layout">
        <button type="button" class="admin-sidebar-backdrop" data-admin-sidebar-close aria-label="Fermer le menu"></button>

        <aside id="admin-sidebar" class="admin-sidebar" aria-label="Navigation d’administration">
            <div class="admin-sidebar-brand">
                <a href="{{ $isSystemAdmin ? route('admin.dashboard') : route('intranet.departments.manage.index') }}" class="admin-brand-link">
                    <span class="admin-brand-mark" aria-hidden="true">
                        <img src="{{ asset('images/logo-nere-mining.png') }}" alt="">
                    </span>
                    <span class="min-w-0">
                        <span class="admin-brand-eyebrow">NÉRÉ Mining</span>
                        <span class="admin-brand-title">{{ $isSystemAdmin ? 'Administration' : 'Gestion' }}</span>
                    </span>
                </a>
                <button type="button" class="admin-icon-button lg:hidden" data-admin-sidebar-close aria-label="Fermer le menu">
                    <x-icone nom="x" />
                </button>
            </div>

            <div class="admin-sidebar-context">
                <span class="admin-context-icon"><x-icone :nom="$isSystemAdmin ? 'shield' : 'building-2'" /></span>
                <span>
                    <span class="admin-context-label">Espace sécurisé</span>
                    <span class="admin-context-value">{{ $isSystemAdmin ? 'Administration système' : 'Gestion des départements' }}</span>
                </span>
            </div>

            <nav class="admin-navigation">
                @foreach($navigationGroups as $group)
                    <div class="admin-nav-group">
                        <p class="admin-nav-label">{{ $group['label'] }}</p>
                        <div class="space-y-1">
                            @foreach($group['items'] as $item)
                                @php($active = collect($item['match'])->contains(fn ($pattern) => request()->routeIs($pattern)))
                                <a href="{{ route($item['route']).(isset($item['fragment']) ? '#'.$item['fragment'] : '') }}"
                                   class="admin-nav-link {{ $active ? 'is-active' : '' }}"
                                   @if($active) aria-current="page" @endif>
                                    <x-icone :nom="$item['icon']" />
                                    <span>{{ $item['label'] }}</span>
                                    @if($active)<span class="admin-nav-indicator" aria-hidden="true"></span>@endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="admin-sidebar-footer">
                <a href="{{ route('intranet.dashboard') }}" class="admin-back-link">
                    <x-icone nom="arrow-left" />
                    <span>Retour à l’intranet</span>
                </a>
                <div class="admin-environment">
                    <span class="admin-environment-dot"></span>
                    <span>{{ $siteName ?? config('app.name') }}</span>
                </div>
            </div>
        </aside>

        <div class="admin-main-column">
            <header class="admin-header">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="admin-icon-button lg:hidden" data-admin-sidebar-open aria-controls="admin-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                        <span class="admin-menu-lines" aria-hidden="true"><span></span><span></span><span></span></span>
                    </button>
                    <div class="min-w-0">
                        <p class="admin-page-eyebrow">{{ $isSystemAdmin ? 'Console d’administration' : 'Espace de gestion' }}</p>
                        <h1 class="admin-page-title">@yield('title')</h1>
                    </div>
                </div>

                <div class="admin-user">
                    <span class="admin-user-avatar" aria-hidden="true">{{ $userInitials }}</span>
                    <span class="hidden min-w-0 sm:block">
                        <span class="admin-user-name">{{ auth()->user()->name }}</span>
                        <span class="admin-user-role">{{ $isSystemAdmin ? 'Administrateur système' : 'Gestionnaire' }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="admin-icon-button" title="Se déconnecter" aria-label="Se déconnecter">
                            <x-icone nom="log-out" />
                        </button>
                    </form>
                </div>
            </header>

            <main id="admin-main-content" class="admin-content" tabindex="-1">
                <div class="admin-content-inner" data-page-skeleton-root>
                    @include('partials.page-skeleton')
                    @if(session('success'))
                        <div role="status" class="admin-alert admin-alert-success">
                            <span class="admin-alert-icon"><x-icone nom="circle-check" /></span>
                            <div><p class="font-semibold">Opération réussie</p><p>{{ session('success') }}</p></div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div role="alert" class="admin-alert admin-alert-error">
                            <span class="admin-alert-icon"><x-icone nom="circle-alert" /></span>
                            <div><p class="font-semibold">Une erreur est survenue</p><p>{{ session('error') }}</p></div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div role="alert" class="admin-alert admin-alert-error">
                            <span class="admin-alert-icon"><x-icone nom="circle-alert" /></span>
                            <div>
                                <p class="font-semibold">Vérifiez les informations saisies</p>
                                <ul class="mt-1 list-inside list-disc">
                                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
