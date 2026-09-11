@extends('layouts.app-new')

@section('titre', 'Base de Connaissances')

@section('contenu')
<div class="max-w-7xl mx-auto" x-data="knowledgeSearch()">
    <!-- Hero Search Section -->
    <div class="bg-gradient-to-br from-accent-500 to-accent-600 rounded-2xl shadow-lg p-12 mb-8 text-white">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-4xl font-bold mb-4">Comment pouvons-nous vous aider ?</h1>
            <p class="text-accent-100 mb-8">Recherchez parmi {{ $articles->total() }} articles de notre base de connaissances</p>
            
            <!-- Search Bar avec IA -->
            <div class="relative">
                <input 
                    type="search" 
                    x-model="searchQuery"
                    @input.debounce.300ms="intelligentSearch()"
                    class="w-full px-6 py-4 pr-12 rounded-xl text-gray-900 placeholder-gray-500 focus:ring-4 focus:ring-white/30 focus:outline-none shadow-xl"
                    placeholder="Recherchez un article, une procédure, un mot-clé...">
                
                <button @click="intelligentSearch()" 
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-accent-600 hover:text-accent-700">
                    <x-icon name="search" size="lg" />
                </button>

                <!-- Loading Indicator -->
                <div x-show="searching" 
                     class="absolute right-14 top-1/2 -translate-y-1/2">
                    <x-icon name="refresh" size="md" class="text-accent-600 animate-spin" />
                </div>
            </div>

            <!-- AI Suggestions -->
            <div x-show="suggestions.length > 0" 
                 x-transition
                 class="mt-4 bg-white rounded-lg shadow-xl p-4 text-left">
                <p class="text-sm text-gray-600 mb-2">💡 Suggestions intelligentes:</p>
                <div class="space-y-2">
                    <template x-for="suggestion in suggestions" :key="suggestion.id">
                        <a :href="'/base-connaissances/' + suggestion.id" 
                           class="block p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="font-semibold text-gray-900" x-text="suggestion.titre"></div>
                            <div class="text-sm text-gray-600 line-clamp-1" x-text="suggestion.preview"></div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links / Categories -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="?q=mot de passe" class="quick-category bg-white hover:bg-gray-50 rounded-lg p-4 border-2 border-gray-200 hover:border-accent-500 transition text-center">
            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center mx-auto mb-2">
                <x-icon name="lock" size="lg" class="text-blue-600" />
            </div>
            <div class="font-semibold text-gray-900">Compte & Accès</div>
            <div class="text-sm text-gray-600 mt-1">Mots de passe, connexions</div>
        </a>

        <a href="?q=réseau" class="quick-category bg-white hover:bg-gray-50 rounded-lg p-4 border-2 border-gray-200 hover:border-accent-500 transition text-center">
            <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center mx-auto mb-2">
                <x-icon name="network" size="lg" class="text-green-600" />
            </div>
            <div class="font-semibold text-gray-900">Réseau & Connexion</div>
            <div class="text-sm text-gray-600 mt-1">VPN, WiFi, internet</div>
        </a>

        <a href="?q=application" class="quick-category bg-white hover:bg-gray-50 rounded-lg p-4 border-2 border-gray-200 hover:border-accent-500 transition text-center">
            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center mx-auto mb-2">
                <x-icon name="desktop" size="lg" class="text-purple-600" />
            </div>
            <div class="font-semibold text-gray-900">Logiciels & Apps</div>
            <div class="text-sm text-gray-600 mt-1">Installation, utilisation</div>
        </a>

        <a href="?q=matériel" class="quick-category bg-white hover:bg-gray-50 rounded-lg p-4 border-2 border-gray-200 hover:border-accent-500 transition text-center">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center mx-auto mb-2">
                <x-icon name="hardware" size="lg" class="text-amber-600" />
            </div>
            <div class="font-semibold text-gray-900">Matériel</div>
            <div class="text-sm text-gray-600 mt-1">Ordinateurs, imprimantes</div>
        </a>
    </div>

    <div class="grid grid-cols-3 gap-8">
        <!-- Articles List -->
        <div class="col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900">
                            @if(request('q'))
                            Résultats pour "{{ request('q') }}"
                            @else
                            Articles Populaires
                            @endif
                        </h2>
                        <span class="text-sm text-gray-600">{{ $articles->total() }} articles</span>
                    </div>
                </div>

                <!-- Articles -->
                @if($articles->isEmpty())
                <div class="p-12 text-center">
                    <x-icon name="search" size="xl" class="mx-auto text-gray-300 mb-4" />
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Aucun article trouvé</h3>
                    <p class="text-gray-500">Essayez une recherche différente ou créez un ticket</p>
                    <a href="{{ route('tickets.create') }}" class="btn-primary inline-flex items-center gap-2 mt-4 px-6 py-2 rounded-lg">
                        <x-icon name="plus" size="sm" />
                        Créer un Ticket
                    </a>
                </div>
                @else
                <div class="divide-y divide-gray-100">
                    @foreach($articles as $article)
                    <a href="{{ route('knowledge.show', $article) }}" 
                       class="block p-6 hover:bg-gray-50 transition group">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-accent-600 transition mb-2">
                                    {{ $article->titre }}
                                </h3>
                                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                    {{ Str::limit(strip_tags($article->contenu), 180) }}
                                </p>
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    @if($article->categorie)
                                    <span class="flex items-center gap-1">
                                        <x-icon name="tag" size="xs" />
                                        {{ $article->categorie->nom }}
                                    </span>
                                    @endif
                                    <span class="flex items-center gap-1">
                                        <x-icon name="eye" size="xs" />
                                        {{ number_format($article->vues) }} vues
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <x-icon name="clock" size="xs" />
                                        {{ $article->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-lg bg-accent-100 flex items-center justify-center group-hover:bg-accent-200 transition">
                                    <x-icon name="arrow-right" size="md" class="text-accent-600" />
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($articles->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $articles->links() }}
                </div>
                @endif
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-span-1">
            <!-- Popular Articles -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <x-icon name="fire" size="md" class="text-red-500" />
                    Articles Populaires
                </h3>
                <div class="space-y-3">
                    @foreach(\App\Models\KnowledgeArticle::where('publie', true)->orderBy('vues', 'desc')->limit(5)->get() as $popular)
                    <a href="{{ route('knowledge.show', $popular) }}" 
                       class="block p-3 hover:bg-gray-50 rounded-lg transition">
                        <div class="font-semibold text-sm text-gray-900 line-clamp-2 mb-1">
                            {{ $popular->titre }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ number_format($popular->vues) }} vues
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>

            <!-- Create Article CTA -->
            @can('knowledge.create')
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 text-white">
                <h3 class="text-lg font-bold mb-2">Partager vos Connaissances</h3>
                <p class="text-blue-100 text-sm mb-4">Aidez vos collègues en publiant un article</p>
                <a href="#create-article" 
                   @click.prevent="$dispatch('open-create-modal')"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white text-blue-600 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <x-icon name="plus" size="sm" />
                    Créer un Article
                </a>
            </div>
            @endcan
        </div>
    </div>
</div>

@push('scripts')
<script>
function knowledgeSearch() {
    return {
        searchQuery: '{{ request('q') }}',
        searching: false,
        suggestions: [],

        async intelligentSearch() {
            if (this.searchQuery.length < 3) {
                this.suggestions = [];
                return;
            }

            this.searching = true;

            try {
                const response = await fetch(`/base-connaissances/suggerer?terme=${encodeURIComponent(this.searchQuery)}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const articles = await response.json();
                this.suggestions = articles.slice(0, 5);
            } catch (error) {
                console.error('Erreur recherche:', error);
            } finally {
                this.searching = false;
            }
        }
    };
}
</script>
@endpush

<style>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
