@extends('layouts.portal')

@section('titre', 'Base de Connaissances')
@section('sous-titre', 'Documentation & Procédures')

@section('contenu')
<div class="px-6 py-4 space-y-6">
    
    <!-- Search & Filter Section -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Rechercher des articles</h3>
        <form method="GET" action="{{ route('knowledge.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="q" class="block text-sm font-medium text-gray-300 mb-2">Terme de recherche</label>
                    <input type="search" id="q" name="q" value="{{ request('q') }}" 
                           placeholder="Titre, procédure, mot-clé…"
                           class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition">
                </div>
                <div>
                    <label for="per_page" class="block text-sm font-medium text-gray-300 mb-2">Par page</label>
                    <select id="per_page" name="per_page" class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="10" @selected(($perPage ?? 15) == 10)>10 par page</option>
                        <option value="15" @selected(($perPage ?? 15) == 15)>15 par page</option>
                        <option value="25" @selected(($perPage ?? 15) == 25)>25 par page</option>
                        <option value="50" @selected(($perPage ?? 15) == 50)>50 par page</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                    Rechercher
                </button>
                @if(request()->hasAny(['q', 'per_page']))
                    <a href="{{ route('knowledge.index') }}" class="px-6 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg font-medium transition">
                        Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Articles Count -->
    @if(!$articles->isEmpty())
    <div class="flex items-center justify-between">
        <p class="text-gray-400">
            <span class="font-semibold text-white">{{ $articles->total() }}</span>
            article{{ $articles->total() > 1 ? 's' : '' }} trouvé{{ $articles->total() > 1 ? 's' : '' }}
        </p>
    </div>
    @endif

    <!-- Articles List -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden">
        <div class="p-6 border-b border-dark-700">
            <h3 class="text-lg font-semibold text-white">Articles publiés</h3>
        </div>
        
        @if($articles->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <p class="text-gray-400 text-lg">Aucun article ne correspond à votre recherche.</p>
                <p class="text-gray-500 text-sm mt-2">Essayez avec des mots-clés différents ou consultez tous les articles.</p>
            </div>
        @else
            <div class="divide-y divide-dark-700">
                @foreach($articles as $article)
                <a href="{{ route('knowledge.show', $article) }}" 
                   class="block p-6 hover:bg-dark-700/50 transition group">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-lg font-semibold text-white group-hover:text-primary-400 transition truncate">
                                {{ $article->titre }}
                            </h4>
                            <p class="text-gray-400 text-sm mt-2 line-clamp-2">
                                {{ Str::limit(strip_tags($article->contenu), 200) }}
                            </p>
                            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 012 12V7a2 2 0 012-2z"></path>
                                    </svg>
                    {{ $article->categorie?->nom ?? 'Général' }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                    {{ number_format($article->vues) }} vue{{ $article->vues > 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-600 group-hover:text-primary-400 transition flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
                @endforeach
            </div>

            <!-- Pagination & Info -->
            @if($articles->hasPages())
            <div class="px-6 py-4 border-t border-dark-700 bg-dark-900/50">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-400">
                        Affichage de <span class="font-semibold">{{ $articles->firstItem() ?? 0 }}</span> 
                        à <span class="font-semibold">{{ $articles->lastItem() ?? 0 }}</span> 
                        sur <span class="font-semibold">{{ $articles->total() }}</span> articles
                    </p>
                    <div>
                        {{ $articles->links('pagination::tailwind') }}
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>

    <!-- Publish Article Section -->
    <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Publier une fiche procédure / article
        </h3>

        <form method="POST" action="{{ route('knowledge.store') }}" class="space-y-4">
            @csrf
            
            <!-- Title -->
            <div>
                <label for="titre" class="block text-sm font-medium text-gray-300 mb-2">
                    Titre de l'article <span class="text-red-400">*</span>
                </label>
                <input type="text" id="titre" name="titre" value="{{ old('titre') }}" required
                       placeholder="Ex : Comment réinitialiser son mot de passe VPN"
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition @error('titre') border-red-500 @enderror">
                @error('titre')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div>
                <label for="contenu" class="block text-sm font-medium text-gray-300 mb-2">
                    Contenu explicatif <span class="text-red-400">*</span>
                </label>
                <textarea id="contenu" name="contenu" rows="6" required
                          placeholder="Décrivez les étapes à suivre, les bonnes pratiques, etc."
                          class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition resize-none @error('contenu') border-red-500 @enderror">{{ old('contenu') }}</textarea>
                @error('contenu')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div>
                <label for="ticket_category_id" class="block text-sm font-medium text-gray-300 mb-2">
                    Catégorie (optionnel)
                </label>
                <select id="ticket_category_id" name="ticket_category_id"
                        class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                    <option value="">-- Sélectionner une catégorie --</option>
                    @forelse($categories ?? [] as $category)
                        <option value="{{ $category->id }}" @selected(old('ticket_category_id') == $category->id)>
                            {{ $category->nom }}
                        </option>
                    @empty
                    @endforelse
                </select>
            </div>

            <!-- Keywords -->
            <div>
                <label for="mots_cles" class="block text-sm font-medium text-gray-300 mb-2">
                    Mots-clés (séparés par des virgules)
                </label>
                <input type="text" id="mots_cles" name="mots_cles" value="{{ old('mots_cles') }}"
                       placeholder="vpn, reinitialisation, acces, reseau"
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition">
                <p class="text-gray-500 text-xs mt-1">Ces mots-clés amélioreront la recherche et la découverte de votre article.</p>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Publier l'article
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
