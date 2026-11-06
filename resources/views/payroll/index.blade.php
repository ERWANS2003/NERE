<x-intranet-layout title="Paie">
    @include('payroll.partials.nav', ['title' => 'Paie', 'subtitle' => 'Une seule paie ouverte à la fois. Une paie clôturée ne se rouvre pas.'])

    @if(collect($missing)->contains(true))
        <div class="mb-6 rounded-lg border border-or-200 bg-or-50 px-4 py-3 text-sm text-graphite-800">
            <p class="font-semibold">Paramétrage incomplet :</p>
            <ul class="mt-1 list-disc pl-5">
                @if($missing['contribution'])<li>aucune cotisation sociale active (<a class="font-medium text-nere-700 underline" href="{{ route('payroll.rubrics.create') }}">créer une rubrique</a>) ;</li>@endif
                @if($missing['tax'])<li>aucun impôt sur salaire actif ;</li>@endif
                @if($missing['profiles'])<li>aucun salarié n’a de fiche de paie (<a class="font-medium text-nere-700 underline" href="{{ route('payroll.profiles.index') }}">renseigner les salariés</a>).</li>@endif
            </ul>
        </div>
    @endif

    <section class="mb-6 rounded-xl border border-graphite-200 bg-white p-5">
        @if($openPeriod)
            <p class="text-sm text-graphite-700">Paie en cours : <a href="{{ route('payroll.periods.show', $openPeriod) }}" class="font-semibold text-nere-700 hover:underline">{{ $openPeriod->label() }}</a>. Clôturez-la avant d’ouvrir le mois suivant.</p>
        @else
            <form method="POST" action="{{ route('payroll.periods.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <label class="block">
                    <span class="etiquette">Ouvrir la paie du mois</span>
                    <input type="month" name="month" value="{{ old('month', now()->format('Y-m')) }}" required class="champ">
                </label>
                <button class="bouton-primaire">Ouvrir</button>
            </form>
        @endif
    </section>

    <section class="overflow-hidden rounded-xl border border-graphite-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-graphite-50 text-graphite-600">
                <tr><th class="px-4 py-2">Mois</th><th class="px-4 py-2">État</th><th class="px-4 py-2 text-right">Bulletins</th><th class="px-4 py-2 text-right">Net total (FCFA)</th></tr>
            </thead>
            <tbody class="divide-y divide-graphite-100">
                @forelse($periods as $period)
                    <tr>
                        <td class="px-4 py-2"><a href="{{ route('payroll.periods.show', $period) }}" class="font-semibold text-nere-700 hover:underline">{{ $period->label() }}</a></td>
                        <td class="px-4 py-2">
                            @if($period->isClosed())
                                <span class="rounded bg-graphite-100 px-2 py-0.5 text-xs font-semibold text-graphite-700">Clôturée le {{ $period->closed_at->format('d/m/Y') }}</span>
                            @else
                                <span class="rounded bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-800">En cours</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">{{ $period->payslips_count }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ \App\Support\Fcfa::format($period->payslips_sum_net) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-graphite-500">Aucune paie pour l’instant.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <div class="mt-4">{{ $periods->links() }}</div>
</x-intranet-layout>
