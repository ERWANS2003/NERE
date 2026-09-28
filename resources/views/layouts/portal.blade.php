<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="api-token" content="{{ auth()->user()->createToken('web')->plainTextToken }}">
    @endauth
    <title>@yield('titre', 'Portail') · ITSM Néré Mining</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-nere-mining.png') }}">

    {{-- Resolve the theme before first paint so there is no flash of the wrong palette. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('nere-theme');
                var theme = stored
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
    @yield('styles')
</head>
<body class="min-h-screen antialiased">

@php
    $user = auth()->user();
    $estDemandeur = $user?->hasRole('demandeur') ?? false;
    $estSuperviseur = $user && ! $estDemandeur;                 // technicien, dsi, directeur, admin
    $estRoleDirection = $user && ($user->hasRole('admin') || $user->hasRole('dsi') || $user->hasRole('directeur_departement'));
    $estAdmin = $user?->hasRole('admin') ?? false;
@endphp

<div x-data="{ sidebar: false }" class="flex min-h-screen">

    {{-- ══════════════════════════ SIDEBAR ══════════════════════════ --}}
    <div
        x-show="sidebar"
        x-cloak
        @click="sidebar = false"
        class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-[268px] -translate-x-full flex-col border-r border-white/5 transition-transform duration-200 lg:static lg:translate-x-0 nm-no-print"
        :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
        style="background-color: var(--surface-nav);"
    >
        {{-- Brand --}}
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                <span class="nm-brand-gradient flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl shadow-brand">
                    <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining"
                         class="h-8 w-8 rounded-md bg-white object-contain p-0.5">
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-[15px] font-bold leading-tight text-white">ITSM</span>
                    <span class="block truncate text-[11px] font-semibold uppercase tracking-wider text-accent-300">Néré Mining</span>
                </span>
            </a>
            <button
                type="button"
                @click="sidebar = false"
                class="ml-auto rounded-lg p-1.5 text-white/50 hover:bg-white/10 hover:text-white lg:hidden"
                aria-label="Fermer le menu"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="nm-scroll-thin flex-1 space-y-0.5 overflow-y-auto px-3 py-3">
            <a href="{{ route('dashboard') }}"
               class="nm-nav-link {{ request()->routeIs('dashboard') ? 'nm-nav-active' : '' }}">
                <x-icon name="home" class="h-5 w-5 flex-shrink-0" />
                <span>Tableau de bord</span>
            </a>

            <a href="{{ route('tickets.index') }}"
               class="nm-nav-link {{ request()->routeIs('tickets.*') && ! request()->routeIs('tickets.create') ? 'nm-nav-active' : '' }}">
                <x-icon name="ticket" class="h-5 w-5 flex-shrink-0" />
                <span>{{ $estDemandeur ? 'Mes demandes' : 'Mes tickets' }}</span>
            </a>

            <a href="{{ route('search.index') }}"
               class="nm-nav-link {{ request()->routeIs('search.*') ? 'nm-nav-active' : '' }}">
                <x-icon name="search" class="h-5 w-5 flex-shrink-0" />
                <span>Recherche avancée</span>
            </a>

            @if ($estSuperviseur)
                <div class="nm-nav-heading">Pilotage</div>

                <a href="{{ route('kanban.index') }}"
                   class="nm-nav-link {{ request()->routeIs('kanban.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="kanban" class="h-5 w-5 flex-shrink-0" />
                    <span>Tableau Kanban</span>
                </a>

                <a href="{{ route('sla.index') }}"
                   class="nm-nav-link {{ request()->routeIs('sla.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="clock" class="h-5 w-5 flex-shrink-0" />
                    <span>Gestion des SLA</span>
                </a>

                <a href="{{ route('templates.index') }}"
                   class="nm-nav-link {{ request()->routeIs('templates.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="template" class="h-5 w-5 flex-shrink-0" />
                    <span>Modèles de tickets</span>
                </a>

                <a href="{{ route('assets.index') }}"
                   class="nm-nav-link {{ request()->routeIs('assets.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="hardware" class="h-5 w-5 flex-shrink-0" />
                    <span>Inventaire des actifs</span>
                </a>
            @endif

            <div class="nm-nav-heading">Services</div>

            <a href="{{ route('services.index') }}"
               class="nm-nav-link {{ request()->routeIs('services.index') || request()->routeIs('services.show') ? 'nm-nav-active' : '' }}">
                <x-icon name="grid" class="h-5 w-5 flex-shrink-0" />
                <span>Catalogue de services</span>
            </a>

            <a href="{{ route('services.my-requests') }}"
               class="nm-nav-link {{ request()->routeIs('services.my-requests') ? 'nm-nav-active' : '' }}">
                <x-icon name="file-text" class="h-5 w-5 flex-shrink-0" />
                <span>Mes demandes de service</span>
            </a>

            <a href="{{ route('knowledge.index') }}"
               class="nm-nav-link {{ request()->routeIs('knowledge.*') ? 'nm-nav-active' : '' }}">
                <x-icon name="book" class="h-5 w-5 flex-shrink-0" />
                <span>Base de connaissances</span>
            </a>

            @if ($estRoleDirection)
                <div class="nm-nav-heading">Direction</div>

                <a href="{{ route('reports.index') }}"
                   class="nm-nav-link {{ request()->routeIs('reports.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="chart-bar" class="h-5 w-5 flex-shrink-0" />
                    <span>Rapports &amp; analyses</span>
                </a>

                <a href="{{ route('department.index') }}"
                   class="nm-nav-link {{ request()->routeIs('department.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="users" class="h-5 w-5 flex-shrink-0" />
                    <span>Mon département</span>
                </a>
            @endif

            @can('safety.view')
                <a href="{{ route('safety.index') }}"
                   class="nm-nav-link {{ request()->routeIs('safety.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="shield-check" class="h-5 w-5 flex-shrink-0" />
                    <span>Incidents HSE</span>
                </a>
            @endcan

            @if ($estAdmin)
                <div class="nm-nav-heading">Administration</div>

                <a href="{{ route('admin.users.index') }}"
                   class="nm-nav-link {{ request()->routeIs('admin.users.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="user" class="h-5 w-5 flex-shrink-0" />
                    <span>Utilisateurs</span>
                </a>

                <a href="{{ route('admin.roles.index') }}"
                   class="nm-nav-link {{ request()->routeIs('admin.roles.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="lock" class="h-5 w-5 flex-shrink-0" />
                    <span>Rôles &amp; permissions</span>
                </a>

                <a href="{{ route('admin.settings.departments') }}"
                   class="nm-nav-link {{ request()->routeIs('admin.settings.*') ? 'nm-nav-active' : '' }}">
                    <x-icon name="settings" class="h-5 w-5 flex-shrink-0" />
                    <span>Paramètres</span>
                </a>
            @endif
        </nav>

        {{-- Account --}}
        @auth
            <div class="border-t border-white/10 p-3">
                <div class="flex items-center gap-3 rounded-xl px-2 py-2">
                    <span class="nm-spark-gradient flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-[13px] font-bold text-accent-950">
                        {{ Str::upper(Str::substr($user->name, 0, 2)) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[13px] font-semibold text-white">{{ $user->name }}</span>
                        <span class="block truncate text-[11px] text-white/50">
                            {{ $user->role?->nom ?? 'Utilisateur' }}
                            @if ($user->departement)
                                · {{ $user->departement->nom }}
                            @endif
                        </span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-[13px] font-medium text-white/60 transition hover:bg-white/10 hover:text-accent-300">
                        <x-icon name="logout" class="h-4 w-4" />
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    {{-- ══════════════════════════ MAIN ══════════════════════════ --}}
    <div class="flex min-w-0 flex-1 flex-col">

        <header class="sticky top-0 z-20 border-b nm-no-print"
                style="background-color: color-mix(in srgb, var(--surface-card) 88%, transparent); backdrop-filter: blur(10px); border-color: var(--line-subtle);">
            <div class="flex items-center gap-3 px-4 py-3.5 sm:px-6">
                <button type="button" @click="sidebar = true"
                        class="rounded-lg p-2 lg:hidden"
                        style="color: var(--text-muted);"
                        aria-label="Ouvrir le menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div class="min-w-0 flex-1">
                    <h1 class="nm-page-title truncate">@yield('titre', 'Tableau de bord')</h1>
                    <p class="nm-page-subtitle truncate">@yield('sous-titre', 'Bienvenue sur votre portail ITSM')</p>
                </div>

                <div class="flex flex-shrink-0 items-center gap-2">
                    <x-theme-toggle />

                    @include('partials.notification-bell')

                    <a href="{{ route('tickets.create') }}" class="nm-btn nm-btn-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        <span class="hidden sm:inline">Nouvelle demande</span>
                    </a>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        @if (session('success') || session('error') || $errors->any())
            <div class="space-y-2 px-4 pt-4 sm:px-6">
                @foreach ([
                    ['type' => 'success', 'message' => session('success')],
                    ['type' => 'danger',  'message' => session('error')],
                ] as $flash)
                    @if ($flash['message'])
                        <div class="nm-badge flex items-start gap-2.5 rounded-xl px-4 py-3 text-[13px] font-normal nm-badge-{{ $flash['type'] }} fade-in-up">
                            <x-icon :name="$flash['type'] === 'success' ? 'check-circle' : 'alert'" class="mt-px h-4 w-4 flex-shrink-0" />
                            <span>{{ $flash['message'] }}</span>
                        </div>
                    @endif
                @endforeach

                @if ($errors->any())
                    <div class="nm-badge flex items-start gap-2.5 rounded-xl px-4 py-3 text-[13px] font-normal nm-badge-danger fade-in-up">
                        <x-icon name="alert" class="mt-px h-4 w-4 flex-shrink-0" />
                        <div>
                            <p class="font-semibold">Veuillez corriger les erreurs suivantes :</p>
                            <ul class="mt-1 list-inside list-disc space-y-0.5 opacity-90">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <main class="flex-1 px-4 py-5 sm:px-6 sm:py-6">
            @yield('contenu')
        </main>

        <footer class="border-t px-6 py-4 text-center text-[11px] nm-no-print"
               style="border-color: var(--line-subtle); color: var(--text-subtle);">
            ITSM Néré Mining — Plateforme de gestion des services d'entreprise
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
