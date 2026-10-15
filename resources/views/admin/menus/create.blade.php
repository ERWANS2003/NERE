@extends('admin.layout')

@section('title', 'Créer un menu')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('admin.menus.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux menus
        </a>
    </div>

    @if($locations->isEmpty())
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            Aucune localisation de restauration active n’est configurée. Configurez-en une avant de créer un menu.
        </div>
    @else
        <form method="POST" action="{{ route('admin.menus.store') }}" class="space-y-6">
            @csrf
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 font-semibold text-gray-900">Période et destinataires</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label for="meal_location_id" class="mb-1 block text-sm font-medium text-gray-700">Localisation</label>
                        <select id="meal_location_id" name="meal_location_id" required class="w-full rounded border-gray-300">
                            <option value="">Choisir une localisation</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('meal_location_id') === (string) $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('meal_location_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="week_start" class="mb-1 block text-sm font-medium text-gray-700">Début de semaine (lundi)</label>
                        <input id="week_start" type="date" name="week_start" value="{{ old('week_start', $weekStart) }}" required class="w-full rounded border-gray-300">
                        @error('week_start')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="response_deadline" class="mb-1 block text-sm font-medium text-gray-700">Date limite de réponse</label>
                        <input id="response_deadline" type="datetime-local" name="response_deadline" value="{{ old('response_deadline', $deadline) }}" required class="w-full rounded border-gray-300">
                        @error('response_deadline')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            @foreach($jours as $index => $jour)
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold text-gray-900">{{ $jour }}</h3>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label for="day-{{ $index }}-menu-1" class="mb-1 block text-sm font-medium text-gray-700">Menu 1</label>
                            <textarea id="day-{{ $index }}-menu-1" name="days[{{ $index }}][menu_1]" required maxlength="500" rows="3" class="w-full rounded border-gray-300">{{ old("days.$index.menu_1") }}</textarea>
                            @error("days.$index.menu_1")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="day-{{ $index }}-menu-2" class="mb-1 block text-sm font-medium text-gray-700">Menu 2</label>
                            <textarea id="day-{{ $index }}-menu-2" name="days[{{ $index }}][menu_2]" required maxlength="500" rows="3" class="w-full rounded border-gray-300">{{ old("days.$index.menu_2") }}</textarea>
                            @error("days.$index.menu_2")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="day-{{ $index }}-dessert" class="mb-1 block text-sm font-medium text-gray-700">Dessert</label>
                            <input id="day-{{ $index }}-dessert" type="text" name="days[{{ $index }}][dessert]" value="{{ old("days.$index.dessert") }}" required maxlength="255" class="w-full rounded border-gray-300">
                            @error("days.$index.dessert")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>
            @endforeach

            @error('days')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.menus.index') }}" class="rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">Annuler</a>
                <button type="submit" class="rounded bg-nere-600 px-4 py-2 text-white hover:bg-nere-700">Enregistrer le brouillon</button>
            </div>
        </form>
    @endif
</div>
@endsection
