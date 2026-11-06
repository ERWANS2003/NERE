<x-intranet-layout title="Rubriques de paie">
    @include('payroll.partials.nav', ['title' => 'Rubriques et paramètres', 'subtitle' => 'Taux, plafonds et barèmes sont saisis ici par le service paie, d’après les textes en vigueur.'])

    <div class="mb-6 grid gap-4 lg:grid-cols-[1fr_20rem]">
        <p class="rounded-lg border border-graphite-200 bg-graphite-50 px-4 py-3 text-sm text-graphite-700">
            Le salaire de base et les absences non payées sont gérés automatiquement. Créez ici les gains (primes, indemnités), les retenues (avances, prêts), les cotisations sociales (taux salarial, patronal, plafond) et l’impôt sur salaire (barème par tranches). Toute modification rend les bulletins du mois en cours « à recalculer ».
        </p>
        <form method="POST" action="{{ route('payroll.settings') }}" class="flex items-end gap-2 rounded-lg border border-graphite-200 bg-white p-4">
            @csrf
            @method('PUT')
            <label class="block flex-1">
                <span class="etiquette">Jours par mois (prorata des absences)</span>
                <input type="number" name="days_per_month" min="1" max="31" value="{{ $daysPerMonth }}" class="champ">
            </label>
            <button class="bouton-secondaire">Enregistrer</button>
        </form>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('payroll.rubrics.create') }}" class="bouton-primaire">Nouvelle rubrique</a>
    </div>

    @forelse(\App\Models\PayrollRubric::KINDS as $kind => $kindLabel)
        @continue(! $rubrics->has($kind))
        <section class="mb-6 overflow-hidden rounded-xl border border-graphite-200 bg-white">
            <h2 class="border-b border-graphite-100 bg-graphite-50 px-4 py-2 text-sm font-bold text-graphite-800">{{ $kindLabel }}</h2>
            <table class="w-full text-left text-sm">
                <tbody class="divide-y divide-graphite-100">
                    @foreach($rubrics[$kind] as $rubric)
                        <tr class="{{ $rubric->active ? '' : 'text-graphite-400' }}">
                            <td class="w-36 px-4 py-2 font-mono text-xs">{{ $rubric->code }}</td>
                            <td class="px-4 py-2 font-medium">{{ $rubric->label }} @unless($rubric->active)<span class="ml-1 text-xs">(inactive)</span>@endunless</td>
                            <td class="px-4 py-2 text-graphite-600">
                                @switch($kind)
                                    @case('gain') {{ collect([$rubric->is_contributory ? 'cotisable' : null, $rubric->is_taxable ? 'imposable' : null])->filter()->join(', ') ?: 'non cotisable, non imposable' }} @break
                                    @case('contribution') salarial {{ \App\Support\Fcfa::rate($rubric->employee_rate) }} · patronal {{ \App\Support\Fcfa::rate($rubric->employer_rate) }} · base {{ \App\Models\PayrollRubric::BASES[$rubric->base] ?? '' }}@if($rubric->ceiling) · plafond {{ \App\Support\Fcfa::format($rubric->ceiling) }}@endif @break
                                    @case('tax') {{ count($rubric->tax_rules['brackets'] ?? []) }} tranche(s)@if(! empty($rubric->tax_rules['abatement_rate'])) · abattement {{ \App\Support\Fcfa::rate($rubric->tax_rules['abatement_rate']) }}@endif @break
                                    @default
                                @endswitch
                            </td>
                            <td class="px-4 py-2 text-right"><a href="{{ route('payroll.rubrics.edit', $rubric) }}" class="font-medium text-nere-700 hover:underline">Modifier</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
    @endforelse
    @if($rubrics->isEmpty())
        <p class="rounded-xl border border-graphite-200 bg-white px-4 py-8 text-center text-sm text-graphite-500">Aucune rubrique. Commencez par les cotisations sociales et l’impôt sur salaire.</p>
    @endif
</x-intranet-layout>
