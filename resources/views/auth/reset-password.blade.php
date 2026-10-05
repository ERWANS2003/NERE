<x-guest-layout>
    <x-slot name="titre">Nouveau mot de passe</x-slot>

    <h1 class="text-lg font-semibold tracking-tight text-graphite-900">
        Définir un nouveau mot de passe
    </h1>

    <p class="mt-1 text-sm text-graphite-500">
        Pour votre sécurité, choisissez un mot de passe d'au moins 12 caractères
        que vous n'utilisez nulle part ailleurs.
    </p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-5 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <x-etiquette for="email">Adresse e-mail</x-etiquette>
            <x-champ id="email"
                     name="email"
                     type="email"
                     :value="old('email', $email)"
                     required
                     autofocus
                     autocomplete="email" />
            <x-erreur :messages="$errors->get('email')" />
        </div>

        <div>
            <x-etiquette for="password">Nouveau mot de passe</x-etiquette>
            <x-champ id="password"
                     name="password"
                     type="password"
                     required
                     autocomplete="new-password" />
            <x-erreur :messages="$errors->get('password')" />
        </div>

        <div>
            <x-etiquette for="password_confirmation">Confirmer le mot de passe</x-etiquette>
            <x-champ id="password_confirmation"
                     name="password_confirmation"
                     type="password"
                     required
                     autocomplete="new-password" />
            <x-erreur :messages="$errors->get('password_confirmation')" />
        </div>

        <x-bouton-primaire class="w-full">Enregistrer le mot de passe</x-bouton-primaire>
    </form>
</x-guest-layout>