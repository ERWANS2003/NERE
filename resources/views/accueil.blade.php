<x-app-layout>
    <x-slot name="titre">Accueil</x-slot>

    <h1 class="text-xl font-semibold tracking-tight text-graphite-900">
        Bonjour {{ \Illuminate\Support\Str::before(Auth::user()->name, ' ') }}
    </h1>

    <p class="mt-1 text-sm text-graphite-500">
        Votre portail est en cours de configuration : les départements seront
        disponibles à la prochaine étape.
    </p>
</x-app-layout>