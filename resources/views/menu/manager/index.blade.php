@extends('menu.layout')

@section('title', 'Gestion des semaines de menus')

@section('content')
    <section class="mb-8 flex flex-col gap-5 border-b border-graphite-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 tag-service">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                    Espace gestionnaire
            </span>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-graphite-950 sm:text-4xl">Gestion des menus</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-graphite-600 sm:text-base">Préparez les menus de la semaine, partagez le lien aux équipes et exportez les commandes pour la restauration.</p>
        </div>
        <a href="{{ route('menu.manager.create') }}" class="bouton-primaire min-h-11 shrink-0 rounded-md">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
            Préparer une semaine
        </a>
    </section>

    <div class="mb-4 flex items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold tracking-tight text-graphite-900">Semaines de menus</h2>
            <p class="mt-1 text-sm text-graphite-600">Suivez les inscriptions et les périodes publiées.</p>
        </div>
        <span class="inline-flex shrink-0 items-center gap-2 rounded-md bg-graphite-50 px-3 py-1.5 text-xs font-semibold text-graphite-600">{{ $campaigns->total() }} semaine(s)</span>
    </div>

    <div class="space-y-4">
        @forelse($campaigns as $campaign)
            <section class="overflow-hidden rounded-lg border border-graphite-200 bg-white">
                <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md @if($campaign->status === 'open') bg-green-50 text-green-700 @elseif($campaign->status === 'draft') bg-or-50 text-graphite-700 @else bg-graphite-100 text-graphite-500 @endif">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-graphite-900 sm:text-lg">{{ $campaign->title }}</h3>
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold @if($campaign->status === 'open') bg-green-50 text-green-800 @elseif($campaign->status === 'draft') bg-or-50 text-graphite-800 @else bg-graphite-100 text-graphite-600 @endif">
                                    <span class="h-1.5 w-1.5 rounded-full @if($campaign->status === 'open') bg-green-500 @elseif($campaign->status === 'draft') bg-or-600 @else bg-graphite-400 @endif"></span>
                                    {{ ['draft' => 'Brouillon', 'open' => 'Inscriptions ouvertes', 'closed' => 'Clôturée'][$campaign->status] ?? $campaign->status }}
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-graphite-600">
                                <span class="inline-flex items-center gap-1.5"><svg class="h-4 w-4 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>{{ $campaign->starts_on->format('d/m/Y') }} – {{ $campaign->ends_on->format('d/m/Y') }}</span>
                                <span class="inline-flex items-center gap-1.5"><svg class="h-4 w-4 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m16-10a4 4 0 010 8m-9-8a4 4 0 110-8 4 4 0 010 8z"/></svg>{{ $campaign->registrations_count }} inscription(s)</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2 lg:justify-end">
                        @if($campaign->status !== 'draft')
                            <a href="{{ route('menu.registration.show', $campaign->link_token) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-graphite-300 bg-white px-3.5 py-2 text-sm font-semibold text-graphite-700 transition hover:bg-graphite-50 hover:text-nere-700" target="_blank" rel="noopener">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 6H20v6.5M20 6l-9 9m7-1v5H4V5h5"/></svg>
                                Ouvrir le lien
                            </a>
                            <a href="{{ route('menu.manager.export', $campaign) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-graphite-800 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-graphite-900">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4m-13 7v2a1 1 0 001 1h16a1 1 0 001-1v-2"/></svg>
                                Export Excel
                            </a>
                        @endif
                        @if($campaign->status === 'draft')
                            <form method="POST" action="{{ route('menu.manager.publish', $campaign) }}" onsubmit="return confirm('Publier cette semaine ? Les employés désignés seront notifiés.')">
                                @csrf
                                <button class="inline-flex min-h-10 items-center justify-center rounded-md bg-nere-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-nere-700">Publier</button>
                            </form>
                        @else
                            <a href="{{ route('menu.manager.tracking', $campaign) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-graphite-300 bg-white px-3.5 py-2 text-sm font-semibold text-graphite-700 transition hover:bg-graphite-50 hover:text-nere-700">Suivi des choix</a>
                        @endif
                        @if($campaign->status === 'open')
                            <form method="POST" action="{{ route('menu.manager.close', $campaign) }}" onsubmit="return confirm('Clôturer les inscriptions ?')">
                                @csrf
                                <button class="inline-flex min-h-10 items-center justify-center rounded-md px-3 py-2 text-sm font-semibold text-graphite-600 transition hover:bg-red-50 hover:text-red-700">Clôturer</button>
                            </form>
                        @endif
                    </div>
                </div>
                @if($campaign->status !== 'draft')
                    <div class="border-t border-graphite-100 px-5 py-3 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-graphite-500">Lien à transmettre</p>
                        <a class="mt-1 block truncate text-sm font-medium text-nere-700 hover:text-nere-800 hover:underline" href="{{ route('menu.registration.show', $campaign->link_token) }}">{{ route('menu.registration.show', $campaign->link_token) }}</a>
                    </div>
                @endif
            </section>
        @empty
            <div class="rounded-lg border border-graphite-200 bg-white px-6 py-10 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-md bg-graphite-50 text-graphite-500"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg></span>
                <h3 class="mt-4 font-bold text-graphite-900">Aucune semaine préparée</h3>
                <p class="mt-1 text-sm text-graphite-600">Créez une semaine pour publier les choix de menus aux employés.</p>
                <a href="{{ route('menu.manager.create') }}" class="bouton-primaire mt-5 min-h-11 rounded-md">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                    Créer la première semaine
                </a>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $campaigns->links() }}</div>
@endsection
