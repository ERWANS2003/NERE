@php
    $breadcrumbs = [
        ['label' => 'Base de connaissances'],
    ];
@endphp

<x-intranet-layout title="Base de connaissances" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        title="Base de connaissances"
        description="Des solutions simples aux problèmes courants. Consultez un guide avant d’envoyer un ticket au support."
    >
        <x-slot:actions>
            @if($isManager)
                <x-button :href="route('intranet.knowledge-base.create')">
                    <span aria-hidden="true">+</span> Ajouter un guide
                </x-button>
            @endif
        </x-slot:actions>
    </x-intranet.page-header>

    <x-intranet.filters as-form action="{{ route('intranet.knowledge-base.index') }}" label="Rechercher et filtrer les guides" data-live-search
          class="mb-6 grid gap-3 lg:grid-cols-[minmax(0,1fr)_240px_auto]">
        <div>
            <label for="knowledge-search" class="sr-only">Rechercher un guide</label>
            <input id="knowledge-search" type="search" name="search" value="{{ request('search') }}"
                   placeholder="Ex. mot de passe, imprimante, réseau"
                   class="champ">
        </div>
        <div>
            <label for="knowledge-category" class="sr-only">Catégorie</label>
            <select id="knowledge-category" name="category" class="champ">
                <option value="">Toutes les catégories</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <x-button type="submit" variant="secondary">Rechercher</x-button>
    </x-intranet.filters>

    <p class="mb-4 text-sm text-graphite-600" aria-live="polite">
        {{ $articles->total() }} guide(s) disponible(s)
    </p>

    @if($articles->isNotEmpty())
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach($articles as $article)
                <x-card as="article" class="flex flex-col">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        @if($article->category && isset($categories[$article->category]))
                            <span class="rounded-full bg-nere-50 px-3 py-1 text-xs font-semibold text-nere-700">
                                {{ $categories[$article->category] }}
                            </span>
                        @endif
                        @unless($article->is_published)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">Brouillon</span>
                        @endunless
                    </div>
                    <h2 class="text-lg font-bold text-graphite-900">
                        <a href="{{ route('intranet.knowledge-base.show', $article) }}" class="rounded hover:text-nere-700 focus-visible:outline">
                            {{ $article->title }}
                        </a>
                    </h2>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-graphite-600">{{ $article->summary }}</p>
                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-graphite-100 pt-3">
                        <span class="text-xs text-graphite-500">
                            {{ $article->helpful_count }} personne(s) ont trouvé ce guide utile
                        </span>
                        <a href="{{ route('intranet.knowledge-base.show', $article) }}"
                           class="shrink-0 text-sm font-semibold text-nere-700 hover:text-nere-900">
                            Lire le guide <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </x-card>
            @endforeach
        </div>

        @if($articles->hasPages())
            <div class="mt-6">{{ $articles->links() }}</div>
        @endif
    @else
        <x-card class="p-10 text-center">
            <h2 class="font-semibold text-graphite-900">Aucun guide trouvé</h2>
            <p class="mt-2 text-sm text-graphite-600">Essayez d’autres mots-clés ou choisissez une autre catégorie.</p>
            <a href="{{ route('intranet.knowledge-base.index') }}" class="mt-4 inline-flex text-sm font-semibold text-nere-700 hover:underline">
                Afficher tous les guides
            </a>
        </x-card>
    @endif

    <div class="mt-8 rounded-xl border border-or-200 bg-or-50 p-5">
        <h2 class="font-semibold text-graphite-900">Vous ne trouvez pas la solution ?</h2>
        <p class="mt-1 text-sm text-graphite-700">Le support informatique peut vous aider si le problème persiste.</p>
        <a href="{{ route('intranet.tickets.create') }}" class="mt-3 inline-flex text-sm font-semibold text-nere-700 hover:underline">
            Créer un ticket IT <span class="ml-1" aria-hidden="true">→</span>
        </a>
    </div>
</x-intranet-layout>
