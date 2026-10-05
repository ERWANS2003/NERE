<x-guest-layout>
    <x-slot name="titre">Connexion</x-slot>

    <h1 class="text-lg font-semibold tracking-tight text-graphite-900">
        Connexion à l'intranet
    </h1>

    <p class="mt-1 text-sm text-graphite-500">
        Saisissez votre matricule ou votre adresse e-mail professionnelle.
    </p>

    <x-statut-session class="mt-4" :status="session('statut')" />

    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <x-etiquette for="identifiant">Matricule ou e-mail</x-etiquette>
            <x-champ id="identifiant"
                     name="identifiant"
                     :value="old('identifiant')"
                     required
                     autofocus
                     autocomplete="username"
                     autocapitalize="off"
                     spellcheck="false" />
            <x-erreur :messages="$errors->get('identifiant')" />
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-2">
                <x-etiquette for="password">Mot de passe</x-etiquette>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-medium text-graphite-600 underline underline-offset-2 hover:text-graphite-900">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <x-champ id="password"
                     name="password"
                     type="password"
                     required
                     autocomplete="current-password" />
            <x-erreur :messages="$errors->get('password')" />
        </div>

        <label for="se_souvenir" class="flex items-center gap-2 text-sm text-graphite-700">
            <input id="se_souvenir"
                   type="checkbox"
                   name="remember"
                   value="1"
                   @checked(old('remember'))
                   class="h-4 w-4 rounded border-graphite-300 text-amber-600 focus:ring-amber-600">
            <span>Rester connecté sur ce poste</span>
        </label>

        <x-bouton-primaire class="w-full">Se connecter</x-bouton-primaire>
    </form>
</x-guest-layout>