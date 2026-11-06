<x-intranet-layout title="Paie · {{ $period->label() }}">
    @include('payroll.partials.nav', ['title' => 'Paie de '.$period->label(), 'subtitle' => $period->isClosed() ? 'Clôturée le '.$period->closed_at->format('d/m/Y à H:i').' par '.($period->closer?->name ?? '?').'. Les bulletins sont figés.' : 'En cours : saisissez les absences et variables, calculez, contrôlez puis clôturez.'])

    @php($fmt = fn ($v) => \App\Support\Fcfa::format($v))
    @php($stale = $payslips->where('stale', true)->count())

    <div class="mb-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach(['gross' => 'Brut', 'employee_contributions' => 'Cotisations salariales', 'tax' => 'Impôt', 'deductions' => 'Retenues', 'net' => 'Net à payer', 'employer_contributions' => 'Charges patronales'] as $key => $label)
            <div class="rounded-lg border border-graphite-200 bg-white p-3">
                <p class="text-xs text-graphite-500">{{ $label }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums text-graphite-900">{{ $fmt($totals[$key]) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        @unless($period->isClosed())
            <form method="POST" action="{{ route('payroll.periods.calculate', $period) }}">
                @csrf
                <button class="bouton-primaire">{{ $payslips->isEmpty() ? 'Calculer les bulletins' : 'Recalculer tous les bulletins' }}</button>
            </form>
        @endunless
        @if($payslips->isNotEmpty())
            <a href="{{ route('payroll.periods.ledger', $period) }}" data-no-skeleton class="bouton-secondaire">Livre de paie (Excel)</a>
            <a href="{{ route('payroll.periods.batch', $period) }}" data-no-skeleton class="bouton-secondaire">Tous les bulletins (PDF)</a>
        @endif
        @if($stale > 0)
            <span class="rounded bg-or-50 px-3 py-1.5 text-sm font-medium text-graphite-800">{{ $stale }} bulletin(s) à recalculer</span>
        @endif
    </div>

    <section class="overflow-x-auto rounded-xl border border-graphite-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-graphite-50 text-graphite-600">
                <tr>
                    <th class="px-3 py-2">Salarié</th>
                    @unless($period->isClosed())<th class="px-3 py-2 text-right">Abs. non payées</th><th class="px-3 py-2 text-right">Variables</th>@endunless
                    <th class="px-3 py-2 text-right">Brut</th><th class="px-3 py-2 text-right">Cotis.</th><th class="px-3 py-2 text-right">Impôt</th><th class="px-3 py-2 text-right">Retenues</th><th class="px-3 py-2 text-right">Net</th><th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-graphite-100">
                @php($rows = $period->isClosed() ? $payslips->map(fn ($p) => ['user_id' => $p->user_id, 'name' => $p->employee['name'] ?? '?', 'matricule' => $p->employee['matricule'] ?? null]) : $profiles->map(fn ($pr) => ['user_id' => $pr->user_id, 'name' => $pr->user->name, 'matricule' => $pr->user->matricule]))
                @forelse($rows as $row)
                    @php($payslip = $payslips->get($row['user_id']))
                    <tr class="{{ $payslip?->stale ? 'bg-or-50/50' : '' }}">
                        <td class="px-3 py-2"><span class="font-medium text-graphite-900">{{ $row['name'] }}</span>@if($row['matricule'])<span class="ml-1 text-xs text-graphite-500">{{ $row['matricule'] }}</span>@endif</td>
                        @unless($period->isClosed())
                            <td class="px-3 py-2 text-right tabular-nums">{{ ($entries[$row['user_id']] ?? 0) + 0 ?: '' }}</td>
                            <td class="px-3 py-2 text-right">{{ $variableCounts[$row['user_id']] ?? '' }}</td>
                        @endunless
                        @if($payslip)
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmt($payslip->gross) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmt($payslip->employee_contributions) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmt($payslip->tax) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmt($payslip->deductions) }}</td>
                            <td class="px-3 py-2 text-right font-semibold tabular-nums">{{ $fmt($payslip->net) }}</td>
                        @else
                            <td colspan="5" class="px-3 py-2 text-center text-xs text-graphite-400">non calculé</td>
                        @endif
                        <td class="whitespace-nowrap px-3 py-2 text-right">
                            @unless($period->isClosed())
                                @if($row['user_id'])<a href="{{ route('payroll.periods.employee', [$period, $row['user_id']]) }}" class="font-medium text-nere-700 hover:underline">Saisir</a>@endif
                            @endunless
                            @if($payslip)<a href="{{ route('payroll.payslips.show', $payslip) }}" data-no-skeleton class="ml-3 font-medium text-graphite-600 hover:text-nere-700">PDF</a>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-graphite-500">Aucun salarié avec une fiche de paie active.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if(! $period->isClosed() && $payslips->isNotEmpty())
        <section class="mt-8 rounded-xl border border-red-200 bg-white p-5">
            <h2 class="font-semibold text-graphite-900">Clôturer la paie</h2>
            <p class="mt-1 text-sm text-graphite-600">La clôture fige définitivement les montants et publie les bulletins dans « Mes bulletins ». Elle ne peut pas être annulée : une erreur se corrige sur la paie suivante.</p>
            <form method="POST" action="{{ route('payroll.periods.close', $period) }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <label class="block"><span class="etiquette">Tapez « {{ $period->label() }} » pour confirmer</span><input name="confirmation" required class="champ" autocomplete="off"></label>
                <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800" @disabled($stale > 0)>Clôturer définitivement</button>
            </form>
            @if($stale > 0)<p class="mt-2 text-sm text-red-700">Recalculez d’abord les bulletins modifiés.</p>@endif
        </section>
    @endif
</x-intranet-layout>
