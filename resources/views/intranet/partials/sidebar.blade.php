<!-- Sidebar -->
{{-- Mobile : tiroir hors écran, ouvert par le bouton de l'en-tête (sidebarOpen).
     Grand écran (lg) : toujours visible, l'état sidebarOpen n'a aucun effet. --}}
<aside
    id="intranet-sidebar"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="w-64 shrink-0 bg-white border-r border-graphite-200 flex flex-col fixed lg:static inset-y-0 left-0 z-40 lg:z-0 transition-transform duration-200 lg:translate-x-0"
    aria-label="Navigation principale"
>
    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b border-graphite-200 px-4">
        <div class="flex w-full items-center justify-center rounded-lg bg-graphite-900 px-3 py-2">
            <img
                src="{{ asset('images/logo-nere-mining.png') }}"
                alt="NÉRÉ Mining"
                class="h-10 w-auto"
            >
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-4 px-3">
        <ul class="space-y-1">
            
            <!-- Accueil -->
            <li>
                <a 
                    href="{{ route('intranet.dashboard') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.dashboard') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Accueil</span>
                </a>
            </li>

            <!-- Départements -->
            <li>
                <a 
                    href="{{ route('intranet.departments.index') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.departments.*') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Départements</span>
                </a>
            </li>

            <!-- Annuaire -->
            <li>
                <a 
                    href="{{ route('intranet.directory') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.directory') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Annuaire</span>
                </a>
            </li>

            <!-- Calendrier -->
            <li>
                <a 
                    href="{{ route('intranet.calendar') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.calendar') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Calendrier</span>
                </a>
            </li>

            <!-- Annonces -->
            <li>
                <a 
                    href="{{ route('intranet.announcements.index') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.announcements*') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                    <span>Annonces</span>
                </a>
            </li>

            <!-- Mes Demandes -->
            <li>
                <a 
                    href="{{ route('intranet.submissions.index') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.submissions.*') || request()->routeIs('intranet.forms.*') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Mes Demandes</span>
                </a>
            </li>

        </ul>

        <!-- Divider -->
        <div class="my-4 border-t border-graphite-200"></div>

        <!-- Secondary Links -->
        <ul class="space-y-1">
            
            <!-- Ressources -->
            <li>
                <a 
                    href="{{ route('intranet.resources') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.resources*') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Ressources</span>
                </a>
            </li>

            <!-- Analytics -->
            <li>
                <a 
                    href="{{ route('intranet.analytics') }}" 
                    class="sidebar-link {{ request()->routeIs('intranet.analytics') ? 'active' : '' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Analytics</span>
                </a>
            </li>

        </ul>
    </nav>

    <!-- Footer -->
    <div class="border-t border-graphite-200 p-4">
        <div class="flex items-center justify-between text-xs text-graphite-600">
            <span>Karma · Burkina Faso</span>
            <a 
                href="{{ route('intranet.departments.show', 'it') }}" 
                class="text-nere-600 hover:text-nere-700 font-medium transition"
            >
                Support IT
            </a>
        </div>
    </div>

</aside>

<!-- Sidebar Overlay (Mobile) -->
<div 
    x-show="sidebarOpen"
    @click="sidebarOpen = false"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 bg-graphite-900/50 lg:hidden z-30"
    style="display: none;"
></div>

{{-- Les styles .sidebar-link sont dans resources/css/app.css : @apply n'est compilé
     par Tailwind que dans les fichiers CSS, pas dans un <style> de template. --}}
