@php
    $breadcrumbs = [
        ['label' => 'Calendrier', 'url' => route('intranet.calendar')],
        ['label' => 'Événements départementaux'],
    ];
@endphp

<x-intranet-layout title="Événements départementaux" :breadcrumbs="$breadcrumbs">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900">Événements départementaux</h1>
            <p class="mt-2 text-graphite-600">Gérez les événements des départements que vous dirigez.</p>
        </div>
        <a href="{{ route('intranet.calendar.department-events.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-nere-600 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-700">
            <i class="fas fa-plus" aria-hidden="true"></i>Ajouter un événement
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-graphite-200 bg-white">
        @if($events->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-graphite-50 text-graphite-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Événement</th>
                            <th class="px-4 py-3 font-semibold">Département</th>
                            <th class="px-4 py-3 font-semibold">Date</th>
                            <th class="px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite-100">
                        @foreach($events as $event)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="block font-semibold text-graphite-900">{{ $event->title }}</span>
                                    <span class="text-xs text-graphite-500">{{ $event->description }}</span>
                                </td>
                                <td class="px-4 py-3 text-graphite-700">{{ $event->department?->name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-graphite-700">
                                    {{ $event->event_date?->format('d/m/Y') }}
                                    @if(!$event->all_day && $event->event_time)
                                        · {{ substr((string) $event->event_time, 0, 5) }}
                                    @else
                                        · Toute la journée
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('intranet.calendar.department-events.edit', $event) }}"
                                           class="font-medium text-nere-700 hover:text-nere-900">Modifier</a>
                                        <form method="POST" action="{{ route('intranet.calendar.department-events.destroy', $event) }}"
                                              onsubmit="return confirm('Supprimer cet événement ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-700 hover:text-red-900">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-graphite-100 px-4 py-3">{{ $events->links() }}</div>
        @else
            <div class="px-6 py-14 text-center">
                <h2 class="text-lg font-semibold text-graphite-900">Aucun événement départemental</h2>
                <p class="mt-1 text-sm text-graphite-600">Ajoutez un événement pour le rendre visible aux membres du département.</p>
            </div>
        @endif
    </div>
</x-intranet-layout>
