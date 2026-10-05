<x-app-layout>
    <x-slot name="titre">Mon compte</x-slot>

    <h1 class="text-xl font-semibold tracking-tight text-graphite-900">Mon compte</h1>

    <p class="mt-1 text-sm text-graphite-500">
        Mettez à jour vos coordonnées ou votre mot de passe.
    </p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @include('profil.partials.coordonnees')
        @include('profil.partials.mot-de-passe')
    </div>
</x-app-layout>