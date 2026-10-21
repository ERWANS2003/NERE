@php
    $breadcrumbs = [
        ['label' => 'Mes demandes'],
    ];
@endphp

<x-intranet-layout title="Mes demandes" :breadcrumbs="$breadcrumbs">

    <x-intranet.page-header
        title="Mes demandes"
        description="Suivez l'état de vos demandes soumises aux départements"
    >
        <x-slot:actions>
            <x-button :href="route('intranet.forms.index')">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvelle demande
            </x-button>
        </x-slot:actions>
    </x-intranet.page-header>

    {{-- Filtres statut --}}
    <x-intranet.filters label="Filtrer mes demandes par statut" class="mb-6 flex flex-wrap gap-2">
        @php
            $filtres = [
                '' => 'Toutes',
                'pending' => 'En attente',
                'approved' => 'Approuvées',
                'rejected' => 'Rejetées',
            ];
        @endphp
        @foreach($filtres as $valeur => $label)
            <x-intranet.filter-link
                :href="route('intranet.submissions.index', array_filter(['status' => $valeur]))"
                :active="request('status', '') === $valeur"
            >
                {{ $label }}
            </x-intranet.filter-link>
        @endforeach
    </x-intranet.filters>

    {{-- Liste --}}
    <x-intranet.table>
        @if($submissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-graphite-50 text-graphite-600">
                        <tr>
                            <th class="text-left font-semibold px-4 py-3">Réf</th>
                            <th class="text-left font-semibold px-4 py-3">Formulaire</th>
                            <th class="text-left font-semibold px-4 py-3 hidden md:table-cell">Département</th>
                            <th class="text-left font-semibold px-4 py-3">Statut</th>
                            <th class="text-left font-semibold px-4 py-3 hidden sm:table-cell">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite-100">
                        @foreach($submissions as $submission)
                            <tr class="hover:bg-graphite-50 transition">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-graphite-900">#{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-4 py-3 text-graphite-900">{{ $submission->form?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-graphite-600 hidden md:table-cell">{{ $submission->form?->department?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <x-intranet.status-badge :status="$submission->status" />
                                </td>
                                <td class="px-4 py-3 text-graphite-500 hidden sm:table-cell">{{ $submission->created_at?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('intranet.submissions.show', $submission->id) }}" class="text-nere-600 hover:text-nere-700 font-medium">
                                        Voir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-graphite-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-graphite-600 mb-2">Aucune demande</p>
                <a href="{{ route('intranet.forms.index') }}" class="text-sm text-nere-600 hover:text-nere-700 font-medium">
                    Faire une nouvelle demande →
                </a>
            </div>
        @endif
    </x-intranet.table>

    @if($submissions->hasPages())
        <div class="mt-6">
            {{ $submissions->links() }}
        </div>
    @endif

</x-intranet-layout>
