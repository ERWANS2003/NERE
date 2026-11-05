@extends('menu.layout')

@section('title', 'Choix de '.$guest->name)

@section('content')
    <section class="mb-6 border-b border-graphite-200 pb-6">
        <a href="{{ route('menu.manager.tracking', $campaign) }}" class="text-sm font-medium text-graphite-600 hover:text-nere-700">&larr; Suivi des choix</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-graphite-950">{{ $guest->name }}</h1>
        <p class="mt-2 text-sm text-graphite-600">{{ $guest->department }} · {{ $campaign->title }}</p>
    </section>

    <form method="POST" action="{{ route('menu.manager.guests.choices.store', [$campaign, $guest]) }}" class="space-y-4">
        @csrf
        @foreach($campaign->days as $day)
            @php($previousChoice = $registration?->choices->firstWhere('menu_day_id', $day->id)?->selected_menu)
            <fieldset class="rounded-lg border border-graphite-200 bg-white p-4 sm:p-5">
                <legend class="px-1 text-sm font-bold text-graphite-900">{{ ucfirst($day->date->translatedFormat('l d F')) }}</legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                    @foreach(['1' => $day->menu_one, '2' => $day->menu_two, '' => 'Pas de repas'] as $value => $label)
                        <label class="flex cursor-pointer items-start gap-2 rounded-md border border-graphite-200 p-3 text-sm has-[:checked]:border-nere-500 has-[:checked]:bg-nere-50">
                            <input type="radio" name="choices[{{ $day->id }}]" value="{{ $value }}" class="mt-0.5" @checked((string) old("choices.{$day->id}", $previousChoice) === (string) $value)>
                            <span>
                                @if($value !== '')<span class="block text-xs font-bold uppercase text-graphite-500">Menu {{ $value }}</span>@endif
                                <span class="text-graphite-900">{{ $label }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        <div class="flex justify-end gap-3">
            <a href="{{ route('menu.manager.tracking', $campaign) }}" class="rounded-md border border-graphite-300 px-4 py-2 text-sm">Annuler</a>
            <button class="bouton-primaire">Enregistrer les choix</button>
        </div>
    </form>
@endsection
