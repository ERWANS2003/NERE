<x-intranet-layout title="{{ $user->name }} · Paie {{ $period->label() }}">
    @include('payroll.partials.nav', ['title' => $user->name, 'subtitle' => 'Paie de '.$period->label().' · salaire de base '.\App\Support\Fcfa::format($profile->base_salary).' FCFA'])
    @php($fmt = fn ($v) => \App\Support\Fcfa::format($v))

    <a href="{{ route('payroll.periods.show', $period) }}" class="mb-4 inline-block text-sm font-medium text-graphite-600 hover:text-nere-700">&larr; Retour à la paie du mois</a>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-6">
            <section class="rounded-xl border border-graphite-200 bg-white p-5">
                <h2 class="font-semibold text-graphite-900">Absences non payées</h2>
                <form method="POST" action="{{ route('payroll.periods.entry', [$period, $user]) }}" class="mt-3 flex items-end gap-3">
                    @csrf
                    @method('PUT')
                    <label class="block"><span class="etiquette">Nombre de jours</span><input type="number" name="unpaid_days" min="0" max="31" step="0.5" value="{{ $unpaidDays + 0 }}" class="champ w-32"></label>
                    <button class="bouton-secondaire">Enregistrer</button>
                </form>
            </section>

            <section class="rounded-xl border border-graphite-200 bg-white p-5">
                <h2 class="font-semibold text-graphite-900">Éléments variables du mois</h2>
                <ul class="mt-3 divide-y divide-graphite-100 text-sm">
                    @forelse($variables as $variable)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <span>{{ $variable->rubric->label }} @if($variable->note)<span class="text-graphite-500">({{ $variable->note }})</span>@endif</span>
                            <span class="flex items-center gap-3">
                                <span class="tabular-nums {{ $variable->rubric->kind === 'deduction' ? 'text-red-700' : '' }}">{{ $variable->rubric->kind === 'deduction' ? '-' : '+' }} {{ $fmt($variable->amount) }}</span>
                                <form method="POST" action="{{ route('payroll.periods.variables.destroy', [$period, $variable]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-graphite-500 hover:text-red-700">Retirer</button>
                                </form>
                            </span>
                        </li>
                    @empty
                        <li class="py-2 text-graphite-500">Aucun élément variable.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('payroll.periods.variables.store', [$period, $user]) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_8rem]">
                    @csrf
                    <select name="payroll_rubric_id" required class="champ" aria-label="Rubrique">
                        <option value="">Choisir une rubrique</option>
                        @foreach($rubrics as $rubric)
                            <option value="{{ $rubric->id }}">{{ $rubric->label }} ({{ $rubric->kind === 'gain' ? 'gain' : 'retenue' }})</option>
                        @endforeach
                    </select>
                    <input type="number" name="amount" min="1" step="1" required placeholder="Montant" class="champ" aria-label="Montant">
                    <input name="note" maxlength="100" placeholder="Précision (facultatif), ex. 12 h de nuit" class="champ sm:col-span-2" aria-label="Précision">
                    <button class="bouton-primaire sm:col-span-2">Ajouter</button>
                </form>
            </section>
        </div>

        <section class="rounded-xl border border-graphite-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-graphite-900">Aperçu du bulletin</h2>
                @if($payslip?->stale)<span class="text-xs font-medium text-or-700">à recalculer</span>@endif
            </div>
            <table class="mt-3 w-full text-sm">
                <thead class="text-xs text-graphite-500"><tr><th class="py-1 text-left">Rubrique</th><th class="py-1 text-right">Base</th><th class="py-1 text-right">Taux</th><th class="py-1 text-right">Montant</th></tr></thead>
                <tbody class="divide-y divide-graphite-100">
                    @foreach($preview['lines'] as $line)
                        <tr>
                            <td class="py-1.5">{{ $line['label'] }}</td>
                            <td class="py-1.5 text-right tabular-nums text-graphite-500">{{ $line['base'] !== null && $line['kind'] !== 'gain' ? $fmt($line['base']) : '' }}</td>
                            <td class="py-1.5 text-right text-graphite-500">{{ \App\Support\Fcfa::rate($line['rate']) }}</td>
                            <td class="py-1.5 text-right tabular-nums {{ in_array($line['kind'], ['contribution', 'tax', 'deduction']) ? 'text-red-700' : '' }}">{{ in_array($line['kind'], ['contribution', 'tax', 'deduction']) ? '-' : '' }}{{ $fmt($line['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-graphite-300 font-semibold">
                    <tr><td class="py-1.5">Brut</td><td colspan="3" class="py-1.5 text-right tabular-nums">{{ $fmt($preview['totals']['gross']) }}</td></tr>
                    <tr><td class="py-1.5 text-nere-800">Net à payer</td><td colspan="3" class="py-1.5 text-right text-lg tabular-nums text-nere-800">{{ $fmt($preview['totals']['net']) }}</td></tr>
                    <tr class="text-xs font-normal text-graphite-500"><td class="py-1">Charges patronales</td><td colspan="3" class="py-1 text-right tabular-nums">{{ $fmt($preview['totals']['employer_contributions']) }}</td></tr>
                </tfoot>
            </table>
        </section>
    </div>
</x-intranet-layout>
