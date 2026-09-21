<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="api-token" content="{{ auth()->user()->createToken('web')->plainTextToken }}">
    @endauth
    <title>@yield('titre', 'Portail') · ITSM Néré Mining</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-nere-mining.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Néré Mining Brand Colors
                        primary: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        // Enhanced mining/charcoal theme
                        dark: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827',
                            950: '#030712',
                        },
                        // Mining accent colors
                        ore: {
                            light: '#fbbf24',
                            main: '#f59e0b',
                            dark: '#d97706',
                        },
                        slate: {
                            main: '#1f2937',
                        }
                    },
                    // Enhanced font
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    },
                    // Box shadows for mining theme
                    boxShadow: {
                        'ore-glow': '0 0 20px rgba(245, 158, 11, 0.15)',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }

        :root {
            --portal-ink: #17211f;
            --portal-ink-soft: #22302d;
            --portal-surface: #f6f5f1;
            --portal-panel: #ffffff;
            --portal-line: #e2e4df;
            --portal-muted: #71807b;
            --portal-copper: #b96b2c;
            --portal-copper-dark: #8f4b1d;
            --portal-teal: #176b67;
            --portal-shadow: 0 18px 45px rgba(23, 33, 31, .08);
        }
        
        body {
            font-family: 'Instrument Sans', 'Segoe UI', sans-serif;
            background: var(--portal-surface);
            color: var(--portal-ink);
            letter-spacing: 0;
        }

        .portal-shell { background: var(--portal-surface); }
        .portal-sidebar {
            width: 17.5rem;
            background: var(--portal-ink);
            border-right: 1px solid rgba(255,255,255,.08);
            box-shadow: 12px 0 35px rgba(23,33,31,.08);
            z-index: 40;
        }
        .portal-brand {
            padding: 1.35rem 1.35rem 1.1rem;
            border-bottom: 1px solid rgba(255,255,255,.09);
        }
        .portal-brand-mark {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: .85rem;
            background: linear-gradient(145deg, #e2a05b, var(--portal-copper-dark));
            box-shadow: 0 8px 18px rgba(185,107,44,.25);
        }
        .portal-nav { padding: 1rem .8rem; }
        .portal-nav-label {
            color: rgba(239,242,237,.42);
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            padding: .85rem .8rem .45rem;
        }
        .sidebar-link {
            gap: .7rem;
            padding: .7rem .8rem;
            color: rgba(239,242,237,.72);
            border: 1px solid transparent;
            border-radius: .7rem;
            font-size: .84rem;
        }
        .sidebar-link svg { color: rgba(239,242,237,.46); transition: color .15s; }
        .sidebar-link:hover {
            background: rgba(255,255,255,.07);
            color: #fff;
            border-color: rgba(255,255,255,.08);
        }
        .sidebar-link:hover svg, .sidebar-link.active svg { color: #e2a05b; }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(185,107,44,.24), rgba(185,107,44,.08));
            border-color: rgba(226,160,91,.26);
            color: #fff;
            box-shadow: inset 3px 0 0 #e2a05b;
        }
        .portal-user-card { border-color: rgba(255,255,255,.09) !important; }
        .portal-main { background: var(--portal-surface); }
        .portal-topbar {
            background: rgba(255,255,255,.9);
            border-bottom: 1px solid var(--portal-line);
            box-shadow: 0 1px 0 rgba(255,255,255,.8);
        }
        .portal-topbar h2 { color: var(--portal-ink) !important; font-size: 1.08rem; letter-spacing: -.01em; }
        .portal-topbar p { color: var(--portal-muted) !important; }
        .portal-content { background: var(--portal-surface); }
        .portal-content > div { max-width: 1500px; margin: 0 auto; }
        .portal-content .bg-dark-800, .portal-content .bg-dark-900,
        .portal-content .bg-white {
            background: var(--portal-panel) !important;
            border-color: var(--portal-line) !important;
            box-shadow: var(--portal-shadow);
        }
        .portal-content .text-white, .portal-content .text-gray-100,
        .portal-content .text-gray-200, .portal-content .text-gray-300 { color: var(--portal-ink) !important; }
        .portal-content .text-gray-400, .portal-content .text-gray-500 { color: var(--portal-muted) !important; }
        .portal-content .bg-dark-700, .portal-content .bg-dark-600 { background: #eef0ec !important; }
        .portal-content .border-dark-700, .portal-content .border-dark-800 { border-color: var(--portal-line) !important; }
        .portal-content input, .portal-content select, .portal-content textarea {
            background: #fbfcfa !important;
            color: var(--portal-ink) !important;
            border-color: #d6dbd5 !important;
        }
        .portal-content input::placeholder, .portal-content textarea::placeholder { color: #9aa7a1 !important; }
        .portal-content .bg-primary-600 { background: var(--portal-copper) !important; }
        .portal-content .hover\:bg-primary-700:hover { background: var(--portal-copper-dark) !important; }
        .portal-content .text-primary-400, .portal-content .text-primary-300 { color: var(--portal-copper-dark) !important; }
        .portal-content table thead { background: #f0f2ee !important; }
        .portal-content table tbody tr:hover { background: #f5f7f3 !important; }
        .portal-content .rounded-xl { border-radius: .8rem; }
        .portal-content .rounded-lg { border-radius: .65rem; }
        .portal-content .shadow-sm, .portal-content .shadow-md { box-shadow: var(--portal-shadow); }
        .portal-mobile-toggle { display: none; }
        .portal-overlay { display: none; }

        @media (max-width: 1024px) {
            .portal-sidebar { width: 16rem; }
        }
        @media (max-width: 768px) {
            .portal-sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                transform: translateX(-105%);
                transition: transform .2s ease;
            }
            .portal-sidebar.is-open { transform: translateX(0); }
            .portal-overlay.is-open { display: block; position: fixed; inset: 0; background: rgba(23,33,31,.42); z-index: 30; }
            .portal-mobile-toggle { display: inline-flex; }
            .portal-topbar { padding-left: 1rem !important; padding-right: 1rem !important; }
            .portal-topbar .topbar-copy { min-width: 0; }
            .portal-topbar .topbar-copy h2 { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .portal-topbar .topbar-copy p { display: none; }
            .portal-topbar .quick-action-label { display: none; }
            .portal-content > div { padding-left: 1rem !important; padding-right: 1rem !important; }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }

    </style>
    @stack('styles')
</head>
<body class="bg-dark-900 text-gray-100 min-h-screen">
    
    <!-- Layout with Sidebar -->
    <div class="portal-shell flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
        
        <!-- Sidebar -->
        <div class="portal-overlay" :class="sidebarOpen ? 'is-open' : ''" @click="sidebarOpen = false"></div>
        <aside class="portal-sidebar flex flex-col" :class="sidebarOpen ? 'is-open' : ''">
            <!-- Logo -->
            <div class="portal-brand">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <div class="portal-brand-mark flex items-center justify-center shrink-0">
                        <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining" class="w-8 h-8 object-contain">
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-white leading-tight">ITSM</h1>
                        <p class="text-xs text-primary-300 font-semibold">Néré Mining</p>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="portal-nav flex-1 space-y-1 overflow-y-auto">
                @auth
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" 
                       class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span>Tableau de bord</span>
                    </a>

                    <!-- Tickets -->
                    <a href="{{ route('tickets.index') }}" 
                       class="sidebar-link {{ request()->routeIs('tickets.*') && !request()->routeIs('search.*') && !request()->routeIs('kanban.*') && !request()->routeIs('sla.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span>Mes tickets</span>
                    </a>

                    <!-- Advanced Search -->
                    <a href="{{ route('search.index') }}" 
                       class="sidebar-link {{ request()->routeIs('search.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span>Recherche Avancée</span>
                    </a>

                    <!-- Ticket Templates -->
                    <a href="{{ route('templates.index') }}" 
                       class="sidebar-link {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Modèles de Tickets</span>
                    </a>

                    @if(auth()->user()->hasPermission('tickets.view'))
                        <!-- All Tickets (Admin/Tech) -->
                        <a href="{{ route('tickets.index') }}" 
                           class="sidebar-link {{ request()->routeIs('tickets.index') && !request()->routeIs('kanban.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <span>Tous les tickets</span>
                        </a>

                        <!-- Kanban Board -->
                        <a href="{{ route('kanban.index') }}" 
                           class="sidebar-link {{ request()->routeIs('kanban.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5a2 2 0 00-2 2v14a2 2 0 002 2h4m0-21h10a2 2 0 012 2v14a2 2 0 01-2 2m-10-21v21m0-21H9m10 0h4a2 2 0 012 2v14a2 2 0 01-2 2h-4m0-21v21"></path>
                            </svg>
                            <span>Tableau Kanban</span>
                        </a>

                        <!-- SLA Management -->
                        <a href="{{ route('sla.index') }}" 
                           class="sidebar-link {{ request()->routeIs('sla.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Gestion SLA</span>
                        </a>
                    @endif

                    <!-- Knowledge Base -->
                    <a href="{{ route('knowledge.index') }}" 
                       class="sidebar-link {{ request()->routeIs('knowledge.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>Base de connaissances</span>
                    </a>

                    <a href="{{ route('services.index') }}"
                       class="sidebar-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10"></path>
                        </svg>
                        <span>Catalogue de services</span>
                    </a>

                    @if(auth()->user()->hasPermission('safety.view'))
                        <a href="{{ route('safety.index') }}"
                           class="sidebar-link {{ request()->routeIs('safety.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l8 4v5c0 4.8-3.4 8.7-8 10-4.6-1.3-8-5.2-8-10V7l8-4zM12 8v4m0 3h.01"></path>
                            </svg>
                            <span>HSE & conformité</span>
                        </a>
                    @endif

                    @hasRole('admin')
                        <!-- Divider -->
                        <div class="my-4 border-t border-dark-800"></div>

                        <!-- Admin Section -->
                        <div class="portal-nav-label">
                            <p class="text-xs font-semibold text-gray-500 uppercase">Administration</p>
                        </div>

                        <a href="{{ route('admin.users.index') }}" 
                           class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span>Utilisateurs</span>
                        </a>

                        <a href="{{ route('admin.roles.index') }}" 
                           class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span>Rôles & Permissions</span>
                        </a>

                        <a href="{{ route('admin.settings.departments') }}" 
                           class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Paramètres</span>
                        </a>
                    @endhasRole
                @endauth
            </nav>

            <!-- User Profile -->
            @auth
            <div class="portal-user-card p-4 border-t">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-linear-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                        <span class="text-white font-semibold text-sm">{{ substr(auth()->user()->name, 0, 2) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->role?->nom ?? 'Utilisateur' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-400 hover:text-white hover:bg-dark-800 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
            @endauth
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Top Bar -->
            <header class="portal-topbar px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="topbar-copy flex items-center gap-3">
                        <button type="button" class="portal-mobile-toggle p-2 rounded-lg text-gray-600 hover:bg-gray-100" @click="sidebarOpen = true" aria-label="Ouvrir le menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <div>
                        <h2 class="text-xl font-semibold text-white">@yield('titre', 'Tableau de bord')</h2>
                        <p class="text-sm text-gray-400 mt-0.5">@yield('sous-titre', 'Bienvenue sur votre portail ITSM')</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Notification Bell -->
                        @include('partials.notification-bell')
                        
                        <!-- Quick Actions -->
                        <a href="{{ route('tickets.create') }}" 
                           class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span class="quick-action-label">Nouvelle demande</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div class="px-6 py-4">
                @if(session('success'))
                    <div class="bg-green-900/50 border border-green-700 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-green-100">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-900/50 border border-red-700 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-red-100">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-900/50 border border-red-700 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium text-red-100 mb-2">Veuillez corriger les erreurs suivantes:</p>
                                <ul class="list-disc list-inside text-sm text-red-200 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Main Content -->
            <main class="portal-content flex-1 overflow-y-auto">
                @yield('contenu')
            </main>

        </div>
    </div>

    @stack('scripts')
</body>
</html>
