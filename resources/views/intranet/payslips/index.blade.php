<x-intranet-layout title="Mes bulletins de paie">
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-2 text-3xl font-bold text-graphite-900">Mes bulletins de paie</h1>
        <p class="mb-6 text-graphite-600">Vos bulletins sont disponibles ici dès la clôture de la paie du mois. Vous seul et le service paie y avez accès.</p>

        <section class="overflow-hidden rounded-xl border border-graphite-200 bg-white">
            <ul class="divide-y divide-graphite-100">
                @forelse($payslips as $payslip)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <span class="font-medium text-graphite-900">{{ $payslip->period->label() }}</span>
                        <span class="flex items-center gap-4">
                            <span class="tabular-nums text-graphite-700">Net : {{ \App\Support\Fcfa::format($payslip->net) }} FCFA</span>
                            <a href="{{ route('intranet.payslips.download', $payslip) }}" data-no-skeleton class="font-semibold text-nere-700 hover:underline">Télécharger (PDF)</a>
                        </span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-graphite-500">Aucun bulletin disponible pour l’instant.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-intranet-layout>
