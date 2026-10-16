<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ trim(($titre ?? 'Connexion').' — '.config('app.name'), ' —') }}</title>

        {{-- Aucune police distante : l'intranet doit fonctionner hors Internet,
             sur le reseau de la mine. La pile systeme est declaree dans app.css. --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="login-body">
        <main class="login-container">
            <section class="login-brand" aria-label="Bienvenue">
                <div class="login-brand-logo-wrap">
                    <img
                        class="login-brand-logo"
                        src="{{ asset('images/logo-nere-mining.png') }}"
                        alt="NÉRÉ Mining"
                    />
                </div>
                <div class="login-brand-message">
                    <h1 aria-label="Bienvenue dans l'Intranet de NERE Mining">
                        <span data-typing-text aria-hidden="true">Bienvenue dans l'Intranet de NERE Mining</span>
                    </h1>
                </div>
            </section>

            <section class="login-panel" aria-labelledby="login-title">
                <div class="login-panel-emblem-wrap">
                    <img
                        class="login-panel-emblem"
                        src="{{ asset('images/logo-nere-embleme.png') }}"
                        alt="Emblème NÉRÉ"
                    />
                </div>
                <div class="login-header">
                    <h2 id="login-title">Connexion</h2>
                </div>

                {{ $slot }}
            </section>
        </main>
        <script>
            (() => {
                const title = document.querySelector('[data-typing-text]');

                if (!title) return;

                const text = title.textContent.trim();
                const letters = Array.from(text);

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    title.textContent = text;
                    return;
                }

                title.textContent = '';
                let index = 0;
                const timer = window.setInterval(() => {
                    title.textContent += letters[index++];

                    if (index >= letters.length) {
                        window.clearInterval(timer);
                    }
                }, 48);
            })();
        </script>
    </body>
</html>