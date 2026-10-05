<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titre', 'Intranet') · Néré Mining</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-nere-mining.png') }}">

    {{-- Thème avant premier rendu --}}
    <script>
        (function(){
            try {
                var t = localStorage.getItem('nere-theme')
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', t);
            } catch(e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen" style="background-color: var(--surface-page); color: var(--text-body);">

<div x-data="{ mobileSidebar: false }">

    {{-- ═══ TOPBAR ═══ --}}
    <header class="sticky top-0 z-30 border-b"
            style="background-color: var(--surface-card); border-color: var(--line-subtle);">
        <div class="mx-auto flex h-14 max-w-screen-xl items-center gap-4 px-4 sm:px-6">

            {{-- Logo / Marque --}}
            <a href="{{ route('intranet.home') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining" class="h-8 w-auto">
                <span class="hidden sm:block font-bold text-sm tracking-wide" style="color: var(--text-strong);">
                    Intranet
                </span>
            </a>

            <div class="flex-1"></div>

            {{-- Notifications --}}
            <a href="{{ route('intranet.home') }}" class="relative rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
               style="color: var(--text-muted);" title="Notifications">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
            </a>

            {{-- Avatar / menu utilisateur --}}
            @auth
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-100 transition text-sm font-medium"
                        style="color: var(--text-body);">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-500 text-white text-xs font-bold">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                    </span>
                    <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-1 w-44 rounded-xl border shadow-lg py-1 z-50"
                     style="background: var(--surface-raised); border-color: var(--line-subtle);">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition"
                       style="color: var(--text-body);">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        ITSM (portail)
                    </a>
                    <hr style="border-color: var(--line-subtle);" class="my-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </div>
    </header>

    {{-- ═══ BREADCRUMB ═══ --}}
    @hasSection('breadcrumb')
    <div class="border-b px-4 py-2 text-sm sm:px-6" style="border-color: var(--line-subtle); color: var(--text-muted);">
        <div class="mx-auto max-w-screen-xl flex items-center gap-1.5">
            <a href="{{ route('intranet.home') }}" class="hover:underline" style="color: var(--brand);">Intranet</a>
            @yield('breadcrumb')
        </div>
    </div>
    @endif

    {{-- ═══ FLASH ═══ --}}
    @if (session('success') || session('error'))
        <div class="mx-auto max-w-screen-xl px-4 pt-4 sm:px-6">
            @if(session('success'))
                <div class="flex items-center gap-2 rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <svg class="h-4 w-4 shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center gap-2 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('error') }}
                </div>
            @endif
        </div>
    @endif

    {{-- ═══ CONTENU ═══ --}}
    <main class="mx-auto max-w-screen-xl px-4 py-6 sm:px-6 sm:py-8">
        @yield('content')
    </main>

    <footer class="mt-8 border-t py-4 text-center text-xs" style="border-color: var(--line-subtle); color: var(--text-subtle);">
        Intranet Néré Mining — {{ now()->year }}
    </footer>
</div>

@stack('scripts')
</body>
</html>
