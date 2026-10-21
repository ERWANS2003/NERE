@php
    $breadcrumbs = [
        ['label' => 'Support IT'],
    ];
    $priorityStyles = [
        'low' => 'text-graphite-600',
        'normal' => 'text-blue-700',
        'high' => 'text-amber-700',
        'urgent' => 'text-red-700 font-semibold',
    ];
@endphp

<x-intranet-layout title="Support IT" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        title="Support informatique"
        :description="$isAgent ? 'File des tickets du département IT' : 'Consultez et suivez vos demandes d’assistance'"
    >
        <x-slot:actions>
            <x-button :href="route('intranet.tickets.create')">Créer un ticket</x-button>
        </x-slot:actions>
    </x-intranet.page-header>

    <x-intranet.filters as-form label="Filtrer les tickets" data-live-search class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_220px_auto]">
        <input
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Référence ou sujet"
            class="rounded-lg border border-graphite-300 px-3 py-2 text-sm"
        >
        <select name="status" class="rounded-lg border border-graphite-300 px-3 py-2 text-sm">
            <option value="">Tous les statuts</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Filtrer</x-button>
    </x-intranet.filters>

    <x-intranet.table>
        @if($tickets->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-graphite-50 text-xs uppercase tracking-wide text-graphite-600">
                        <tr>
                            <th class="px-4 py-3">Référence / sujet</th>
                            @if($isAgent)<th class="px-4 py-3">Demandeur</th>@endif
                            <th class="px-4 py-3">Priorité</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Responsable</th>
                            <th class="px-4 py-3">Créé le</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite-100">
                        @foreach($tickets as $ticket)
                            <tr class="hover:bg-graphite-50">
                                <td class="px-4 py-4">
                                    <a href="{{ route('intranet.tickets.show', $ticket) }}" class="font-semibold text-nere-700 hover:underline">{{ $ticket->number }}</a>
                                    <p class="mt-1 max-w-md truncate text-graphite-800">{{ $ticket->subject }}</p>
                                </td>
                                @if($isAgent)<td class="px-4 py-4 text-graphite-700">{{ $ticket->requester?->name ?? 'Compte supprimé' }}</td>@endif
                                <td class="px-4 py-4 {{ $priorityStyles[$ticket->priority->value] }}">{{ $ticket->priority->label() }}</td>
                                <td class="px-4 py-4">
                                    <x-intranet.status-badge :status="$ticket->status" />
                                </td>
                                <td class="px-4 py-4 text-graphite-600">{{ $ticket->assignee?->name ?? 'Non attribué' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-graphite-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="font-semibold text-graphite-800">Aucun ticket trouvé</h2>
                <p class="mt-1 text-sm text-graphite-600">Les nouveaux tickets créés apparaîtront ici.</p>
            </div>
        @endif
    </x-intranet.table>

    @if($tickets->hasPages())
        <div class="mt-6">{{ $tickets->links() }}</div>
    @endif
</x-intranet-layout>
