<x-guest-layout>
    <x-slot name="titre">Confirmation du mot de passe</x-slot>

    <h1 class="text-lg font-semibold tracking-tight text-graphite-900">
        Confirmation du mot de passe
    </h1>

    <p class="mt-1 text-sm text-graphite-500">
        Cette page sert à reconfirmer votre mot de passe avant une action sensible.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <x-etiquette for="password">Mot de passe</x-etiquette>
            <x-champ id="password"
                     name="password"
                     type="password"
                     required
                     autofocus
                     autocomplete="current-password" />
            <x-erreur :messages="$errors->get('password')" />
        </div>

        <x-bouton-primaire class="w-full">Confirmer</x-bouton-primaire>
    </form>
</x-guest-layout>