<!-- Header -->
<header class="intranet-header shrink-0 border-b border-graphite-200 bg-white">
    
    <!-- Navigation, search, notifications and user profile -->
    <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:gap-5 sm:px-6">
        
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-4">
            <button
                @click="sidebarOpen = !sidebarOpen"
                :aria-expanded="sidebarOpen.toString()"
                aria-controls="intranet-sidebar"
                class="rounded-lg p-2 text-graphite-600 transition hover:bg-graphite-100 lg:hidden"
                aria-label="Ouvrir le menu"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <button
                @click="$dispatch('open-search')"
                id="intranet-search-trigger"
                class="flex w-full max-w-xl min-w-0 items-center gap-3 rounded-xl border border-graphite-200 bg-graphite-50 px-3 py-2.5 transition hover:border-nere-200 hover:bg-white sm:px-4"
                aria-label="Ouvrir la recherche"
                aria-keyshortcuts="Control+K Meta+K"
            >
                <svg class="h-5 w-5 shrink-0 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="flex-1 truncate text-left text-sm text-graphite-500">Rechercher dans l’intranet...</span>
                <kbd class="hidden items-center gap-1 rounded-md border border-graphite-200 bg-white px-2 py-1 text-xs font-semibold text-graphite-600 sm:inline-flex">
                    Ctrl / ⌘ K
                </kbd>
            </button>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-4 lg:gap-6">

        <button
            type="button"
            data-pwa-install
            hidden
            class="items-center gap-2 rounded-lg bg-nere-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-nere-800 focus:outline-none focus:ring-2 focus:ring-nere-500 focus:ring-offset-2"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4m-2 8h12"/>
            </svg>
            <span class="hidden sm:inline">Installer l’application</span>
            <span class="sm:hidden">Installer</span>
        </button>

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
        <div class="relative" x-data="{ open: false }" @open-intranet-profile.window="open = true" @close-intranet-profile.window="open = false">
            
            <!-- Trigger -->
            <button 
                @click="open = !open"
                @click.away="open = false"
                id="intranet-profile-trigger"
                :aria-expanded="open.toString()"
                aria-controls="intranet-profile-menu"
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
                id="intranet-profile-menu"
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

                <button
                    type="button"
                    @click="$dispatch('open-intranet-tutorial'); open = false"
                    class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm text-graphite-700 transition hover:bg-graphite-50"
                >
                    <svg class="h-5 w-5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 1.806-2 3.272-2 1.933 0 3.5 1.343 3.5 3 0 1.4-1.1 2.575-2.589 2.892-.986.209-1.683 1.055-1.683 2.108M12 18h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Revoir le tutoriel
                </button>

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

                @if(auth()->user()->hasDepartmentProcessingAccess())
                    <a href="{{ route('intranet.submissions.queue') }}"
                       class="flex items-center gap-3 px-4 py-2 text-sm text-graphite-700 hover:bg-graphite-50 transition">
                        <svg class="h-5 w-5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v4H4zM4 12h10v4H4zM18 12v8m-4-4h8"/>
                        </svg>
                        Demandes à traiter
                    </a>
                @endif

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

</header>
