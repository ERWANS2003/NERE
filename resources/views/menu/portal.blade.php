@extends('menu.layout')

@section('title', 'Menus ouverts')

@section('content')
    <section class="mb-8 border-b border-graphite-200 pb-6">
        <div class="max-w-2xl">
            <p class="tag-service">Restauration du personnel</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-graphite-950 sm:text-4xl">Menus de la semaine</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-graphite-600 sm:text-base">
                Consultez les menus proposés et indiquez vos choix du lundi au vendredi.
            </p>
        </div>
    </section>

    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-graphite-900">Inscriptions ouvertes</h2>
            <p class="mt-1 text-sm text-graphite-600">Sélectionnez une semaine pour renseigner vos repas.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-md bg-graphite-50 px-3 py-1.5 text-xs font-semibold text-graphite-600">
            <span class="h-2 w-2 rounded-full bg-green-500"></span>{{ $campaigns->total() }} semaine(s)
        </span>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse($campaigns as $campaign)
            <article class="group overflow-hidden rounded-lg border border-graphite-200 bg-white transition-colors hover:border-graphite-400">
                <div class="p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.12em] text-nere-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                                Semaine disponible
                            </span>
                            <h3 class="mt-2 text-lg font-bold leading-snug text-graphite-900 sm:text-xl">{{ $campaign->title }}</h3>
                        </div>
                        <span class="shrink-0 rounded-md bg-graphite-50 px-3 py-2 text-center text-xs font-semibold text-graphite-700">
                            {{ $campaign->days->count() }}<span class="mt-0.5 block font-medium">jours</span>
                        </span>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-graphite-100 pt-4 text-sm text-graphite-600">
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                            <time datetime="{{ $campaign->starts_on->toDateString() }}">{{ $campaign->starts_on->translatedFormat('d M') }}</time>
                            <span aria-hidden="true">—</span>
                            <time datetime="{{ $campaign->ends_on->toDateString() }}">{{ $campaign->ends_on->translatedFormat('d M Y') }}</time>
                        </span>
                        @if($campaign->registration_closes_at)
                            <span class="inline-flex items-center gap-2 text-xs">
                                <svg class="h-4 w-4 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Clôture le {{ $campaign->registration_closes_at->format('d/m à H:i') }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('menu.registration.show', $campaign->link_token) }}" class="bouton-primaire mt-5 min-h-11 w-full rounded-md sm:w-auto">
                        Choisir mes menus
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7"/></svg>
                    </a>
                </div>
            </article>
        @empty
            <div class="col-span-full overflow-hidden rounded-lg border border-graphite-200 bg-white px-6 py-10 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-md bg-graphite-50 text-graphite-500">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm3 10h8"/></svg>
                </span>
                <h3 class="mt-4 text-base font-bold text-graphite-900">Aucune inscription ouverte</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-graphite-600">Les nouvelles semaines de menus apparaîtront ici dès leur publication.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-5">{{ $campaigns->links() }}</div>
@endsection
