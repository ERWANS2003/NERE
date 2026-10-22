<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1b2126">
    <title>@yield('title', 'Menu du personnel') · NÉRÉ Mining</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-graphite-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-graphite-200 bg-white">
        <div class="mx-auto flex max-w-screen-xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('menu.portal') }}" class="group flex min-w-0 items-center gap-3 rounded-md">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-nere-600 text-white transition group-hover:bg-nere-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v5m4-5v5M5 8h8v2a4 4 0 01-4 4v7m7-18v7a4 4 0 004 4v7m0-18v7"/>
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold tracking-wide text-graphite-900">MENU DU PERSONNEL</span>
                    <span class="hidden text-xs text-graphite-500 sm:block">NÉRÉ Mining · Restauration</span>
                </span>
            </a>
            <nav aria-label="Navigation du module Menu" class="flex shrink-0 items-center gap-2 sm:gap-4">
                <a href="{{ route('menu.portal') }}" @if(request()->routeIs('menu.portal')) aria-current="page" @endif class="rounded-md px-3 py-2 text-sm font-medium transition hover:bg-graphite-50 hover:text-nere-700 @if(request()->routeIs('menu.portal')) bg-nere-50 text-nere-700 @else text-graphite-600 @endif">
                    <span class="hidden sm:inline">Semaines ouvertes</span>
                    <span class="sm:hidden">Menus</span>
                </a>
                @if(auth()->user()->hasPermission('menu.manage'))
                    <a href="{{ route('menu.manager.campaigns') }}" @if(request()->routeIs('menu.manager.*')) aria-current="page" @endif class="rounded-md px-3 py-2 text-sm font-medium transition hover:bg-graphite-50 hover:text-nere-700 @if(request()->routeIs('menu.manager.*')) bg-nere-50 text-nere-700 @else text-graphite-600 @endif">
                        Gestion
                    </a>
                @endif
                <div class="hidden items-center gap-2 border-l border-graphite-200 pl-4 md:flex">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-graphite-100 text-xs font-bold text-graphite-700">{{ auth()->user()->initials }}</span>
                    <span class="max-w-40 truncate text-sm text-graphite-600">{{ auth()->user()->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="ml-1">
                    @csrf
                    <button class="rounded-md border border-graphite-300 px-3 py-2 text-sm font-medium text-graphite-700 transition hover:bg-graphite-50 hover:text-graphite-900">Quitter</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-screen-xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <div class="mx-auto max-w-5xl">
            @if(session('success'))
                <div role="status" class="mb-6 flex items-start gap-3 rounded-md border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-900">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div role="alert" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-900">
                    <p class="font-semibold">Vérifiez les informations saisies :</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
    <footer class="mt-10 border-t border-graphite-200 bg-white">
        <div class="mx-auto flex max-w-screen-xl flex-col gap-1 px-4 py-5 text-xs text-graphite-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <span>Restauration du personnel · NÉRÉ Mining</span>
            <a href="{{ route('intranet.dashboard') }}" class="font-medium text-graphite-600 transition hover:text-nere-700">Retour à l’intranet</a>
        </div>
    </footer>
</body>
</html>
