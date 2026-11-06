<x-intranet-layout title="Fiche de paie · {{ $user->name }}">
    @include('payroll.partials.nav', ['title' => $user->name, 'subtitle' => collect([$user->matricule ? 'Matricule '.$user->matricule : null, $user->poste])->filter()->join(' · ')])

    <form method="POST" action="{{ route('payroll.profiles.update', $user) }}" class="mx-auto max-w-3xl space-y-6">
        @csrf
        @method('PUT')
        <section class="space-y-4 rounded-xl border border-graphite-200 bg-white p-6">
            <h2 class="font-semibold text-graphite-900">Contrat et situation</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block"><span class="etiquette">Salaire de base mensuel (FCFA)</span><input type="number" name="base_salary" min="0" step="1" required value="{{ old('base_salary', $profile->base_salary) }}" class="champ"></label>
                <label class="block"><span class="etiquette">Catégorie</span><input name="category" maxlength="50" value="{{ old('category', $profile->category) }}" class="champ"></label>
                <label class="block"><span class="etiquette">Type de contrat</span><input name="contract_type" maxlength="50" value="{{ old('contract_type', $profile->contract_type) }}" class="champ" placeholder="CDI, CDD…"></label>
                <label class="block"><span class="etiquette">N° de sécurité sociale</span><input name="social_security_number" maxlength="50" value="{{ old('social_security_number', $profile->social_security_number) }}" class="champ"></label>
                <label class="block"><span class="etiquette">Charges de famille (nombre)</span><input type="number" name="dependents" min="0" max="20" required value="{{ old('dependents', $profile->dependents) }}" class="champ"></label>
            </div>
        </section>

        <section class="space-y-4 rounded-xl border border-graphite-200 bg-white p-6">
            <h2 class="font-semibold text-graphite-900">Paiement</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block">
                    <span class="etiquette">Mode</span>
                    <select name="payment_method" class="champ">
                        @foreach(\App\Models\PayrollProfile::PAYMENT_METHODS as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', $profile->payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block"><span class="etiquette">Banque</span><input name="bank_name" maxlength="100" value="{{ old('bank_name', $profile->bank_name) }}" class="champ"></label>
                <label class="block"><span class="etiquette">Compte ou numéro</span><input name="bank_account" maxlength="100" value="{{ old('bank_account', $profile->bank_account) }}" class="champ"></label>
            </div>
        </section>

        <section class="rounded-xl border border-graphite-200 bg-white p-6">
            <h2 class="font-semibold text-graphite-900">Éléments fixes chaque mois</h2>
            <p class="mb-3 text-sm text-graphite-600">Primes, indemnités et retenues récurrentes. Laissez vide ce qui ne s’applique pas.</p>
            @forelse($rubrics as $rubric)
                <label class="flex items-center justify-between gap-3 border-t border-graphite-100 py-2 text-sm">
                    <span><span class="font-medium text-graphite-900">{{ $rubric->label }}</span> <span class="text-xs text-graphite-500">{{ $rubric->kind === 'gain' ? 'gain' : 'retenue' }}</span></span>
                    <input type="number" name="fixed[{{ $rubric->id }}]" min="0" step="1" value="{{ old('fixed.'.$rubric->id, $fixedAmounts[$rubric->id] ?? '') }}" class="champ w-40 text-right" aria-label="Montant {{ $rubric->label }}">
                </label>
            @empty
                <p class="text-sm text-graphite-500">Aucune rubrique de gain ou de retenue. <a href="{{ route('payroll.rubrics.create') }}" class="text-nere-700 underline">En créer une</a>.</p>
            @endforelse
        </section>

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $profile->active ?? true)) class="rounded border-graphite-300"> Inclure dans la paie</label>
            <div class="flex gap-3">
                <a href="{{ route('payroll.profiles.index') }}" class="rounded-md border border-graphite-300 px-4 py-2 text-sm">Annuler</a>
                <button class="bouton-primaire">Enregistrer</button>
            </div>
        </div>
    </form>
</x-intranet-layout>
