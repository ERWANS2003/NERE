@extends('menu.layout')

@section('title', 'Menus ouverts')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Menus de la semaine</h1>
        <p class="mt-1 text-sm text-gray-600">Choisissez une semaine ouverte pour renseigner vos menus.</p>
    </div>

    <div class="space-y-4">
        @forelse($campaigns as $campaign)
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold">{{ $campaign->title }}</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $campaign->starts_on->format('d/m/Y') }} – {{ $campaign->ends_on->format('d/m/Y') }}
                        </p>
                        <p class="mt-2 text-sm text-gray-700">
                            {{ $campaign->days->count() }} jour(s) au menu
                            @if($campaign->registration_closes_at)
                                · Inscriptions jusqu’au {{ $campaign->registration_closes_at->format('d/m/Y à H:i') }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('menu.registration.show', $campaign->link_token) }}" class="inline-block rounded bg-red-700 px-4 py-2 text-center font-medium text-white hover:bg-red-800">
                        Choisir mes menus
                    </a>
                </div>
            </section>
        @empty
            <div class="rounded-lg bg-white p-8 text-center text-gray-600 shadow-sm">
                Aucune semaine n’est actuellement ouverte aux inscriptions.
            </div>
        @endforelse
    </div>
    <div class="mt-5">{{ $campaigns->links() }}</div>
@endsection
