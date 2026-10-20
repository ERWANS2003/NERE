@php
    $editing = $event->exists;
    $breadcrumbs = [
        ['label' => 'Calendrier', 'url' => route('intranet.calendar')],
        ['label' => 'Événements départementaux', 'url' => route('intranet.calendar.department-events.index')],
        ['label' => $editing ? 'Modifier' : 'Nouvel événement'],
    ];
    $action = $editing
        ? route('intranet.calendar.department-events.update', $event)
        : route('intranet.calendar.department-events.store');
@endphp

<x-intranet-layout :title="$editing ? 'Modifier un événement' : 'Nouvel événement départemental'" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-2 text-3xl font-bold text-graphite-900">{{ $editing ? 'Modifier un événement' : 'Nouvel événement départemental' }}</h1>
        <p class="mb-6 text-sm text-graphite-600">Cet événement sera visible par les membres du département sélectionné.</p>

        <form method="POST" action="{{ $action }}" class="space-y-5 rounded-xl border border-graphite-200 bg-white p-6 sm:p-8">
            @csrf
            @if($editing)
                @method('PUT')
            @endif

            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-graphite-700">Titre</label>
                <input id="title" name="title" required maxlength="255" value="{{ old('title', $event->title) }}"
                       class="w-full rounded-lg border-graphite-300 focus:border-nere-500 focus:ring-nere-500">
                @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-graphite-700">Description</label>
                <textarea id="description" name="description" rows="4" maxlength="10000"
                          class="w-full rounded-lg border-graphite-300 focus:border-nere-500 focus:ring-nere-500">{{ old('description', $event->description) }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="department_id" class="mb-1 block text-sm font-medium text-graphite-700">Département</label>
                    <select id="department_id" name="department_id" required
                            class="w-full rounded-lg border-graphite-300 focus:border-nere-500 focus:ring-nere-500">
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}"
                                @selected((string) old('department_id', $event->department_id ?? ($departments->count() === 1 ? $departments->first()->id : '')) === (string) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="type" class="mb-1 block text-sm font-medium text-graphite-700">Type</label>
                    <select id="type" name="type" required
                            class="w-full rounded-lg border-graphite-300 focus:border-nere-500 focus:ring-nere-500">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $event->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="event_date" class="mb-1 block text-sm font-medium text-graphite-700">Date</label>
                    <input id="event_date" type="date" name="event_date" required
                           value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border-graphite-300 focus:border-nere-500 focus:ring-nere-500">
                    @error('event_date')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="event_time" class="mb-1 block text-sm font-medium text-graphite-700">Heure</label>
                    <input id="event_time" type="time" name="event_time"
                           value="{{ old('event_time', $event->event_time ? substr((string) $event->event_time, 0, 5) : '') }}"
                           class="w-full rounded-lg border-graphite-300 focus:border-nere-500 focus:ring-nere-500">
                    @error('event_time')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-graphite-700">
                <input type="checkbox" name="all_day" value="1" @checked((bool) old('all_day', $event->all_day))
                       class="rounded border-graphite-300 text-nere-600 focus:ring-nere-500">
                Toute la journée
            </label>

            <div class="flex flex-wrap justify-end gap-3 border-t border-graphite-100 pt-5">
                <a href="{{ route('intranet.calendar.department-events.index') }}"
                   class="rounded-lg border border-graphite-300 px-4 py-2 text-sm font-medium text-graphite-700 hover:bg-graphite-50">Annuler</a>
                <button type="submit" class="rounded-lg bg-nere-600 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-700">
                    {{ $editing ? 'Enregistrer les modifications' : 'Ajouter à l’agenda départemental' }}
                </button>
            </div>
        </form>
    </div>
</x-intranet-layout>
