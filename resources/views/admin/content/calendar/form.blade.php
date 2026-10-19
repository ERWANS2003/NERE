@extends('admin.layout')

@section('title', $event->exists ? 'Modifier un événement' : 'Nouvel événement')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">{{ $event->exists ? 'Modifier un événement' : 'Ajouter un événement' }}</h1>
        <form method="POST" action="{{ $event->exists ? route('admin.calendar.update', $event) : route('admin.calendar.store') }}" class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @if($event->exists) @method('PUT') @endif
            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-gray-700">Titre</label>
                <input id="title" name="title" required maxlength="255" value="{{ old('title', $event->title) }}" class="w-full rounded border-gray-300">
                @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                <textarea id="description" name="description" rows="4" maxlength="10000" class="w-full rounded border-gray-300">{{ old('description', $event->description) }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="type" class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                    <select id="type" name="type" required class="w-full rounded border-gray-300">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $event->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="department_id" class="mb-1 block text-sm font-medium text-gray-700">Département concerné</label>
                    <select id="department_id" name="department_id" class="w-full rounded border-gray-300">
                        <option value="">Toute l’entreprise</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id', $event->department_id) === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="event_date" class="mb-1 block text-sm font-medium text-gray-700">Date</label>
                    <input id="event_date" type="date" name="event_date" required value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}" class="w-full rounded border-gray-300">
                    @error('event_date')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="event_time" class="mb-1 block text-sm font-medium text-gray-700">Heure (si événement à heure fixe)</label>
                    <input id="event_time" type="time" name="event_time" value="{{ old('event_time', $event->event_time ? substr((string) $event->event_time, 0, 5) : '') }}" class="w-full rounded border-gray-300">
                    @error('event_time')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="all_day" value="1" @checked((bool) old('all_day', $event->all_day)) class="rounded border-gray-300">
                Toute la journée
            </label>
            <div class="flex justify-end gap-3 border-t pt-4">
                <a href="{{ route('admin.calendar.index') }}" class="rounded border px-4 py-2 text-sm">Annuler</a>
                <button class="rounded bg-nere-700 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            </div>
        </form>
    </div>
@endsection
