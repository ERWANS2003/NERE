<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titre', 'Tableau de bord') · ITSM Néré Mining</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-nere-mining.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/theme.css'])
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: var(--bg-50);
            color: var(--text-800);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--surface-white);
            border-right: 1px solid var(--border-200);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 40;
        }

        .sidebar-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-200);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .sidebar-header img {
            height: 40px;
            width: auto;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: var(--spacing-md) 0;
        }

        .nav-section {
            padding: var(--spacing-md) var(--spacing-lg) var(--spacing-sm);
        }

        .nav-section-title {
            font-size: var(--font-size-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-500);
            margin-bottom: var(--spacing-sm);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-sm) var(--spacing-lg);
            color: var(--text-600);
            text-decoration: none;
            font-size: var(--font-size-sm);
            transition: all var(--transition-fast);
            border-left: 3px solid transparent;
            margin-bottom: 2px;
        }

        .nav-link:hover {
            background-color: var(--surface-hover);
            color: var(--accent-600);
        }

        .nav-link.active {
            background-color: var(--accent-200);
            border-left-color: var(--accent-500);
            color: var(--accent-600);
            font-weight: 600;
        }

        .nav-link svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-200);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background-color: var(--surface-light);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-md);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--accent-500), var(--accent-600));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-900);
            font-weight: 700;
            font-size: var(--font-size-sm);
            flex-shrink: 0;
        }

        .user-info h3 {
            margin: 0;
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-800);
        }

        .user-info p {
            margin: 2px 0 0 0;
            font-size: var(--font-size-xs);
            color: var(--text-500);
        }

        .logout-btn {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            background-color: var(--surface-light);
            border: 1px solid var(--border-200);
            border-radius: var(--radius-md);
            color: var(--text-600);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .logout-btn:hover {
            background-color: var(--status-critical);
            color: var(--surface-white);
            border-color: var(--status-critical);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
        }

        .header {
            position: sticky;
            top: 0;
            z-index: 30;
            background-color: var(--surface-white);
            border-bottom: 1px solid var(--border-200);
            padding: var(--spacing-lg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
        }

        .header h1 {
            margin: 0;
            font-family: 'Space Grotesk', sans-serif;
            font-size: var(--font-size-2xl);
            font-weight: 600;
            color: var(--text-900);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        /* Page Content */
        .page-content {
            flex: 1;
            padding: var(--spacing-xl);
            overflow-y: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: var(--shadow-xl);
            }
        }

        /* Utility */
        .flex { display: flex; }
        .items-center { align-items: center; }
        .gap-md { gap: var(--spacing-md); }
        .p-lg { padding: var(--spacing-lg); }
        .rounded-lg { border-radius: var(--radius-lg); }
        .bg-surface { background-color: var(--surface-white); }
        .text-primary { color: var(--text-800); }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining">
            </div>

            <nav class="sidebar-nav">
                @if(auth()->check())
                    @can('tickets.view')
                        <div class="nav-section">
                            <div class="nav-section-title">Opérations</div>
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <x-icon name="dashboard" size="md" />
                                <span>Tableau de bord</span>
                            </a>
                            <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                                <x-icon name="tickets" size="md" />
                                <span>Tickets</span>
                            </a>
                        </div>
                    @endcan

                    @can('assets.view')
                        <div class="nav-section">
                            <div class="nav-section-title">Ressources</div>
                            <a href="{{ route('assets.index') }}" class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                                <x-icon name="assets" size="md" />
                                <span>Actifs</span>
                            </a>
                        </div>
                    @endcan

                    @can('teams.view')
                        <div class="nav-section">
                            <div class="nav-section-title">Gestion</div>
                            <a href="{{ route('department.index') }}" class="nav-link {{ request()->routeIs('department.*') ? 'active' : '' }}">
                                <x-icon name="teams" size="md" />
                                <span>Départements</span>
                            </a>
                        </div>
                    @endcan

                    @can('safety.view')
                        <div class="nav-section">
                            <div class="nav-section-title">Sécurité</div>
                            <a href="#" class="nav-link">
                                <x-icon name="safety" size="md" />
                                <span>Incidents</span>
                            </a>
                        </div>
                    @endcan

                    @can('reports.view')
                        <div class="nav-section">
                            <div class="nav-section-title">Rapports</div>
                            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                <x-icon name="reports" size="md" />
                                <span>Rapports</span>
                            </a>
                        </div>
                    @endcan

                    @can('admin.access')
                        <div class="nav-section">
                            <div class="nav-section-title">Administration</div>
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <x-icon name="users" size="md" />
                                <span>Utilisateurs</span>
                            </a>
                        </div>
                    @endcan
                @endif
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                @if(auth()->check())
                    <div class="user-card">
                        <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div class="user-info">
                            <h3>{{ auth()->user()->name }}</h3>
                            <p>{{ auth()->user()->role?->nom ?? 'Utilisateur' }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <x-icon name="logout" size="sm" class="inline-block mr-2" />
                            Déconnexion
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1>@yield('titre', 'Tableau de bord')</h1>
                <div class="header-actions">
                    @yield('header-actions')
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                @yield('contenu')
            </div>
        </main>
    </div>

    @yield('scripts')
</body>
</html>
