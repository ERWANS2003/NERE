<section class="carte p-6">
    <h2 class="text-base font-semibold text-graphite-900">Mon mot de passe</h2>

    <p class="mt-1 text-sm text-graphite-500">
        Choisissez un mot de passe d'au moins 12 caractères, différent de vos
        autres identifiants.
    </p>

    @if (session('statut') === 'mot-de-passe-modifie')
        <div class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            Mot de passe mis a jour.
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="mt-4 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <x-etiquette for="current_password">Mot de passe actuel</x-etiquette>
            <x-champ id="current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-erreur :messages="$errors->get('current_password')" />
        </div>

        <div>
            <x-etiquette for="password">Nouveau mot de passe</x-etiquette>
            <x-champ id="password" name="password" type="password" autocomplete="new-password" />
            <x-erreur :messages="$errors->get('password')" />
        </div>

        <div>
            <x-etiquette for="password_confirmation">Confirmer le nouveau mot de passe</x-etiquette>
            <x-champ id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-erreur :messages="$errors->get('password_confirmation')" />
        </div>

        <x-bouton-primaire>Mettre a jour</x-bouton-primaire>
    </form>
</section>