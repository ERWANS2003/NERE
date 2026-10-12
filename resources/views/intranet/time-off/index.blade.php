@php
    $breadcrumbs = [
        ['label' => 'Mes congés'],
    ];
    $statuts = [
        'pending' => ['label' => 'En attente', 'type' => 'warning'],
        'approved' => ['label' => 'Approuvé', 'type' => 'success'],
        'denied' => ['label' => 'Refusé', 'type' => 'danger'],
        'cancelled' => ['label' => 'Annulé', 'type' => 'info'],
    ];
@endphp

<x-intranet-layout title="Mes congés" :breadcrumbs="$breadcrumbs">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Mes congés</h1>
            <p class="text-graphite-600">Consultez vos soldes et suivez vos demandes</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($estApprobateur)
                <a href="{{ route('intranet.time-off.approvals') }}" class="bouton-secondaire relative">
                    Approbations
                    @if($enAttente > 0)
                        <span class="ml-1 inline-flex items-center justify-center rounded-full bg-nere-600 px-1.5 text-xs font-bold text-white">{{ $enAttente }}</span>
                    @endif
                </a>
            @endif
            <a href="{{ route('intranet.time-off.create') }}" class="bouton-primaire">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Demander un congé
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Cartes de solde --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse($balances as $balance)
            @php
                $total = (float) $balance->total_days;
                $used = (float) $balance->used_days;
                $pct = $total > 0 ? min(100, round($used / $total * 100)) : 0;
            @endphp
            <div class="bg-white rounded-lg border border-graphite-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-graphite-900">{{ $balance->policy?->name ?? 'Congé' }}</h2>
                    <span class="text-2xl font-bold text-nere-600">{{ rtrim(rtrim(number_format($balance->remaining_days, 1), '0'), '.') }}</span>
                </div>
                <div class="text-xs text-graphite-500 mb-2">
                    {{ rtrim(rtrim(number_format($used, 1), '0'), '.') }} utilisés sur {{ rtrim(rtrim(number_format($total, 1), '0'), '.') }} jours
                </div>
                <div class="h-2 w-full rounded-full bg-graphite-100 overflow-hidden">
                    <div class="h-full bg-nere-500 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                <div class="mt-2 text-right text-xs text-graphite-400">{{ $pct }} % consommé</div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <x-departments.empty-tab message="Aucun solde de congé défini pour cette année." />
            </div>
        @endforelse
    </div>

    {{-- Historique --}}
    <h2 class="text-xl font-bold text-graphite-900 mb-4">Historique des demandes</h2>
    <div class="bg-white rounded-lg border border-graphite-200 overflow-hidden">
        @if($requests->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-graphite-50 text-graphite-600">
                        <tr>
                            <th class="text-left font-semibold px-4 py-3">Type</th>
                            <th class="text-left font-semibold px-4 py-3">Du</th>
                            <th class="text-left font-semibold px-4 py-3">Au</th>
                            <th class="text-left font-semibold px-4 py-3">Jours</th>
                            <th class="text-left font-semibold px-4 py-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite-100">
                        @foreach($requests as $req)
                            @php $s = $statuts[$req->status] ?? ['label' => ucfirst($req->status), 'type' => 'info']; @endphp
                            <tr class="hover:bg-graphite-50 transition">
                                <td class="px-4 py-3 text-graphite-900">{{ $req->policy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-graphite-600">{{ $req->start_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-graphite-600">{{ $req->end_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-graphite-600">{{ rtrim(rtrim(number_format($req->total_days, 1), '0'), '.') }}{{ $req->is_half_day ? ' (½)' : '' }}</td>
                                <td class="px-4 py-3"><x-badge :type="$s['type']">{{ $s['label'] }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-10 text-center">
                <p class="text-graphite-600 mb-1">Aucune demande pour le moment</p>
                <a href="{{ route('intranet.time-off.create') }}" class="text-sm text-nere-600 hover:text-nere-700 font-medium">
                    Faire une première demande →
                </a>
            </div>
        @endif
    </div>

    @if($requests->hasPages())
        <div class="mt-6">{{ $requests->links() }}</div>
    @endif

</x-intranet-layout>
