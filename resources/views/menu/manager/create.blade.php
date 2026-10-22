@extends('menu.layout')

@section('title', 'Créer une semaine de menus')

@section('content')
    <div class="mb-6">
        <a href="{{ route('menu.manager.campaigns') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-graphite-600 transition hover:text-nere-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m7 7l-7-7 7-7"/></svg>
            Retour aux semaines
        </a>
        <div class="mt-5 max-w-2xl">
            <span class="tag-service">Nouvelle campagne</span>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-graphite-950 sm:text-4xl">Préparer les menus</h1>
            <p class="mt-3 text-sm leading-6 text-graphite-600 sm:text-base">Sélectionnez la semaine de travail, puis renseignez les deux plats proposés pour chaque journée.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('menu.manager.store') }}" class="space-y-6">
        @csrf
        <section class="overflow-hidden rounded-lg border border-graphite-200 bg-white">
            <div class="border-b border-graphite-100 px-5 py-4 sm:px-6">
                <h2 class="font-bold text-graphite-900">Période et publication</h2>
                <p class="mt-1 text-sm text-graphite-600">Une campagne correspond toujours à une semaine complète, du lundi au vendredi.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">
                <label class="sm:col-span-2 lg:col-span-1">
                    <span class="etiquette">Semaine concernée <span class="text-nere-600">*</span></span>
                    <input name="week" type="week" value="{{ old('week', $week) }}" required class="champ">
                    <span class="mt-1.5 block text-xs leading-5 text-graphite-500">Les dates du lundi au vendredi seront créées automatiquement.</span>
                </label>
                <label>
                    <span class="etiquette">Clôture des inscriptions <span class="font-normal text-graphite-500">(facultatif)</span></span>
                    <input name="registration_closes_at" type="datetime-local" value="{{ old('registration_closes_at') }}" class="champ">
                    <span class="mt-1.5 block text-xs leading-5 text-graphite-500">Sans date, les inscriptions restent ouvertes jusqu’à clôture manuelle.</span>
                </label>
                <label>
                    <span class="etiquette">Publication</span>
                    <select name="status" required class="champ">
                        <option value="draft" @selected(old('status', 'open') === 'draft')>Enregistrer en brouillon</option>
                        <option value="open" @selected(old('status', 'open') === 'open')>Ouvrir les inscriptions</option>
                    </select>
                    <span class="mt-1.5 block text-xs leading-5 text-graphite-500">Un brouillon n’apparaît pas dans le portail des employés.</span>
                </label>
            </div>
        </section>

        @foreach(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'] as $index => $weekday)
            <section class="overflow-hidden rounded-lg border border-graphite-200 bg-white">
                <div class="flex items-center gap-3 border-b border-graphite-100 px-5 py-4 sm:px-6">
                    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-graphite-50 text-sm font-semibold text-graphite-600">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <h2 class="font-bold text-graphite-900">{{ $weekday }}</h2>
                        <p class="text-xs text-graphite-500">Plats du jour</p>
                    </div>
                    <span class="ml-auto text-xs font-medium text-graphite-500">Jour ouvré</span>
                </div>
                <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <label>
                        <span class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-graphite-800">
                            <span class="h-2.5 w-2.5 rounded-full bg-yellow-400 ring-2 ring-yellow-100"></span>
                            Menu 1
                        </span>
                        <textarea name="days[{{ $index }}][menu_one]" required maxlength="1000" rows="3" placeholder="Décrivez le premier plat..." class="champ resize-y">{{ old("days.$index.menu_one") }}</textarea>
                    </label>
                    <label>
                        <span class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-graphite-800">
                            <span class="h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-green-100"></span>
                            Menu 2
                        </span>
                        <textarea name="days[{{ $index }}][menu_two]" required maxlength="1000" rows="3" placeholder="Décrivez le second plat..." class="champ resize-y">{{ old("days.$index.menu_two") }}</textarea>
                    </label>
                    <label class="sm:col-span-2">
                        <span class="mb-1.5 block text-sm font-semibold text-graphite-800">Dessert fixe <span class="font-normal text-graphite-500">(inclus dans les choix employés)</span></span>
                        <input name="days[{{ $index }}][dessert]" required maxlength="255" value="{{ old("days.$index.dessert") }}" placeholder="Ex. Fruit de saison" class="champ">
                    </label>
                </div>
            </section>
        @endforeach

        <div class="sticky bottom-0 z-20 -mx-4 border-t border-graphite-200 bg-white px-4 py-4 sm:static sm:mx-0 sm:flex sm:items-center sm:justify-between sm:gap-3 sm:rounded-lg sm:border sm:px-5">
            <p class="hidden text-sm text-graphite-500 sm:block">Vérifiez les menus avant de publier la semaine.</p>
            <div class="flex w-full gap-3 sm:w-auto">
                <a href="{{ route('menu.manager.campaigns') }}" class="bouton-secondaire flex-1 sm:flex-none">Annuler</a>
                <button class="bouton-primaire flex-1 sm:flex-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer la semaine
                </button>
            </div>
        </div>
    </form>
@endsection
