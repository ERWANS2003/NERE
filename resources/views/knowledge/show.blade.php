@extends('layouts.portal')

@section('titre', $article->titre)
@section('sous-titre', 'Article de la base de connaissances')

@section('contenu')
<div class="px-6 py-4">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('knowledge.index') }}" class="inline-flex items-center gap-2 text-primary-400 hover:text-primary-300 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            <span>Retour aux articles</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Article Content -->
        <div class="lg:col-span-2">
            <article class="bg-dark-800 rounded-xl border border-dark-700 p-8">
                <!-- Article Header -->
                <div class="mb-6 pb-6 border-b border-dark-700">
                    <h1 class="text-3xl font-bold text-white mb-3">{{ $article->titre }}</h1>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-xs font-semibold">{{ substr($article->auteur?->name ?? 'S', 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-300">{{ $article->auteur?->name ?? 'Support' }}</p>
                                <p class="text-xs text-gray-500">Publié le {{ $article->created_at->format('d M Y à H:i') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span>{{ number_format($article->vues) }} vue{{ $article->vues > 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Article Content -->
                <div class="prose prose-invert max-w-none">
                    <div class="text-gray-300 leading-relaxed whitespace-pre-wrap break-words">
                        {{ $article->contenu }}
                    </div>
                </div>

                <!-- Article Footer -->
                <div class="mt-8 pt-6 border-t border-dark-700">
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-400">Cet article a-t-il été utile?</span>
                        <div class="flex gap-2">
                            <button onclick="alert('Merci!')" class="px-4 py-2 bg-green-900/30 hover:bg-green-900/50 border border-green-700 text-green-300 rounded-lg transition inline-flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.646 7.23a2 2 0 01-1.789 1.106H9m0 0a2 2 0 100-4m0 4a2 2 0 110-4m0 4V5a2 2 0 114 0"></path>
                                </svg>
                                <span>Oui, utile</span>
                            </button>
                            <button onclick="alert('Merci pour votre retour')" class="px-4 py-2 bg-red-900/30 hover:bg-red-900/50 border border-red-700 text-red-300 rounded-lg transition inline-flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.23l-.02-.023m-.02.023L2.95 7m15.25 13.6A2 2 0 0015 18H4m0 0a2 2 0 01-.02-4m.021 4a6 6 0 1011.98 0m0 0a2 2 0 01-.765-1.235m0 0a24.056 24.056 0 01-7.069-2.468m0 0a24.076 24.076 0 013.213-3.956m7.069 2.468c.564.784 1.077 1.654 1.468 2.468M9 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Peu utile</span>
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <div class="bg-dark-800 rounded-xl border border-dark-700 p-6 sticky top-6">
                <h3 class="text-lg font-semibold text-white mb-4">Informations</h3>
                
                <div class="space-y-4">
                    <!-- Category -->
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Catégorie</p>
                        <p class="text-gray-200 mt-1">
                            @if($article->categorie)
                                <span class="inline-block px-3 py-1 bg-primary-600/20 text-primary-300 rounded-full text-sm font-medium">
                                    {{ $article->categorie->nom }}
                                </span>
                            @else
                                <span class="text-gray-500">Général</span>
                            @endif
                        </p>
                    </div>

                    <!-- Keywords -->
                    <div class="pt-4 border-t border-dark-700">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Mots-clés</p>
                        @if($article->mots_cles)
                            <div class="flex flex-wrap gap-2">
                                @foreach(explode(',', $article->mots_cles) as $keyword)
                                    <a href="{{ route('knowledge.index', ['q' => trim($keyword)]) }}" 
                                       class="px-2 py-1 bg-dark-900 hover:bg-dark-700 border border-dark-600 text-gray-300 hover:text-white rounded text-xs transition">
                                        {{ trim($keyword) }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-sm">Aucun mot-clé</p>
                        @endif
                    </div>

                    <!-- Stats -->
                    <div class="pt-4 border-t border-dark-700">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Statistiques</p>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Vues</span>
                                <span class="font-semibold text-white">{{ number_format($article->vues) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Utile</span>
                                <span class="font-semibold text-green-400">{{ number_format($article->utile_count ?? 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Back to List -->
                    <div class="pt-4 border-t border-dark-700">
                        <a href="{{ route('knowledge.index') }}" 
                           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Voir tous les articles</span>
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
