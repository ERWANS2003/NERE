<x-guest-layout>
    <x-slot name="titre">Mot de passe oublié</x-slot>

    <h1 class="text-lg font-semibold tracking-tight text-graphite-900">
        Réinitialiser mon mot de passe
    </h1>

    <p class="mt-1 text-sm text-graphite-500">
        Indiquez l'adresse e-mail de votre compte : vous recevrez un lien de
        réinitialisation valable quelques minutes.
    </p>

    <x-statut-session class="mt-4" :status="session('statut')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <x-etiquette for="email">Adresse e-mail professionnelle</x-etiquette>
            <x-champ id="email"
                     name="email"
                     type="email"
                     :value="old('email')"
                     required
                     autofocus
                     autocomplete="email" />
            <x-erreur :messages="$errors->get('email')" />
        </div>

        <x-bouton-primaire class="w-full">Envoyer le lien</x-bouton-primaire>

        <p class="text-center text-sm">
            <a href="{{ route('login') }}"
               class="font-medium text-graphite-600 underline underline-offset-2 hover:text-graphite-900">
                Retour à la connexion
            </a>
        </p>
    </form>
</x-guest-layout>