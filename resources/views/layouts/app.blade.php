<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- $titre est fourni via un slot par les vues filles ; absent sur
             l'accueil, le nom de l'application suffit alors. --}}
        <title>{{ trim(($titre ?? '').' — '.config('app.name'), ' —') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        {{-- Bouton d'evitementement du menu mobile, place avant l'en-tete pour
             que le focus clavier atteigne la navigation sans traverser le logo. --}}
        <a href="#contenu"
           class="sr-only z-50 rounded-md bg-graphite-900 px-4 py-2 text-sm font-semibold text-white focus:not-sr-only focus:absolute focus:left-4 focus:top-4">
            Aller au contenu principal
        </a>

        <div class="min-h-screen bg-graphite-50">
            @include('layouts.navigation')

            <main id="contenu" class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                {{ $slot }}
            </main>
        </div>

        @stack('modales')
        @livewireScripts
    </body>
</html>