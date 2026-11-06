<x-intranet-layout title="{{ $rubric->exists ? 'Modifier une rubrique' : 'Nouvelle rubrique' }}">
    @include('payroll.partials.nav', ['title' => $rubric->exists ? 'Rubrique '.$rubric->code : 'Nouvelle rubrique'])

    @php
        $rules = $rubric->tax_rules ?? [];
        $brackets = collect($rules['brackets'] ?? [])->map(fn ($b) => ($b['from'] + 0).';'.($b['to'] === null ? '' : $b['to'] + 0).';'.($b['rate'] + 0))->join("\n");
        $family = collect($rules['family_reductions'] ?? [])->map(fn ($rate, $n) => $n.';'.($rate + 0))->join("\n");
    @endphp

    <form method="POST" action="{{ $rubric->exists ? route('payroll.rubrics.update', $rubric) : route('payroll.rubrics.store') }}"
          class="mx-auto max-w-3xl space-y-5 rounded-xl border border-graphite-200 bg-white p-6"
          x-data="{ kind: @js(old('kind', $rubric->kind)) }">
        @csrf
        @if($rubric->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-[10rem_1fr_7rem]">
            <label class="block"><span class="etiquette">Code</span><input name="code" required maxlength="30" value="{{ old('code', $rubric->code) }}" class="champ font-mono uppercase" placeholder="CNSS_SAL"></label>
            <label class="block"><span class="etiquette">Libellé sur le bulletin</span><input name="label" required maxlength="255" value="{{ old('label', $rubric->label) }}" class="champ"></label>
            <label class="block"><span class="etiquette">Ordre</span><input type="number" name="position" min="0" max="9999" value="{{ old('position', $rubric->position) }}" class="champ"></label>
        </div>

        <label class="block">
            <span class="etiquette">Type</span>
            <select name="kind" x-model="kind" class="champ">
                @foreach(\App\Models\PayrollRubric::KINDS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <div x-show="kind === 'gain'" class="flex flex-wrap gap-6 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_contributory" value="1" @checked(old('is_contributory', $rubric->is_contributory)) class="rounded border-graphite-300"> Soumis aux cotisations</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_taxable" value="1" @checked(old('is_taxable', $rubric->is_taxable)) class="rounded border-graphite-300"> Soumis à l’impôt</label>
        </div>

        <div x-show="kind === 'contribution'" x-cloak class="grid gap-4 sm:grid-cols-2">
            <label class="block"><span class="etiquette">Taux salarial (%)</span><input type="number" step="0.0001" min="0" max="100" name="employee_rate" value="{{ old('employee_rate', $rubric->employee_rate) }}" class="champ"></label>
            <label class="block"><span class="etiquette">Taux patronal (%)</span><input type="number" step="0.0001" min="0" max="100" name="employer_rate" value="{{ old('employer_rate', $rubric->employer_rate) }}" class="champ"></label>
            <label class="block"><span class="etiquette">Plafond mensuel de la base (FCFA, vide si aucun)</span><input type="number" step="1" min="0" name="ceiling" value="{{ old('ceiling', $rubric->ceiling) }}" class="champ"></label>
            <label class="block">
                <span class="etiquette">Base de calcul</span>
                <select name="base" class="champ" :disabled="kind !== 'contribution'">
                    @foreach(\App\Models\PayrollRubric::BASES as $value => $label)
                        <option value="{{ $value }}" @selected(old('base', $rubric->base ?? 'contributory') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div x-show="kind === 'tax'" x-cloak class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block">
                    <span class="etiquette">Base de calcul</span>
                    <select name="base" class="champ" :disabled="kind !== 'tax'">
                        @foreach(\App\Models\PayrollRubric::BASES as $value => $label)
                            <option value="{{ $value }}" @selected(old('base', $rubric->base ?? 'taxable') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block"><span class="etiquette">Abattement (%)</span><input type="number" step="0.01" min="0" max="100" name="abatement_rate" value="{{ old('abatement_rate', $rules['abatement_rate'] ?? '') }}" class="champ"></label>
                <label class="block"><span class="etiquette">Arrondir la base à (FCFA)</span><input type="number" min="0" name="round_base_to" value="{{ old('round_base_to', $rules['round_base_to'] ?? '') }}" class="champ" placeholder="ex. 100"></label>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="deduct_contributions" value="1" @checked(old('deduct_contributions', $rules['deduct_contributions'] ?? false)) class="rounded border-graphite-300"> Déduire les cotisations salariales de la base avant abattement</label>
            <label class="block">
                <span class="etiquette">Barème progressif : une tranche par ligne, « de;à;taux % » (laisser « à » vide pour la dernière)</span>
                <textarea name="brackets" rows="8" class="champ font-mono text-sm" placeholder="0;30000;0&#10;30000;50000;10&#10;50000;;25">{{ old('brackets', $brackets) }}</textarea>
            </label>
            <label class="block">
                <span class="etiquette">Réduction pour charges de famille : « nombre de charges;réduction % de l’impôt », une ligne par nombre</span>
                <textarea name="family_reductions" rows="5" class="champ font-mono text-sm" placeholder="1;5&#10;2;10">{{ old('family_reductions', $family) }}</textarea>
            </label>
            <p class="text-xs text-graphite-500">Les exemples affichés en gris ne sont pas des valeurs réglementaires : saisissez le barème officiel en vigueur.</p>
        </div>

        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $rubric->active)) class="rounded border-graphite-300"> Rubrique active</label>

        <div class="flex justify-end gap-3 border-t pt-4">
            <a href="{{ route('payroll.rubrics.index') }}" class="rounded-md border border-graphite-300 px-4 py-2 text-sm">Annuler</a>
            <button class="bouton-primaire">Enregistrer</button>
        </div>
    </form>
</x-intranet-layout>
