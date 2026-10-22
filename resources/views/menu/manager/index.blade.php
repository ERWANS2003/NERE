@extends('menu.layout')

@section('title', 'Gestion des semaines de menus')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Semaines de menus</h1>
            <p class="mt-1 text-sm text-gray-600">Créez une semaine, partagez son lien AD et exportez les choix pour la cuisine.</p>
        </div>
        <a href="{{ route('menu.manager.create') }}" class="rounded bg-red-700 px-4 py-2 font-medium text-white hover:bg-red-800">Créer une semaine</a>
    </div>

    <div class="space-y-4">
        @forelse($campaigns as $campaign)
            <section class="rounded-lg bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-semibold">{{ $campaign->title }}</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $campaign->starts_on->format('d/m/Y') }} – {{ $campaign->ends_on->format('d/m/Y') }}
                            · {{ $campaign->registrations_count }} inscription(s)
                            · <span class="font-medium">{{ ['draft' => 'Brouillon', 'open' => 'Ouverte', 'closed' => 'Clôturée'][$campaign->status] ?? $campaign->status }}</span>
                        </p>
                    </div>
                    @if($campaign->status === 'open')
                        <form method="POST" action="{{ route('menu.manager.close', $campaign) }}" onsubmit="return confirm('Clôturer les inscriptions ?')">
                            @csrf
                            <button class="rounded border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">Clôturer</button>
                        </form>
                    @endif
                </div>
                @if($campaign->status !== 'draft')
                    <div class="mt-4 rounded bg-gray-50 p-3">
                        <p class="text-sm font-medium">Lien à partager aux employés :</p>
                        <a class="break-all text-sm text-blue-700 underline" href="{{ route('menu.registration.show', $campaign->link_token) }}">
                            {{ route('menu.registration.show', $campaign->link_token) }}
                        </a>
                        <a href="{{ route('menu.manager.export', $campaign) }}" class="mt-3 inline-block rounded bg-green-700 px-3 py-2 text-sm font-medium text-white hover:bg-green-800">
                            Télécharger l’Excel
                        </a>
                    </div>
                @endif
            </section>
        @empty
            <div class="rounded-lg bg-white p-8 text-center text-gray-600 shadow-sm">Aucune semaine de menus n’a encore été créée.</div>
        @endforelse
    </div>
    <div class="mt-5">{{ $campaigns->links() }}</div>
@endsection
