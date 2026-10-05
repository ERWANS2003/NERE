<section class="carte p-6">
    <h2 class="text-base font-semibold text-graphite-900">Mes coordonnées</h2>

    @if (session('statut') === 'profil-modifie')
        <div class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            Vos coordonnées ont été mises à jour.
        </div>
    @endif

    <form method="POST" action="{{ route('profil.update') }}" class="mt-4 space-y-4">
        @csrf
        @method('PATCH')

        <div>
            <x-etiquette for="matricule">Matricule</x-etiquette>
            {{-- Le matricule est attribue par les RH : affiche en lecture seule,
                 il sert de rappel quand l'utilisateur contacte l'administration. --}}
            <input id="matricule"
                   value="{{ old('matricule', $user->matricule) }}"
                   disabled
                   class="champ cursor-not-allowed bg-graphite-100 font-mono text-graphite-500">
            <p class="mt-1.5 text-xs text-graphite-500">
                Attribué par les ressources humaines. Non modifiable depuis l'intranet.
            </p>
        </div>

        <div>
            <x-etiquette for="name">Nom et prénom</x-etiquette>
            <x-champ id="name" name="name" :value="old('name', $user->name)" required autocomplete="name" />
            <x-erreur :messages="$errors->get('name')" />
        </div>

        <div>
            <x-etiquette for="email">Adresse e-mail professionnelle</x-etiquette>
            <x-champ id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="email" />
            <x-erreur :messages="$errors->get('email')" />
            <p class="mt-1.5 text-xs text-graphite-500">
                Sert à la réinitialisation du mot de passe et aux notifications internes.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <x-bouton-primaire>Enregistrer</x-bouton-primaire>

            @if (session('statut') === 'profil-modifie')
                <p class="text-sm text-graphite-500">Enregistré.</p>
            @endif
        </div>
    </form>
</section>