<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#b91c1c">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-icon-192.png') }}">

    {{-- Compatible avec les deux styles : <x-intranet-layout title="…"> et @extends + @section('title'). --}}
    <title>{{ $title ?? (trim($__env->yieldContent('title')) ?: 'Intranet') }} - NÉRÉ Mining</title>

    {{-- Aucune police externe : l'intranet tourne sur le réseau local de la mine, sans Internet.
         La pile de polices système est définie dans resources/css/app.css (--font-sans). --}}

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/nere-design-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fullcalendar-custom.css') }}">

    @livewireStyles
    @stack('styles')
</head>
<body class="bg-graphite-50 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:font-semibold focus:text-nere-700 focus:shadow-lg">
        Aller au contenu principal
    </a>
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        @include('intranet.partials.sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Header -->
            @include('intranet.partials.header')

            <!-- Page Content -->
            <main id="main-content" tabindex="-1" class="flex-1 overflow-y-auto bg-graphite-50 p-4 sm:p-6">
                @if (! empty($breadcrumbs))
                    <nav class="mb-6" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-sm text-graphite-600">
                            <li>
                                <a href="{{ route('intranet.dashboard') }}" class="hover:text-nere-600 transition">
                                    Accueil
                                </a>
                            </li>
                            @foreach ($breadcrumbs as $breadcrumb)
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    @if (isset($breadcrumb['url']))
                                        <a href="{{ $breadcrumb['url'] }}" class="hover:text-nere-600 transition">
                                            {{ $breadcrumb['label'] }}
                                        </a>
                                    @else
                                        <span class="text-graphite-900 font-medium">{{ $breadcrumb['label'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>

        </div>

    </div>

    <!-- Search Modal (Cmd+K) -->
    @include('intranet.partials.search-modal')

    {{-- Messages flash (success / error / status / warning) relayés en toasts.
         Les contrôleurs redirigent avec ->with('success', …) ; sans ce pont,
         ces messages n'étaient jamais affichés à l'utilisateur. --}}
    @include('intranet.partials.flash-toasts')

    {{-- Livewire embarque et démarre Alpine (sidebar, menus, recherche Cmd+K). --}}
    @livewireScripts
    @stack('scripts')
</body>
</html>
