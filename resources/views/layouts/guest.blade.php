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
        <div class="login-container">
            <div class="login-header">
                <div class="login-logo">
                    <img src="{{ asset('images/logo-nere-embleme.png') }}" alt="Logo NERE Mining" />
                </div>
                <h1>LOGIN</h1>
            </div>

            {{ $slot }}

            <p class="login-footer" style="margin-top: 30px; color: #666; font-size: 11px;">
                Acces reserve au personnel autorise.
                <br>
                En cas de problème d'accès, contactez l'administration de l'intranet.
            </p>
        </div>
    </body>
</html>