@php
    $breadcrumbs = [
        ['label' => 'Support IT'],
    ];
    $statusStyles = [
        'new' => 'bg-blue-100 text-blue-800',
        'in_progress' => 'bg-amber-100 text-amber-800',
        'waiting' => 'bg-purple-100 text-purple-800',
        'resolved' => 'bg-green-100 text-green-800',
        'closed' => 'bg-graphite-100 text-graphite-700',
    ];
    $priorityStyles = [
        'low' => 'text-graphite-600',
        'normal' => 'text-blue-700',
        'high' => 'text-amber-700',
        'urgent' => 'text-red-700 font-semibold',
    ];
@endphp

<x-intranet-layout title="Support IT" :breadcrumbs="$breadcrumbs">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900">Support informatique</h1>
            <p class="mt-1 text-graphite-600">
                {{ $isAgent ? 'File des tickets du département IT' : 'Consultez et suivez vos demandes d’assistance' }}
            </p>
        </div>
        <a href="{{ route('intranet.tickets.create') }}" class="bouton-primaire">Créer un ticket</a>
    </div>

    <form method="GET" class="mb-6 grid grid-cols-1 gap-3 rounded-xl border border-graphite-200 bg-white p-4 sm:grid-cols-[1fr_220px_auto]">
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
        <button type="submit" class="rounded-lg bg-graphite-800 px-4 py-2 text-sm font-medium text-white hover:bg-graphite-900">Filtrer</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-graphite-200 bg-white">
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
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusStyles[$ticket->status->value] }}">
                                        {{ $ticket->status->label() }}
                                    </span>
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
    </div>

    @if($tickets->hasPages())
        <div class="mt-6">{{ $tickets->links() }}</div>
    @endif
</x-intranet-layout>
