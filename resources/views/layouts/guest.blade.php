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
    <body class="font-sans antialiased">
        <div class="flex min-h-screen flex-col justify-center bg-graphite-900 px-4 py-10">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 flex flex-col items-center text-center">
                    <x-logo class="h-16 w-auto" />

                    <h1 class="mt-5 text-xl font-semibold tracking-tight text-white">
                        {{ config('app.name') }}
                    </h1>

                    <p class="mt-1 text-sm text-graphite-400">
                        Portail interne — Nere Mining
                    </p>
                </div>

                <div class="carte p-6 sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-graphite-500">
                    Acces reserve au personnel autorise.
                    <br>
                    En cas de problème d'accès, contactez l'administration de l'intranet.
                </p>
            </div>
        </div>
    </body>
</html>