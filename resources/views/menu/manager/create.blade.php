@extends('menu.layout')

@section('title', 'Créer une semaine de menus')

@section('content')
    <div class="mb-6">
        <a href="{{ route('menu.manager.campaigns') }}" class="text-sm text-red-700 hover:underline">← Retour aux semaines</a>
        <h1 class="mt-3 text-2xl font-semibold">Préparer les menus</h1>
        <p class="mt-1 text-sm text-gray-600">Choisissez une semaine complète du lundi au vendredi, puis saisissez les plats proposés pour chaque jour.</p>
    </div>

    <form method="POST" action="{{ route('menu.manager.store') }}" class="space-y-5">
        @csrf
        <section class="rounded-lg bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <label>
                    <span class="mb-1 block text-sm font-medium">Semaine concernée</span>
                    <input name="week" type="week" value="{{ old('week', $week) }}" required class="w-full rounded border-gray-300">
                    <span class="mt-1 block text-xs text-gray-500">La période comprend automatiquement le lundi et le vendredi de cette semaine.</span>
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Clôture des inscriptions</span>
                    <input name="registration_closes_at" type="datetime-local" value="{{ old('registration_closes_at') }}" class="w-full rounded border-gray-300">
                </label>
                <label>
                    <span class="mb-1 block text-sm font-medium">Publication</span>
                    <select name="status" required class="w-full rounded border-gray-300">
                        <option value="draft" @selected(old('status', 'open') === 'draft')>Brouillon (lien non accessible)</option>
                        <option value="open" @selected(old('status', 'open') === 'open')>Ouvrir les inscriptions</option>
                    </select>
                </label>
            </div>
        </section>

        @foreach(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'] as $index => $weekday)
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-semibold">{{ $weekday }}</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <label>
                        <span class="mb-1 block text-sm font-medium">Menu 1</span>
                        <textarea name="days[{{ $index }}][menu_one]" required maxlength="1000" rows="2" class="w-full rounded border-gray-300">{{ old("days.$index.menu_one") }}</textarea>
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">Menu 2</span>
                        <textarea name="days[{{ $index }}][menu_two]" required maxlength="1000" rows="2" class="w-full rounded border-gray-300">{{ old("days.$index.menu_two") }}</textarea>
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">Dessert du jour</span>
                        <input name="days[{{ $index }}][dessert]" required maxlength="255" value="{{ old("days.$index.dessert") }}" class="w-full rounded border-gray-300">
                    </label>
                </div>
            </section>
        @endforeach

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('menu.manager.campaigns') }}" class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-50">Annuler</a>
            <button class="rounded bg-red-700 px-5 py-2 font-medium text-white hover:bg-red-800">Enregistrer la semaine</button>
        </div>
    </form>
@endsection
