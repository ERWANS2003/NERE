<!-- Header -->
<header class="h-16 bg-white border-b border-graphite-200 flex items-center justify-between px-6">
    
    <!-- Left: Mobile Menu Toggle + Search -->
    <div class="flex items-center gap-4 flex-1">
        
        <!-- Mobile Menu Toggle -->
        <button 
            @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden p-2 rounded-lg hover:bg-graphite-100 text-graphite-600 transition"
            aria-label="Toggle menu"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        @if (request()->routeIs('intranet.home', 'intranet.dashboard'))
            <p class="hidden shrink-0 text-sm font-semibold text-graphite-800 lg:block">
                Bienvenue dans l'intranet de NÉRÉ Mining
            </p>
        @endif

        <!-- Search Bar -->
        <button 
            @click="$dispatch('open-search')"
            class="flex items-center gap-3 px-4 py-2 bg-graphite-50 hover:bg-graphite-100 border border-graphite-200 rounded-lg transition max-w-md w-full"
        >
            <svg class="w-5 h-5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span class="text-graphite-500 text-sm flex-1 text-left">Rechercher...</span>
            <kbd class="hidden sm:inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-graphite-600 bg-white border border-graphite-200 rounded">
                <span class="text-xs">⌘</span>K
            </kbd>
        </button>

    </div>

    <!-- Right: Date + Notifications + User Profile -->
    <div class="flex items-center gap-6">

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

</header>
