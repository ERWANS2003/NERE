@php
    $breadcrumbs = [
        ['label' => 'Mes demandes'],
    ];
@endphp

<x-intranet-layout title="Mes demandes" :breadcrumbs="$breadcrumbs">

    {{-- En-tête --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Mes demandes</h1>
            <p class="text-graphite-600">Suivez l'état de vos demandes soumises aux départements</p>
        </div>
        <a href="{{ route('intranet.forms.index') }}" class="bouton-primaire">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvelle demande
        </a>
    </div>

    {{-- Filtres statut --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @php
            $filtres = [
                '' => 'Toutes',
                'pending' => 'En attente',
                'approved' => 'Approuvées',
                'rejected' => 'Rejetées',
            ];
        @endphp
        @foreach($filtres as $valeur => $label)
            <a href="{{ route('intranet.submissions.index', array_filter(['status' => $valeur])) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium transition
               {{ request('status', '') === $valeur ? 'bg-nere-600 text-white' : 'bg-white text-graphite-700 border border-graphite-200 hover:bg-graphite-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Liste --}}
    <div class="bg-white rounded-lg border border-graphite-200 overflow-hidden">
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
                                    @php
                                        $statutMap = [
                                            'pending' => ['label' => 'En attente', 'type' => 'warning'],
                                            'approved' => ['label' => 'Approuvée', 'type' => 'success'],
                                            'rejected' => ['label' => 'Rejetée', 'type' => 'danger'],
                                        ];
                                        $statut = $statutMap[$submission->status] ?? ['label' => ucfirst((string) $submission->status), 'type' => 'info'];
                                    @endphp
                                    <x-badge :type="$statut['type']">{{ $statut['label'] }}</x-badge>
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
    </div>

    @if($submissions->hasPages())
        <div class="mt-6">
            {{ $submissions->links() }}
        </div>
    @endif

</x-intranet-layout>
