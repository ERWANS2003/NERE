@extends('admin.layout')

@section('title', 'Suivi des tickets')

@section('content')
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Suivi global du support</h3>
        <p class="mt-1 text-sm text-gray-600">Consultez les tickets et ouvrez leur fiche pour utiliser les statuts, assignations, activités et SLA existants.</p>
        @unless($moduleEnabled)
            <p class="mt-2 text-sm font-medium text-amber-700">Le module est désactivé : les fiches ne sont pas accessibles aux utilisateurs.</p>
        @endunless
    </div>

    <section class="rounded-lg bg-white p-5 shadow-sm">
        <form method="GET" class="mb-5 flex flex-wrap gap-2">
            <input name="search" value="{{ request('search') }}" placeholder="N° de ticket ou sujet" class="rounded border-gray-300">
            <select name="status" class="rounded border-gray-300">
                <option value="">Tous les statuts</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="department" class="rounded border-gray-300">
                <option value="">Tous les départements</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <button class="rounded bg-gray-800 px-3 py-2 text-sm text-white">Filtrer</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-3">Ticket</th>
                        <th class="px-3 py-3">Demandeur</th>
                        <th class="px-3 py-3">Département</th>
                        <th class="px-3 py-3">Priorité</th>
                        <th class="px-3 py-3">Statut</th>
                        <th class="px-3 py-3">Assigné à</th>
                        <th class="px-3 py-3">Créé le</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="px-3 py-3">
                                @if($moduleEnabled)
                                    <a href="{{ route('intranet.tickets.show', $ticket) }}" class="font-medium text-nere-700 hover:underline">{{ $ticket->number }}</a>
                                @else
                                    <span class="font-medium text-gray-700">{{ $ticket->number }}</span>
                                @endif
                                <p class="text-gray-600">{{ $ticket->subject }}</p>
                            </td>
                            <td class="px-3 py-3">{{ $ticket->requester?->name ?? 'Compte supprimé' }}</td>
                            <td class="px-3 py-3">{{ $ticket->department?->name ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $ticket->priority->label() }}</td>
                            <td class="px-3 py-3">{{ $ticket->status->label() }}</td>
                            <td class="px-3 py-3">{{ $ticket->assignee?->name ?? 'Non assigné' }}</td>
                            <td class="px-3 py-3">{{ $ticket->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-500">Aucun ticket trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $tickets->links() }}</div>
    </section>
@endsection
