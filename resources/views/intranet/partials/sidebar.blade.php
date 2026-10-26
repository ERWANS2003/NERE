@php($currentUser = auth()->user())
@php($enabledModules = app(\App\Support\IntranetModuleRegistry::class)->all())

<aside
    id="intranet-sidebar"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 flex-col border-r border-graphite-200 bg-white transition-transform duration-200 lg:static lg:z-0 lg:translate-x-0"
    aria-label="Navigation principale"
>
    <div class="flex h-16 items-center justify-between gap-3 border-b border-graphite-200 px-4">
        <a href="{{ route('intranet.dashboard') }}" class="flex min-w-0 flex-1 items-center justify-center rounded-lg bg-graphite-900 px-3 py-2" aria-label="NÉRÉ Mining, accueil">
            <img src="{{ asset('images/logo-nere-mining.png') }}" alt="NÉRÉ Mining" class="h-10 w-auto">
        </a>
        <button
            type="button"
            @click="sidebarOpen = false"
            class="rounded-lg p-2 text-graphite-600 hover:bg-graphite-100 lg:hidden"
            aria-label="Fermer le menu"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Sections de l’intranet">
        <section id="intranet-personal-section" aria-labelledby="nav-personal-heading">
            <h2 id="nav-personal-heading" class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-graphite-500">Mon espace</h2>
            <ul class="space-y-1">
                <li>
                    <a id="intranet-home-link" href="{{ route('intranet.dashboard') }}"
                       @if(request()->routeIs('intranet.dashboard')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.dashboard') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/>
                        </svg>
                        <span>Accueil</span>
                    </a>
                </li>
                <li>
                    <a id="intranet-requests-link" href="{{ route('intranet.submissions.index') }}"
                       @if(request()->routeIs('intranet.submissions.index', 'intranet.submissions.show', 'intranet.submissions.store', 'intranet.submissions.update', 'intranet.submissions.destroy')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.submissions.index', 'intranet.submissions.show', 'intranet.submissions.store', 'intranet.submissions.update', 'intranet.submissions.destroy') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 5.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Mes demandes</span>
                    </a>
                </li>
            </ul>
        </section>

        <section id="intranet-services-section" class="mt-6" aria-labelledby="nav-services-heading">
            <h2 id="nav-services-heading" class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-graphite-500">Services</h2>
            <ul class="space-y-1">
                @if($enabledModules['forms']['enabled'])
                <li>
                    <a href="{{ route('intranet.forms.index') }}"
                       @if(request()->routeIs('intranet.forms.*')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.forms.*') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Faire une demande</span>
                    </a>
                </li>
                @endif
                @if($enabledModules['knowledge_base']['enabled'])
                <li>
                    <a href="{{ route('intranet.knowledge-base.index') }}"
                       @if(request()->routeIs('intranet.knowledge-base.*')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.knowledge-base.*') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5 5.754 5 4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c1.746 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>Base de connaissances</span>
                    </a>
                </li>
                @endif
                @if($enabledModules['tickets']['enabled'])
                <li>
                    <a href="{{ route('intranet.tickets.index') }}"
                       @if(request()->routeIs('intranet.tickets.*')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.tickets.*') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m-3.182-3.182a4.5 4.5 0 000-6.364M9 12a3 3 0 106 0 3 3 0 00-6 0zm12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Support informatique</span>
                    </a>
                </li>
                @endif
                @if($enabledModules['forms']['enabled'] && $currentUser->hasDepartmentProcessingAccess())
                    <li>
                        <a href="{{ route('intranet.submissions.queue') }}"
                           @if(request()->routeIs('intranet.submissions.queue')) aria-current="page" @endif
                           class="sidebar-link {{ request()->routeIs('intranet.submissions.queue') ? 'active' : '' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v4H4zM4 12h10v4H4zM18 12v8m-4-4h8"/>
                            </svg>
                            <span>Demandes à traiter</span>
                        </a>
                    </li>
                @endif
            </ul>
        </section>

        <section id="intranet-company-section" class="mt-6" aria-labelledby="nav-company-heading">
            <h2 id="nav-company-heading" class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-graphite-500">Vie de l’entreprise</h2>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('intranet.announcements.index') }}"
                       @if(request()->routeIs('intranet.announcements.*')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.announcements.*') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        <span>Annonces</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('intranet.calendar') }}"
                       @if(request()->routeIs('intranet.calendar', 'intranet.calendar.events', 'intranet.calendar.department-events.*')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.calendar', 'intranet.calendar.events', 'intranet.calendar.department-events.*') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Agenda</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('intranet.departments.index') }}"
                       @if(request()->routeIs('intranet.departments.*')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.departments.*') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                        </svg>
                        <span>Départements</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('intranet.directory') }}"
                       @if(request()->routeIs('intranet.directory')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.directory') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2a5 5 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Annuaire</span>
                    </a>
                </li>
                @if($enabledModules['resources']['enabled'])
                <li>
                    <a href="{{ route('intranet.resources') }}"
                       @if(request()->routeIs('intranet.resources*')) aria-current="page" @endif
                       class="sidebar-link {{ request()->routeIs('intranet.resources*') ? 'active' : '' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 5.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Ressources</span>
                    </a>
                </li>
                @endif
            </ul>
        </section>

        @if($currentUser->is_super_admin || $currentUser->isDepartmentHead())
            <section class="mt-6" aria-labelledby="nav-management-heading">
                <h2 id="nav-management-heading" class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-graphite-500">Pilotage</h2>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('intranet.analytics') }}"
                           @if(request()->routeIs('intranet.analytics')) aria-current="page" @endif
                           class="sidebar-link {{ request()->routeIs('intranet.analytics') ? 'active' : '' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14"/>
                            </svg>
                            <span>Analytics</span>
                        </a>
                    </li>
                    @if($currentUser->is_super_admin)
                        <li>
                            <a href="{{ route('admin.settings') }}"
                               @if(request()->routeIs('admin.*')) aria-current="page" @endif
                               class="sidebar-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8a4 4 0 100 8 4 4 0 000-8zm8 4a8 8 0 01-.1 1.3l1.4 1.1-1.4 2.5-1.7-.7a8 8 0 01-2.3 1.3l-.3 1.9h-2.9l-.3-1.9a8 8 0 01-2.3-1.3l-1.7.7L7 14.4l1.4-1.1a8 8 0 010-2.6L7 9.6l1.4-2.5 1.7.7a8 8 0 012.3-1.3l.3-1.9h2.9l.3 1.9a8 8 0 012.3 1.3l1.7-.7 1.4 2.5-1.4 1.1A8 8 0 0120 12z"/>
                                </svg>
                                <span>Administration</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </section>
        @endif
    </nav>
</aside>

<div
    x-show="sidebarOpen"
    @click="sidebarOpen = false"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-30 bg-graphite-900/50 lg:hidden"
    style="display: none;"
></div>
