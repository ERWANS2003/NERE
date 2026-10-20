<x-intranet-layout title="Ressources">

    @php
        // Icône/teinte selon le type de fichier.
        $typeStyles = [
            'pdf' => ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
            'doc' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
            'docx' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
            'xls' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
            'xlsx' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
            'ppt' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
            'pptx' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
            'video' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
        ];
    @endphp

    <!-- Page Header -->
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Bibliothèque de ressources</h1>
            <p class="text-graphite-600">Documents, guides, procédures et formulaires</p>
        </div>
        @can('create', App\Models\Resource::class)
            <a href="{{ route('intranet.resources.create') }}" class="bouton-primaire flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ajouter une ressource
            </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Sidebar : catégories + populaires + récents -->
        <aside class="lg:col-span-1 space-y-6">

            <!-- Catégories avec compteurs -->
            <nav class="bg-white rounded-xl border border-graphite-200 p-4" aria-label="Catégories">
                <h2 class="text-sm font-semibold text-graphite-900 mb-3">Catégories</h2>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('intranet.resources') }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition {{ ! $filters['category'] && ! $filters['favorites'] ? 'bg-nere-50 text-nere-700 font-medium' : 'text-graphite-700 hover:bg-graphite-50' }}">
                            <span>Toutes</span>
                            <span class="text-xs text-graphite-500">{{ $categories->sum('resources_count') }}</span>
                        </a>
                    </li>
                    @foreach($categories as $categorie)
                        <li>
                            <a href="{{ route('intranet.resources', ['category' => $categorie->id]) }}"
                               class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition {{ $filters['category'] === $categorie->id ? 'bg-nere-50 text-nere-700 font-medium' : 'text-graphite-700 hover:bg-graphite-50' }}">
                                <span>{{ $categorie->name }}</span>
                                <span class="text-xs text-graphite-500">{{ $categorie->resources_count }}</span>
                            </a>
                        </li>
                    @endforeach
                    <li class="pt-1 mt-1 border-t border-graphite-100">
                        <a href="{{ route('intranet.resources', ['favorites' => 1]) }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition {{ $filters['favorites'] ? 'bg-or-50 text-or-800 font-medium' : 'text-graphite-700 hover:bg-graphite-50' }}">
                            <svg class="w-4 h-4" fill="{{ $filters['favorites'] ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            Mes favoris
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Populaires -->
            @if($popular->isNotEmpty())
                <div class="bg-white rounded-xl border border-graphite-200 p-4">
                    <h2 class="text-sm font-semibold text-graphite-900 mb-3">Les plus téléchargés</h2>
                    <ul class="space-y-2">
                        @foreach($popular as $p)
                            <li>
                                <a href="{{ route('intranet.resources.show', $p) }}" class="group block">
                                    <span class="text-sm text-graphite-800 group-hover:text-nere-600 transition line-clamp-1">{{ $p->title }}</span>
                                    <span class="text-xs text-graphite-500">{{ $p->download_count }} téléch.</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Récents -->
            @if($recent->isNotEmpty())
                <div class="bg-white rounded-xl border border-graphite-200 p-4">
                    <h2 class="text-sm font-semibold text-graphite-900 mb-3">Ajouts récents</h2>
                    <ul class="space-y-2">
                        @foreach($recent as $r)
                            <li>
                                <a href="{{ route('intranet.resources.show', $r) }}" class="group block">
                                    <span class="text-sm text-graphite-800 group-hover:text-nere-600 transition line-clamp-1">{{ $r->title }}</span>
                                    <span class="text-xs text-graphite-500">{{ $r->created_at?->format('d/m/Y') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>

        <!-- Contenu principal : recherche + filtres + grille -->
        <div class="lg:col-span-3 space-y-6">

            <!-- Barre de recherche + filtres -->
            <form method="GET" action="{{ route('intranet.resources') }}" data-live-search class="bg-white rounded-xl border border-graphite-200 p-4">
                @if($filters['favorites'])
                    <input type="hidden" name="favorites" value="1">
                @endif
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label for="search" class="sr-only">Rechercher</label>
                        <input
                            type="search" id="search" name="search" value="{{ $filters['search'] }}"
                            placeholder="Rechercher un document, un mot-clé…"
                            class="champ w-full"
                        >
                    </div>
                    <div class="flex gap-3">
                        <div>
                            <label for="type" class="sr-only">Type</label>
                            <select id="type" name="type" class="champ" onchange="this.form.submit()">
                                <option value="">Tous les types</option>
                                @foreach($types as $t)
                                    <option value="{{ $t }}" @selected($filters['type'] === $t)>{{ strtoupper($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="department" class="sr-only">Département</label>
                            <select id="department" name="department" class="champ" onchange="this.form.submit()">
                                <option value="">Tous départements</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected($filters['department'] === $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bouton-primaire">Filtrer</button>
                    </div>
                </div>
                @if(array_filter([$filters['search'], $filters['type'], $filters['department'], $filters['category'], $filters['favorites']]))
                    <div class="mt-3 text-sm">
                        <a href="{{ route('intranet.resources') }}" class="text-nere-600 hover:text-nere-700">Réinitialiser les filtres</a>
                    </div>
                @endif
            </form>

            <!-- Résultats -->
            <p class="text-sm text-graphite-500">{{ $resources->count() }} ressource{{ $resources->count() > 1 ? 's' : '' }}</p>

            @if($resources->isEmpty())
                <div class="bg-white rounded-lg border border-graphite-200 p-12 text-center">
                    <svg class="w-16 h-16 text-graphite-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-graphite-600">Aucune ressource ne correspond à votre recherche</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($resources as $resource)
                        @php $style = $typeStyles[$resource->file_type] ?? ['bg' => 'bg-graphite-100', 'text' => 'text-graphite-600']; @endphp
                        <div class="bg-white rounded-lg border border-graphite-200 p-4 hover:shadow-md transition group">
                            <div class="flex items-start gap-4">

                                <!-- Icône type -->
                                <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center {{ $style['bg'] }}">
                                    <svg class="w-6 h-6 {{ $style['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="font-semibold text-graphite-900 truncate group-hover:text-nere-600 transition">
                                            <a href="{{ route('intranet.resources.show', $resource) }}">{{ $resource->title }}</a>
                                        </h3>

                                        <!-- Favori (étoile) -->
                                        <form method="POST" action="{{ route('intranet.resources.favorite', $resource) }}" class="flex-shrink-0">
                                            @csrf
                                            @php $estFavori = in_array($resource->id, $favorites, true); @endphp
                                            <button type="submit" class="text-graphite-300 hover:text-or-500 transition {{ $estFavori ? 'text-or-500' : '' }}"
                                                    title="{{ $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
                                                    aria-label="{{ $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                                                <svg class="w-5 h-5" fill="{{ $estFavori ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>

                                    @if($resource->description)
                                        <p class="text-xs text-graphite-600 mt-1 line-clamp-2">{{ $resource->description }}</p>
                                    @endif

                                    <!-- Badge catégorie + tags -->
                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                        @if($resource->category)
                                            <span class="px-2 py-0.5 rounded bg-nere-50 text-nere-700 text-xs font-medium">{{ $resource->category->name }}</span>
                                        @endif
                                        @foreach(array_slice($resource->tags ?? [], 0, 3) as $tag)
                                            <span class="px-2 py-0.5 rounded bg-graphite-100 text-graphite-600 text-xs">{{ $tag }}</span>
                                        @endforeach
                                    </div>

                                    <!-- Métadonnées -->
                                    <div class="flex items-center gap-3 text-xs text-graphite-500 mt-2">
                                        <span class="uppercase">{{ $resource->file_type }}</span>
                                        @if($resource->file_size)<span>{{ $resource->file_size }}</span>@endif
                                        <span>{{ $resource->download_count }} téléch.</span>
                                        <span>{{ $resource->created_at?->format('d/m/Y') }}</span>
                                    </div>

                                    <a href="{{ route('intranet.resources.show', $resource) }}"
                                       class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-nere-600 hover:text-nere-700 transition">
                                        Voir la fiche
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</x-intranet-layout>
