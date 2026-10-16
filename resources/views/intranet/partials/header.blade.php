<!-- Header -->
<header class="intranet-header shrink-0 border-b border-graphite-200 bg-white">
    
    <!-- Navigation, search, notifications and user profile -->
    <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:gap-5 sm:px-6">
        
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-4">
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="rounded-lg p-2 text-graphite-600 transition hover:bg-graphite-100 lg:hidden"
                aria-label="Ouvrir le menu"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <button
                @click="$dispatch('open-search')"
                class="flex w-full max-w-xl min-w-0 items-center gap-3 rounded-xl border border-graphite-200 bg-graphite-50 px-3 py-2.5 transition hover:border-nere-200 hover:bg-white sm:px-4"
                aria-label="Ouvrir la recherche"
            >
                <svg class="h-5 w-5 shrink-0 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="flex-1 truncate text-left text-sm text-graphite-500">Rechercher dans l’intranet...</span>
                <kbd class="hidden items-center gap-1 rounded-md border border-graphite-200 bg-white px-2 py-1 text-xs font-semibold text-graphite-600 sm:inline-flex">
                    <span class="text-xs">⌘</span>K
                </kbd>
            </button>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-4 lg:gap-6">

        <!-- Notifications -->
        <x-notification-dropdown />

        <!-- Current Date -->
        <div class="hidden md:flex items-center gap-2 text-sm text-graphite-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="font-medium">
                {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
            </span>
        </div>

        <!-- User Profile Dropdown -->
        <div class="relative" x-data="{ open: false }">
            
            <!-- Trigger -->
            <button 
                @click="open = !open"
                @click.away="open = false"
                class="flex items-center gap-3 hover:bg-graphite-50 rounded-lg px-3 py-2 transition"
            >
                <!-- Avatar -->
                <x-avatar 
                    :user="auth()->user()" 
                    size="md" 
                />
                
                <!-- User Info -->
                <div class="hidden lg:block text-left">
                    <div class="text-sm font-semibold text-graphite-900">
                        {{ auth()->user()->name }}
                    </div>
                    <div class="text-xs text-graphite-500">
                        {{ auth()->user()->position_title ?? 'Employé' }}
                    </div>
                </div>

                <!-- Chevron -->
                <svg 
                    class="w-4 h-4 text-graphite-400 transition-transform"
                    :class="{ 'rotate-180': open }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div 
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-white border border-graphite-200 rounded-lg shadow-lg py-1 z-50"
                style="display: none;"
            >
                
                <!-- User Info (Mobile) -->
                <div class="lg:hidden px-4 py-3 border-b border-graphite-200">
                    <div class="text-sm font-semibold text-graphite-900">
                        {{ auth()->user()->name }}
                    </div>
                    <div class="text-xs text-graphite-500">
                        {{ auth()->user()->email }}
                    </div>
                </div>

                <!-- Profile Link -->
                <a 
                    href="{{ route('profile.edit') }}" 
                    class="flex items-center gap-3 px-4 py-2 text-sm text-graphite-700 hover:bg-graphite-50 transition"
                >
                    <svg class="w-5 h-5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mon profil
                </a>

                <!-- Mes Demandes -->
                <a 
                    href="{{ route('intranet.submissions.index') }}" 
                    class="flex items-center gap-3 px-4 py-2 text-sm text-graphite-700 hover:bg-graphite-50 transition"
                >
                    <svg class="w-5 h-5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Mes demandes
                </a>

                <div class="border-t border-graphite-200 my-1"></div>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button 
                        type="submit"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-nere-700 hover:bg-nere-50 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Se déconnecter
                    </button>
                </form>

            </div>

        </div>

        </div>
    </div>

    @if (request()->routeIs('intranet.home', 'intranet.dashboard'))
        @php
            $headerHour = (int) now()->format('H');
            $headerGreeting = $headerHour < 18 ? 'Bonjour' : 'Bonsoir';
            $headerFirstName = auth()->user()->first_name ?? explode(' ', auth()->user()->name)[0];
        @endphp
        <section class="relative isolate overflow-hidden bg-linear-to-br from-nere-900 via-nere-800 to-nere-600 text-white">
            <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-32 h-80 w-80 rounded-full border border-white/10"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-2 top-8 h-52 w-52 rounded-full border border-or-400/30"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 right-1/3 h-64 w-64 rounded-full bg-or-400/10 blur-3xl"></div>
            <div class="relative mx-auto max-w-screen-2xl px-5 py-8 sm:px-8 sm:py-10 lg:px-10">
                <div class="max-w-4xl">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-white/90">
                        <span class="h-2 w-2 rounded-full bg-or-400 shadow-[0_0_12px_rgba(255,210,90,0.9)]"></span>
                        Espace collaborateur
                    </div>
                    <h1 class="text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-5xl">
                        Bienvenue dans l’intranet
                        <span class="mt-1 block text-or-300">NÉRÉ Mining</span>
                    </h1>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-white/80 sm:text-base">
                        {{ $headerGreeting }}, {{ $headerFirstName }}. Retrouvez vos services, vos équipes et les informations essentielles de l’entreprise au même endroit.
                    </p>
                    <div class="mt-6 h-1 w-20 rounded-full bg-or-400"></div>
                </div>
            </div>
            <div aria-hidden="true" class="absolute bottom-0 left-0 h-px w-full bg-linear-to-r from-transparent via-or-400/80 to-transparent"></div>
        </section>
    @endif

</header>
