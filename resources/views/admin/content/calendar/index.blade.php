@extends('admin.layout')

@section('title', 'Gestion du calendrier')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Calendrier</h1>
            <p class="mt-1 text-sm text-gray-600">Les anniversaires, arrivées et congés approuvés sont calculés automatiquement. Gérez ici les événements enregistrés.</p>
        </div>
        <a href="{{ route('admin.calendar.create') }}" class="rounded bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Ajouter un événement</a>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($types as $type => $label)
            <span class="rounded bg-gray-100 px-3 py-1 text-xs text-gray-700">{{ $label }}</span>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Événement</th><th class="px-4 py-3">Département</th><th class="px-4 py-3">Créé par</th><th class="px-4 py-3">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($events as $event)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">{{ $event->event_date->format('d/m/Y') }}{{ $event->all_day || !$event->event_time ? ' · Toute la journée' : ' · '.substr((string) $event->event_time, 0, 5) }}</td>
                        <td class="px-4 py-3"><div class="font-semibold text-gray-900">{{ $event->title }}</div><div class="text-xs text-gray-500">{{ $types[$event->type] ?? $event->type }}</div></td>
                        <td class="px-4 py-3">{{ $event->department?->name ?? 'Toute l’entreprise' }}</td>
                        <td class="px-4 py-3">{{ $event->creator?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.calendar.edit', $event) }}" class="font-medium text-nere-700 hover:underline">Modifier</a>
                                <form method="POST" action="{{ route('admin.calendar.destroy', $event) }}" onsubmit="return confirm('Supprimer cet événement ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-medium text-red-700 hover:underline">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Aucun événement saisi manuellement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
@endsection
