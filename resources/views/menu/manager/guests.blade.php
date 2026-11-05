@extends('menu.layout')

@section('title', 'Personnes sans compte')

@section('content')
    <section class="mb-8 border-b border-graphite-200 pb-6">
        <a href="{{ route('menu.manager.campaigns') }}" class="text-sm font-medium text-graphite-600 hover:text-nere-700">&larr; Gestion des menus</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-graphite-950">Personnes sans compte</h1>
        <p class="mt-2 max-w-2xl text-sm text-graphite-600">Personnes absentes de l'Active Directory et de l'intranet qui mangent au restaurant. Vous saisissez leurs choix depuis le suivi de chaque semaine.</p>
    </section>

    <datalist id="menu-departments">
        @foreach($departments as $department)
            <option value="{{ $department->name }}">
        @endforeach
    </datalist>

    <form method="POST" action="{{ route('menu.manager.guests.store') }}" class="mb-8 grid gap-3 rounded-lg border border-graphite-200 bg-white p-5 sm:grid-cols-[2fr_2fr_1fr_auto] sm:items-end">
        @csrf
        <label class="block">
            <span class="etiquette">Nom et prénom <span class="text-nere-600">*</span></span>
            <input name="name" value="{{ old('name') }}" required maxlength="255" class="champ">
        </label>
        <label class="block">
            <span class="etiquette">Département <span class="text-nere-600">*</span></span>
            <input name="department" list="menu-departments" value="{{ old('department') }}" required maxlength="150" class="champ">
        </label>
        <label class="block">
            <span class="etiquette">Matricule</span>
            <input name="matricule" value="{{ old('matricule') }}" maxlength="50" class="champ">
        </label>
        <button class="bouton-primaire min-h-11">Ajouter</button>
    </form>

    <section class="rounded-lg border border-graphite-200 bg-white">
        <h2 class="border-b border-graphite-100 px-5 py-3 text-sm font-bold text-graphite-800">Liste ({{ $guests->count() }})</h2>
        <ul class="divide-y divide-graphite-100">
            @forelse($guests as $guest)
                <li class="px-5 py-3" x-data="{ edit: false }">
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm" x-show="! edit">
                        <div class="min-w-0">
                            <span class="font-medium {{ $guest->active ? 'text-graphite-900' : 'text-graphite-400 line-through' }}">{{ $guest->name }}</span>
                            <span class="ml-2 text-graphite-500">{{ $guest->department }}@if($guest->matricule) · {{ $guest->matricule }}@endif</span>
                            @unless($guest->active)<span class="ml-2 rounded bg-graphite-100 px-2 py-0.5 text-xs text-graphite-600">Inactif</span>@endunless
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="edit = true" class="font-medium text-nere-700 hover:underline">Modifier</button>
                            <form method="POST" action="{{ route('menu.manager.guests.destroy', $guest) }}" onsubmit="return confirm('Retirer cette personne de la liste ?')">
                                @csrf
                                @method('DELETE')
                                <button class="font-medium text-red-700 hover:underline">Retirer</button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('menu.manager.guests.update', $guest) }}" x-show="edit" x-cloak class="grid gap-3 sm:grid-cols-[2fr_2fr_1fr_auto_auto] sm:items-end">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ $guest->name }}" required maxlength="255" class="champ" aria-label="Nom et prénom">
                        <input name="department" list="menu-departments" value="{{ $guest->department }}" required maxlength="150" class="champ" aria-label="Département">
                        <input name="matricule" value="{{ $guest->matricule }}" maxlength="50" class="champ" aria-label="Matricule" placeholder="Matricule">
                        <label class="flex items-center gap-2 text-sm text-graphite-700">
                            <input type="checkbox" name="active" value="1" @checked($guest->active) class="rounded border-graphite-300">
                            Actif
                        </label>
                        <div class="flex gap-2">
                            <button class="bouton-primaire min-h-10">Enregistrer</button>
                            <button type="button" @click="edit = false" class="rounded-md border border-graphite-300 px-3 text-sm">Annuler</button>
                        </div>
                    </form>
                </li>
            @empty
                <li class="px-5 py-6 text-center text-sm text-graphite-500">Aucune personne enregistrée.</li>
            @endforelse
        </ul>
    </section>
@endsection
