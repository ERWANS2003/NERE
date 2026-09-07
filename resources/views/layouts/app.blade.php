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
            --ambre: #e0a52f;
            --ambre-clair: #ffc247;
            --rouge: #d9362e;
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
            animation: sidebar-enter .45s ease-out both;
            isolation: isolate;
            overflow: hidden;
        }

        .sidebar::before {
            content: "";
            position: absolute;
            inset: -35%;
            z-index: -1;
            pointer-events: none;
            opacity: .68;
            background:
                linear-gradient(120deg, transparent 30%, rgba(224,165,47,.18) 48%, transparent 66%),
                linear-gradient(35deg, transparent 25%, rgba(74,144,217,.14) 50%, transparent 75%);
            background-size: 170% 170%, 145% 145%;
            animation: sidebar-light-drift 10s ease-in-out infinite alternate;
        }

        @keyframes sidebar-light-drift {
            from { transform: translate3d(-8%, -4%, 0) rotate(-3deg); background-position: 0% 35%, 100% 65%; }
            to { transform: translate3d(8%, 4%, 0) rotate(3deg); background-position: 100% 65%, 0% 35%; }
        }

        @keyframes sidebar-enter {
            from { opacity: 0; transform: translateX(-18px); }
            to { opacity: 1; transform: translateX(0); }
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
            animation: nav-link-enter .35s ease-out both;
        }

        .nav-link:nth-of-type(1) { animation-delay: .08s; }
        .nav-link:nth-of-type(2) { animation-delay: .12s; }
        .nav-link:nth-of-type(3) { animation-delay: .16s; }
        .nav-link:nth-of-type(4) { animation-delay: .20s; }
        .nav-link:nth-of-type(5) { animation-delay: .24s; }
        .nav-link:nth-of-type(6) { animation-delay: .28s; }
        .nav-link:nth-of-type(7) { animation-delay: .32s; }

        @keyframes nav-link-enter {
            from { opacity: 0; transform: translateX(-8px); }
            to { opacity: 1; transform: translateX(0); }
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

        .notifications { position:relative; }
        .notifications summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:.35rem; color:var(--texte-att); }
        .notifications summary::-webkit-details-marker { display:none; }
        .notifications .compteur { min-width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; background:var(--rouge); color:#fff; font-size:.65rem; }
        .notifications-menu { position:absolute; right:0; top:calc(100% + .75rem); width:300px; padding:.65rem; background:var(--graphite-card); border:1px solid var(--graphite-line); border-radius:9px; box-shadow:0 12px 30px #0005; z-index:50; }
        .notification-item { display:block; padding:.65rem; border-bottom:1px solid var(--graphite-line); font-size:.77rem; }
        .notification-item:last-child { border-bottom:0; }
        .notification-item strong { display:block; color:var(--texte-clair); margin-bottom:.2rem; }
        .notification-item span { color:var(--texte-att); line-height:1.4; }

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
            .sidebar,
            .nav-link,
            .sidebar::before { animation: none; }
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
                @if(auth()->user()?->hasRole('dsi') || auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('directeur_departement'))
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                        Statistiques
                    </a>
                @endif

                @if(auth()->user()?->hasRole('directeur_departement') || auth()->user()?->hasRole('dsi'))
                    <div class="nav-section">Mon Département</div>
                    <a href="{{ route('department.index') }}" class="nav-link {{ request()->routeIs('department.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Mon Département
                    </a>
                @endif

                @if(auth()->user()?->hasRole('admin'))
                    <div class="nav-section">Administration</div>
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Utilisateurs
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Rôles & Permissions
                    </a>
                    @if(false)
                    <a href="{{ route('admin.settings.categories') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Paramètres
                    </a>
                    @endif
                    <div class="nav-section">Catalogue ESM</div>
                    <a href="{{ route('admin.settings.departments') }}" class="nav-link {{ request()->routeIs('admin.settings.departments*') ? 'active' : '' }}">Services</a>
                    <a href="{{ route('admin.settings.teams') }}" class="nav-link {{ request()->routeIs('admin.settings.teams*') ? 'active' : '' }}">Équipes</a>
                    <a href="{{ route('admin.settings.categories') }}" class="nav-link {{ request()->routeIs('admin.settings.categories*') ? 'active' : '' }}">Catégories</a>
                    <div class="nav-section">Référentiels</div>
                    <a href="{{ route('admin.settings.sites') }}" class="nav-link {{ request()->routeIs('admin.settings.sites*') ? 'active' : '' }}">Sites</a>
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
                    @php($notificationsNonLues = auth()->user()->unreadNotifications()->latest()->limit(5)->get())
                    <details class="notifications">
                        <summary aria-label="Notifications">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            @if($notificationsNonLues->isNotEmpty())<span class="compteur">{{ $notificationsNonLues->count() }}</span>@endif
                        </summary>
                        <div class="notifications-menu">
                            @forelse($notificationsNonLues as $notification)
                                <a class="notification-item" href="{{ route('tickets.show', $notification->data['ticket_id']) }}">
                                    <strong>{{ $notification->data['titre'] ?? 'Notification' }}</strong>
                                    <span>{{ $notification->data['message'] ?? '' }}</span>
                                </a>
                            @empty
                                <div class="notification-item"><span>Aucune nouvelle notification.</span></div>
                            @endforelse
                        </div>
                    </details>
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
