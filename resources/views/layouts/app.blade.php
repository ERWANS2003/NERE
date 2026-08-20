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
    <style>
        :root {
            --graphite: #1b1f23;
            --graphite-soft: #222830;
            --graphite-card: #262d36;
            --graphite-line: #383f47;
            --ivoire: #f7f5f1;
            --ambre: #c8963e;
            --ambre-clair: #e0b563;
            --rouge: #d64545;
            --vert: #3fa66b;
            --bleu: #4a90d9;
            --texte-clair: #eceae6;
            --texte-att: #a9aeb4;
            --sidebar-w: 260px;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--texte-clair);
            background: var(--graphite);
        }

        a { color: inherit; text-decoration: none; }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--graphite-soft);
            border-right: 1px solid var(--graphite-line);
            display: flex;
            flex-direction: column;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 40;
            transition: transform .25s ease;
        }

        .sidebar-marque {
            padding: 1.35rem 1.25rem 1.1rem;
            border-bottom: 1px solid var(--graphite-line);
        }

        .sidebar-marque img {
            width: 100%;
            max-width: 210px;
            height: auto;
            display: block;
        }

        .sidebar-marque .sous-titre {
            margin: 0.55rem 0 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.62rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--texte-att);
        }

        .nav {
            flex: 1;
            padding: 1rem 0.75rem;
            overflow-y: auto;
        }

        .nav-section {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.62rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--texte-att);
            padding: 0.75rem 0.75rem 0.4rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.62rem 0.75rem;
            border-radius: 8px;
            font-size: 0.88rem;
            color: var(--texte-att);
            transition: background .15s, color .15s;
            margin-bottom: 2px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.05);
            color: var(--texte-clair);
        }

        .nav-link.active {
            background: rgba(200,150,62,0.14);
            color: var(--ambre-clair);
            font-weight: 500;
        }

        .nav-link svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: 0.85;
        }

        .sidebar-pied {
            padding: 1rem 1.1rem 1.25rem;
            border-top: 1px solid var(--graphite-line);
        }

        .utilisateur {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.85rem;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--ambre), #8f6a26);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--graphite);
            flex-shrink: 0;
        }

        .utilisateur-infos .nom {
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.2;
        }

        .utilisateur-infos .role {
            font-size: 0.72rem;
            color: var(--texte-att);
        }

        .btn-deconnexion {
            display: block;
            width: 100%;
            padding: 0.55rem;
            border: 1px solid var(--graphite-line);
            border-radius: 8px;
            background: transparent;
            color: var(--texte-att);
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            cursor: pointer;
            transition: border-color .15s, color .15s;
        }

        .btn-deconnexion:hover {
            border-color: var(--ambre);
            color: var(--ambre-clair);
        }

        /* ---------- Main ---------- */
        .main {
            flex: 1;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .entete {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(27,31,35,0.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--graphite-line);
            padding: 0.9rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .entete h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.35rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .entete-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .badge-live {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--vert);
            background: rgba(63,166,107,0.12);
            border: 1px solid rgba(63,166,107,0.25);
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
        }

        .badge-live::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--vert);
            animation: pulse 2.4s ease-out infinite;
        }

        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(63,166,107,0.5); }
            70%  { box-shadow: 0 0 0 6px rgba(63,166,107,0); }
            100% { box-shadow: 0 0 0 0 rgba(63,166,107,0); }
        }

        .contenu {
            flex: 1;
            padding: 1.75rem;
        }

        .alerte {
            padding: 0.75rem 0.9rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .alerte-success {
            background: rgba(63,166,107,0.12);
            border: 1px solid rgba(63,166,107,0.3);
            color: #7fd4a0;
        }

        .alerte-erreur {
            background: rgba(214,69,69,0.12);
            border: 1px solid rgba(214,69,69,0.3);
            color: #f0a0a0;
        }

        .btn-menu {
            display: none;
            background: none;
            border: 1px solid var(--graphite-line);
            border-radius: 8px;
            color: var(--texte-clair);
            padding: 0.4rem 0.55rem;
            cursor: pointer;
        }

        @media (max-width: 960px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.ouvert { transform: translateX(0); }
            .main { margin-left: 0; }
            .btn-menu { display: inline-flex; }
            .contenu { padding: 1.25rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .badge-live::before { animation: none; }
        }

        @yield('styles')
    </style>
    @stack('head')
</head>
<body>
    <div class="app">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-marque">
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining — Mining in Burkina Faso">
                </a>
                <p class="sous-titre">ITSM · Support interne</p>
            </div>

            <nav class="nav">
                <div class="nav-section">Principal</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Tableau de bord
                </a>
                <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    Tickets
                </a>
                <a href="{{ route('assets.index') }}" class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    Actifs
                </a>
                <a href="{{ route('knowledge.index') }}" class="nav-link {{ request()->routeIs('knowledge.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    Base de connaissances
                </a>
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    Rapports
                </a>

                @if(auth()->user()?->hasRole('admin'))
                    <div class="nav-section">Administration</div>
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Utilisateurs
                    </a>
                    <a href="{{ route('admin.settings.categories') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Paramètres
                    </a>
                @endif
            </nav>

            <div class="sidebar-pied">
                <div class="utilisateur">
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                    <div class="utilisateur-infos">
                        <div class="nom">{{ auth()->user()->name }}</div>
                        <div class="role">{{ auth()->user()->role?->nom ?? 'Utilisateur' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-deconnexion">Se déconnecter</button>
                </form>
            </div>
        </aside>

        <div class="main">
            <header class="entete">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <button type="button" class="btn-menu" id="btn-menu" aria-label="Menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </button>
                    <h1>@yield('titre', 'Tableau de bord')</h1>
                </div>
                <div class="entete-actions">
                    <span class="badge-live">Sites opérationnels</span>
                </div>
            </header>

            <main class="contenu">
                @if (session('success'))
                    <div class="alerte alerte-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alerte alerte-erreur">{{ session('error') }}</div>
                @endif

                @yield('contenu')
            </main>
        </div>
    </div>

    <script>
        document.getElementById('btn-menu')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('ouvert');
        });
    </script>
    @stack('scripts')
</body>
</html>
