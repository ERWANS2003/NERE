@extends('menu.layout')

@section('title', 'Suivi des choix')

@section('content')
    <section class="mb-8 border-b border-graphite-200 pb-6">
        <a href="{{ route('menu.manager.campaigns') }}" class="text-sm font-medium text-graphite-600 hover:text-nere-700">&larr; Gestion des menus</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-graphite-950">Suivi des choix</h1>
        <p class="mt-2 text-sm text-graphite-600">
            {{ $campaign->title }}
            @if($campaign->registration_closes_at)
                · clôture le {{ $campaign->registration_closes_at->format('d/m/Y à H:i') }}
            @endif
        </p>
        <p class="mt-3 text-sm font-semibold text-graphite-800">
            {{ $chosen->count() }} / {{ $chosen->count() + $pending->count() }} employés désignés ont choisi leurs plats.
        </p>

        @if($campaign->status === 'open' && $pending->count() > 0)
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('menu.manager.reminder', $campaign) }}"
                      onsubmit="return confirm('Envoyer un rappel par e-mail aux {{ $pending->count() }} employé(s) qui n\'ont pas encore choisi ?')">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-or-400 bg-or-50 px-4 py-2.5 text-sm font-semibold text-graphite-800 transition hover:bg-or-100 focus:outline-none focus:ring-2 focus:ring-or-400/50">
                        <svg class="h-4 w-4 text-or-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Envoyer un rappel par e-mail ({{ $pending->count() }} personne{{ $pending->count() > 1 ? 's' : '' }})
                    </button>
                </form>
                @if($campaign->reminder_sent_at)
                    <span class="text-xs text-graphite-500">
                        Dernier rappel envoyé {{ $campaign->reminder_sent_at->locale('fr')->diffForHumans() }}
                    </span>
                @endif
            </div>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach([['Pas encore choisi', $pending, 'text-nere-700'], ['Choix enregistrés', $chosen, 'text-green-700']] as [$label, $people, $color])
            <section class="rounded-lg border border-graphite-200 bg-white">
                <h2 class="border-b border-graphite-100 px-5 py-3 text-sm font-bold {{ $color }}">{{ $label }} ({{ $people->count() }})</h2>
                <ul class="divide-y divide-graphite-100">
                    @forelse($people as $person)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm">
                            <span class="font-medium text-graphite-900">{{ $person->name }}</span>
                            <span class="truncate text-graphite-500">{{ $person->departments->pluck('name')->join(', ') ?: '-' }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-graphite-500">Personne.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>

    <section class="mt-6 rounded-lg border border-graphite-200 bg-white" id="personnes-sans-compte">
        <div class="flex items-center justify-between gap-3 border-b border-graphite-100 px-5 py-3">
            <h2 class="text-sm font-bold text-graphite-800">Personnes sans compte ({{ $guests->where('has_chosen', true)->count() }} / {{ $guests->count() }})</h2>
            <a href="{{ route('menu.manager.guests') }}" class="text-sm font-medium text-nere-700 hover:underline">Gérer la liste</a>
        </div>
        <ul class="divide-y divide-graphite-100">
            @forelse($guests as $guest)
                <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm">
                    <span class="min-w-0">
                        <span class="font-medium text-graphite-900">{{ $guest->name }}</span>
                        <span class="ml-2 text-graphite-500">{{ $guest->department }}</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-3">
                        <span class="text-xs font-semibold {{ $guest->has_chosen ? 'text-green-700' : 'text-nere-700' }}">{{ $guest->has_chosen ? 'Choix saisis' : 'À saisir' }}</span>
                        <a href="{{ route('menu.manager.guests.choices', [$campaign, $guest]) }}" class="rounded-md border border-graphite-300 px-3 py-1.5 font-medium text-graphite-700 hover:bg-graphite-50 hover:text-nere-700">{{ $guest->has_chosen ? 'Modifier' : 'Saisir les choix' }}</a>
                    </span>
                </li>
            @empty
                <li class="px-5 py-4 text-sm text-graphite-500">Aucune personne sans compte. <a href="{{ route('menu.manager.guests') }}" class="font-medium text-nere-700 hover:underline">En ajouter</a></li>
            @endforelse
        </ul>
    </section>

    <p class="mt-6 text-xs text-graphite-500">
        Seuls les employés cochés « Choisit ses plats au menu du personnel » dans l'administration sont listés.
    </p>
@endsection
