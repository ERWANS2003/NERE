<x-intranet-layout title="Annonces">

    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">
                Annonces
            </h1>
            <p class="text-graphite-600">
                Toutes les annonces et communications de l'entreprise
            </p>
        </div>

        @can('create', \App\Models\Announcement::class)
            <a href="{{ route('intranet.announcements.create') }}" class="bouton-primaire shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvelle annonce
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Recherche + Filtres -->
    <div class="flex flex-col gap-4 mb-8">
        <form method="GET" class="flex gap-2">
            @if(request('priority'))
                <input type="hidden" name="priority" value="{{ request('priority') }}">
            @endif
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Rechercher une annonce..."
                class="champ max-w-sm"
            >
            <button type="submit" class="bouton-secondaire shrink-0">Rechercher</button>
            @if(request('search'))
                <a href="{{ route('intranet.announcements.index', ['priority' => request('priority')]) }}" class="bouton-secondaire shrink-0">
                    Réinitialiser
                </a>
            @endif
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('intranet.announcements.index', ['search' => request('search')]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium {{ !request('priority') ? 'bg-nere-600 text-white' : 'bg-white text-graphite-700 border border-graphite-200 hover:bg-graphite-50' }} transition">
                Toutes
            </a>
            <a href="{{ route('intranet.announcements.index', ['priority' => 'urgent', 'search' => request('search')]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium {{ request('priority') === 'urgent' ? 'bg-nere-600 text-white' : 'bg-white text-graphite-700 border border-graphite-200 hover:bg-graphite-50' }} transition">
                Urgentes
            </a>
            <a href="{{ route('intranet.announcements.index', ['priority' => 'important', 'search' => request('search')]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium {{ request('priority') === 'important' ? 'bg-nere-600 text-white' : 'bg-white text-graphite-700 border border-graphite-200 hover:bg-graphite-50' }} transition">
                Importantes
            </a>
            <a href="{{ route('intranet.announcements.index', ['priority' => 'info', 'search' => request('search')]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium {{ request('priority') === 'info' ? 'bg-nere-600 text-white' : 'bg-white text-graphite-700 border border-graphite-200 hover:bg-graphite-50' }} transition">
                Info
            </a>
        </div>
    </div>

    <!-- Announcements List -->
    <div class="space-y-6">
        @forelse($announcements as $announcement)
            <article class="bg-white rounded-lg border border-graphite-200 overflow-hidden hover:shadow-md transition">

                <!-- Header -->
                <div class="p-6 border-b border-graphite-100">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <!-- Priority Badge -->
                                <x-badge :type="$announcement->priority === 'urgent' ? 'danger' : ($announcement->priority === 'important' ? 'warning' : 'info')">
                                    {{ ucfirst($announcement->priority) }}
                                </x-badge>

                                <span class="text-xs text-graphite-500">Toute l'entreprise</span>

                                <!-- Date -->
                                <span class="text-xs text-graphite-500">
                                    {{ $announcement->published_at?->diffForHumans() }}
                                </span>
                            </div>

                            <h2 class="text-xl font-bold text-graphite-900 mb-2">
                                {{ $announcement->title }}
                            </h2>
                        </div>

                        <!-- Creator Avatar -->
                        <x-avatar :user="$announcement->creator" size="md" />
                    </div>
                </div>

                <!-- Content Preview -->
                <div class="p-6">
                    {{-- Texte brut échappé par Blade : une annonce n'est jamais interprétée comme HTML. --}}
                    <p class="text-graphite-700 whitespace-pre-line line-clamp-3">{{ $announcement->body }}</p>

                    <div class="flex items-center justify-between mt-4">
                        <a
                            href="{{ route('intranet.announcements.show', $announcement) }}"
                            class="inline-flex items-center gap-2 text-nere-600 hover:text-nere-700 font-medium text-sm transition"
                        >
                            <span>Lire la suite</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                        @can('update', $announcement)
                            <a href="{{ route('intranet.announcements.edit', $announcement) }}" class="text-sm text-graphite-500 hover:text-graphite-700 font-medium">
                                Modifier
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 bg-graphite-50 flex items-center justify-between text-xs text-graphite-600">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>{{ $announcement->creator->name }}</span>
                    </div>
                    <div>
                        Publié le {{ $announcement->published_at?->format('d/m/Y à H:i') }}
                    </div>
                </div>

            </article>
        @empty
            <div class="bg-white rounded-lg border border-graphite-200 p-12 text-center">
                <svg class="w-16 h-16 text-graphite-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                <p class="text-graphite-600 mb-2">Aucune annonce</p>
                <p class="text-sm text-graphite-500">
                    @if(request('search') || request('priority'))
                        Aucun résultat pour ces critères.
                    @else
                        Il n'y a pas d'annonces pour le moment.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($announcements->hasPages())
        <div class="mt-8">
            {{ $announcements->links() }}
        </div>
    @endif

</x-intranet-layout>
