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
                        // Néré Mining Brand Colors - Inspired by the logo
                        // Gold/Yellow from the sun (primary)
                        gold: {
                            50: '#fffef2',
                            100: '#fffce0',
                            200: '#fff9c0',
                            300: '#fff49e',
                            400: '#ffed7c',
                            500: '#ffd700', // Main gold
                            600: '#ffcc00',
                            700: '#e6b800',
                            800: '#ccaa00',
                            900: '#b39500',
                        },
                        // Red/Crimson from the rays (accent)
                        crimson: {
                            50: '#ffe8e8',
                            100: '#ffd1d1',
                            200: '#ffadad',
                            300: '#ff8989',
                            400: '#ff6565',
                            500: '#d32f2f', // Main crimson/red
                            600: '#c62828',
                            700: '#b71c1c',
                            800: '#a31a1a',
                            900: '#8b1818',
                        },
                        // Deep charcoal/black for background
                        charcoal: {
                            50: '#f5f5f5',
                            100: '#e8e8e8',
                            200: '#d0d0d0',
                            300: '#b8b8b8',
                            400: '#a0a0a0',
                            500: '#656565',
                            600: '#4a4a4a',
                            700: '#323232',
                            800: '#1a1a1a',
                            900: '#0d0d0d',
                            950: '#000000',
                        },
                    },
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    },
                    boxShadow: {
                        'gold-glow': '0 0 30px rgba(255, 215, 0, 0.25)',
                        'crimson-glow': '0 0 30px rgba(211, 47, 47, 0.25)',
                        'sun-shadow': '0 4px 20px rgba(0, 0, 0, 0.3)',
                    },
                    keyframes: {
                        'sun-pulse': {
                            '0%, 100%': { boxShadow: '0 0 30px rgba(255, 215, 0, 0.25)' },
                            '50%': { boxShadow: '0 0 50px rgba(255, 215, 0, 0.45)' },
                        },
                    },
                    animation: {
                        'sun-pulse': 'sun-pulse 3s ease-in-out infinite',
                    },
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'sans-serif';
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #0d0d0d; }
        ::-webkit-scrollbar-thumb { background: #d32f2f; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ffd700; }

        /* Sidebar Navigation Links */
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #a0a0a0;
            border-radius: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover {
            background-color: rgba(211, 47, 47, 0.1);
            color: #ffd700;
            border-left-color: #ffd700;
            transform: translateX(2px);
        }
        
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), rgba(211, 47, 47, 0.05));
            color: #ffd700;
            border-left-color: #ffd700;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.1);
        }

        /* Mining theme utilities */
        .mining-card {
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.8), rgba(50, 50, 50, 0.6));
            border: 1px solid rgba(255, 215, 0, 0.1);
            border-radius: 0.75rem;
            transition: all 0.3s;
        }

        .mining-card:hover {
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.15);
        }

        /* Button styles */
        .btn-gold {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.2);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.3);
        }

        .btn-crimson {
            background: linear-gradient(135deg, #d32f2f, #ff5252);
            color: white;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(211, 47, 47, 0.2);
        }

        .btn-crimson:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(211, 47, 47, 0.3);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-charcoal-950 text-charcoal-100 min-h-screen">
    
    <!-- Layout with Sidebar -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar - Deep charcoal with gold accent -->
        <aside class="w-64 bg-gradient-to-b from-charcoal-950 to-charcoal-900 border-r-2 border-gold-500 flex flex-col shadow-sun-shadow" x-data="{ open: true }">
            <!-- Logo Section - Gold accent -->
            <div class="p-6 border-b-2 border-crimson-500 bg-gradient-to-r from-charcoal-900 to-charcoal-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 hover:opacity-90 transition-opacity group">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center flex-shrink-0 shadow-gold-glow group-hover:shadow-lg transition-all">
                        <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining" class="w-8 h-8 object-contain">
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-gold-400 leading-tight">ITSM</h1>
                        <p class="text-xs text-crimson-400 font-semibold">Néré Mining</p>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                @auth
                    <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Tableau de bord</span>
                    </a>

                    <a href="{{ route('tickets.index') }}" class="sidebar-link {{ request()->routeIs('tickets.*') && !request()->routeIs('search.*') && !request()->routeIs('kanban.*') && !request()->routeIs('sla.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <span>Mes tickets</span>
                    </a>

                    <a href="{{ route('search.index') }}" class="sidebar-link {{ request()->routeIs('search.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span>Recherche Avancée</span>
                    </a>

                    <a href="{{ route('templates.index') }}" class="sidebar-link {{ request()->routeIs('templates.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Modèles de Tickets</span>
                    </a>

                    @can('view_all_tickets')
                        <a href="{{ route('tickets.index') }}" class="sidebar-link {{ request()->routeIs('tickets.index') && !request()->routeIs('kanban.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            <span>Tous les tickets</span>
                        </a>

                        <a href="{{ route('kanban.index') }}" class="sidebar-link {{ request()->routeIs('kanban.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5a2 2 0 00-2 2v14a2 2 0 002 2h4m0-21h10a2 2 0 012 2v14a2 2 0 01-2 2m-10-21v21m0-21H9m10 0h4a2 2 0 012 2v14a2 2 0 01-2 2h-4m0-21v21"></path></svg>
                            <span>Tableau Kanban</span>
                        </a>

                        <a href="{{ route('sla.index') }}" class="sidebar-link {{ request()->routeIs('sla.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Gestion SLA</span>
                        </a>
                    @endcan

                    <a href="{{ route('knowledge.index') }}" class="sidebar-link {{ request()->routeIs('knowledge.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <span>Base de connaissances</span>
                    </a>

                    @hasRole('admin')
                        <div class="my-4 border-t border-crimson-500/30"></div>
                        <div class="px-4 py-2">
                            <p class="text-xs font-semibold text-gold-400 uppercase tracking-wider">Administration</p>
                        </div>

                        <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            <span>Utilisateurs</span>
                        </a>

                        <a href="{{ route('admin.roles.index') }}" class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span>Rôles & Permissions</span>
                        </a>

                        <a href="{{ route('admin.settings.departments') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Paramètres</span>
                        </a>
                    @endhasRole
                @endauth
            </nav>

            <!-- User Profile -->
            @auth
            <div class="p-4 border-t-2 border-crimson-500 bg-gradient-to-r from-charcoal-900 to-charcoal-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-gold-glow">
                        <span class="text-charcoal-900 font-bold text-sm">{{ substr(auth()->user()->name, 0, 2) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gold-300 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-crimson-300 truncate">{{ auth()->user()->role?->nom ?? 'Utilisateur' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-charcoal-300 hover:text-gold-400 hover:bg-charcoal-700 rounded-lg transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
            @endauth
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden bg-charcoal-900">
            
            <!-- Top Bar - Gold accent top border -->
            <header class="bg-gradient-to-r from-charcoal-900 to-charcoal-800 border-b-2 border-gold-500 px-6 py-4 shadow-sun-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-gold-400 to-crimson-400 bg-clip-text text-transparent">@yield('titre', 'Tableau de bord')</h2>
                        <p class="text-sm text-charcoal-400 mt-0.5">@yield('sous-titre', 'Bienvenue sur votre portail ITSM')</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        @include('partials.notification-bell')
                        
                        <a href="{{ route('tickets.create') }}" 
                           class="inline-flex items-center gap-2 bg-gradient-to-r from-gold-500 to-gold-600 hover:from-gold-600 hover:to-gold-700 text-charcoal-900 px-4 py-2 rounded-lg font-semibold transition-all shadow-gold-glow hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Nouvelle demande</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div class="px-6 py-4">
                @if(session('success'))
                    <div class="bg-green-900/50 border-l-4 border-green-500 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-green-100">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-crimson-900/50 border-l-4 border-crimson-500 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-crimson-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-crimson-100">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-crimson-900/50 border-l-4 border-crimson-500 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-crimson-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="flex-1">
                                <p class="font-medium text-crimson-100 mb-2">Veuillez corriger les erreurs suivantes:</p>
                                <ul class="list-disc list-inside text-sm text-crimson-200 space-y-1">
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
            <main class="flex-1 overflow-y-auto bg-charcoal-900">
                @yield('contenu')
            </main>

        </div>
    </div>

    @stack('scripts')
    <script src="{{ asset('build/assets/app.js') }}" defer></script>
    <script src="{{ asset('js/mining-animations.js') }}" defer></script>
</body>
</html>
