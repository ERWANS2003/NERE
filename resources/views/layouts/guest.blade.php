<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titre', 'Connexion') · ITSM Néré Mining</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ asset('images/logo-nere-mining.png') }}">

    {{-- Applied before paint so a dark-mode reload never flashes white. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('nere-theme');
                var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-page text-body antialiased">

<div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">

    {{-- ---------- Brand panel ---------- --}}
    <aside class="relative hidden overflow-hidden bg-dark-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="nm-brand-gradient absolute inset-0 opacity-90" aria-hidden="true"></div>
        <div class="absolute inset-0 opacity-[0.14]"
             style="background-image:radial-gradient(circle at 22% 18%, var(--color-accent-300), transparent 45%),radial-gradient(circle at 80% 78%, var(--color-primary-400), transparent 42%);"
             aria-hidden="true"></div>

        <div class="relative">
            <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining"
                 class="w-56 max-w-full brightness-0 invert">
        </div>

        <div class="relative max-w-lg">
            <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white/90">
                <x-icon name="shield-check" class="h-3.5 w-3.5" />
                ITSM · Néré Mining
            </p>
            <h1 class="text-4xl font-bold leading-tight tracking-tight text-white">
                Un seul point d'entrée pour signaler, suivre et résoudre.
            </h1>
            <p class="mt-4 text-base leading-relaxed text-white/70">
                Production, Maintenance, Géologie, RH, Finance, IT — tous les incidents
                et les demandes du groupe centralisés au même endroit.
            </p>
        </div>

        <div class="relative flex flex-wrap gap-2">
            @foreach (['Tickets', 'SLA', 'Actifs', 'HSE', 'Connaissance'] as $pilier)
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-white/80">
                    {{ $pilier }}
                </span>
            @endforeach
        </div>
    </aside>

    {{-- ---------- Form panel ---------- --}}
    <main class="flex items-center justify-center px-5 py-10 sm:px-8">
        <div class="w-full max-w-[26rem]">

            <div class="mb-8 flex items-center justify-between lg:hidden">
                <img src="{{ asset('images/logo-nere-mining.png') }}" alt="Néré Mining" class="h-12 w-auto">
            </div>

            <div class="mb-8 flex justify-end">
                <x-theme-toggle />
            </div>

            @yield('contenu')

            <p class="mt-8 text-center text-xs text-muted">
                Besoin d'aide ?
                <a href="mailto:it-support@nere-mining.bf" class="font-semibold text-primary-600 hover:underline">
                    it-support@nere-mining.bf
                </a>
            </p>
        </div>
    </main>
</div>

@stack('scripts')
</body>
</html>
