@php
    $breadcrumbs = [
        ['label' => 'Demandes à traiter'],
    ];
@endphp

<x-intranet-layout title="Demandes à traiter" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        title="Demandes à traiter"
        description="Suivez les demandes de service envoyées aux départements que vous gérez."
    >
        <x-slot:actions>
            <x-button :href="route('intranet.submissions.index')" variant="secondary">
                <x-icone nom="list-checks" />
                Voir mes demandes
            </x-button>
        </x-slot:actions>
    </x-intranet.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('intranet.submissions.queue', ['status' => 'pending']) }}"
           class="rounded-xl border border-amber-200 bg-amber-50 p-5 transition hover:border-amber-300">
            <span class="text-sm font-medium text-amber-800">Demandes en attente</span>
            <span class="mt-1 block text-3xl font-bold text-amber-900">{{ $pendingCount }}</span>
        </a>
        <a href="{{ route('intranet.submissions.queue', ['status' => 'pending', 'assignment' => 'mine']) }}"
           class="rounded-xl border border-blue-200 bg-blue-50 p-5 transition hover:border-blue-300">
            <span class="text-sm font-medium text-blue-800">Prises en charge par moi</span>
            <span class="mt-1 block text-3xl font-bold text-blue-900">{{ $assignedToMeCount }}</span>
        </a>
    </div>

    <x-intranet.filters as-form action="{{ route('intranet.submissions.queue') }}" label="Filtrer les demandes à traiter" data-live-search
          class="mb-6 grid gap-4 sm:grid-cols-3">
        <div>
            <label for="status" class="mb-1 block text-sm font-medium text-graphite-700">Statut</label>
            <select id="status" name="status" class="w-full rounded-lg border-graphite-300 text-sm focus:border-nere-500 focus:ring-nere-500">
                <option value="">Tous les statuts</option>
                <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                <option value="approved" @selected(request('status') === 'approved')>Approuvées</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejetées</option>
            </select>
        </div>
        <div>
            <label for="assignment" class="mb-1 block text-sm font-medium text-graphite-700">Prise en charge</label>
            <select id="assignment" name="assignment" class="w-full rounded-lg border-graphite-300 text-sm focus:border-nere-500 focus:ring-nere-500">
                <option value="">Toutes les demandes</option>
                <option value="unassigned" @selected(request('assignment') === 'unassigned')>À prendre en charge</option>
                <option value="mine" @selected(request('assignment') === 'mine')>Prises en charge par moi</option>
            </select>
        </div>
        <div>
            <label for="department" class="mb-1 block text-sm font-medium text-graphite-700">Département</label>
            <select id="department" name="department" class="w-full rounded-lg border-graphite-300 text-sm focus:border-nere-500 focus:ring-nere-500">
                <option value="">Tous mes départements</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap gap-2 sm:col-span-3">
            <x-button type="submit">
                <x-icone nom="filter" />Filtrer
            </x-button>
            <x-button :href="route('intranet.submissions.queue')" variant="secondary">
                Effacer les filtres
            </x-button>
        </div>
    </x-intranet.filters>

    <x-intranet.table>
        @if($submissions->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-graphite-50 text-graphite-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Demande</th>
                            <th class="px-4 py-3 font-semibold">Demandeur</th>
                            <th class="px-4 py-3 font-semibold">Département</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="px-4 py-3 font-semibold">Prise en charge</th>
                            <th class="px-4 py-3 font-semibold">Date</th>
                            <th class="px-4 py-3 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite-100">
                        @foreach($submissions as $submission)
                            <tr class="hover:bg-graphite-50">
                                <td class="px-4 py-3">
                                    <span class="block font-semibold text-graphite-900">{{ $submission->form?->name ?? 'Formulaire supprimé' }}</span>
                                    <span class="text-xs text-graphite-500">#{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-4 py-3 text-graphite-700">{{ $submission->user?->name ?? 'Compte supprimé' }}</td>
                                <td class="px-4 py-3 text-graphite-700">{{ $submission->form?->department?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-intranet.status-badge :status="$submission->status" /></td>
                                <td class="px-4 py-3 text-graphite-700">
                                    {{ $submission->assignee?->name ?? 'À attribuer' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-graphite-600">{{ $submission->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('intranet.submissions.show', $submission) }}" class="inline-flex items-center gap-1 font-semibold text-nere-700 hover:text-nere-900">
                                        Ouvrir <x-icone nom="arrow-right" class="text-xs" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-graphite-50 text-graphite-400">
                    <x-icone nom="inbox" class="text-2xl" />
                </div>
                <h2 class="mt-4 text-lg font-semibold text-graphite-900">Aucune demande trouvée</h2>
                <p class="mt-1 text-sm text-graphite-600">
                    @if(request()->hasAny(['status', 'assignment', 'department']))
                        Essayez de modifier ou d’effacer les filtres.
                    @else
                        Les nouvelles demandes de vos départements apparaîtront ici.
                    @endif
                </p>
                @if(request()->hasAny(['status', 'assignment', 'department']))
                    <a href="{{ route('intranet.submissions.queue') }}" class="mt-4 inline-flex text-sm font-semibold text-nere-700 hover:text-nere-900">Effacer les filtres</a>
                @endif
            </div>
        @endif
    </x-intranet.table>

    @if($submissions->hasPages())
        <div class="mt-6">{{ $submissions->links() }}</div>
    @endif
</x-intranet-layout>
