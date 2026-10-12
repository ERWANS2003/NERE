<!-- Search Modal (Cmd+K) -->
<div 
    x-data="window.IntranetModules.initGlobalSearch()"
    @open-search.window="open()"
>
    <!-- Modal Overlay -->
    <div 
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-graphite-900/50 backdrop-blur-sm z-50"
        style="display: none;"
    >
        <!-- Modal Content -->
        <div class="flex items-start justify-center min-h-screen pt-20 px-4">
            <div 
                @click.away="close()"
                @keydown.escape="close()"
                @keydown.down.prevent="selectNext()"
                @keydown.up.prevent="selectPrevious()"
                @keydown.enter.prevent="selectResult()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden"
            >
                
                <!-- Search Input -->
                <div class="relative border-b border-graphite-200">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        x-ref="searchInput"
                        x-model="query"
                        @input.debounce.300ms="search()"
                        type="text"
                        placeholder="Rechercher des employés, départements, documents..."
                        class="w-full pl-12 pr-4 py-4 text-lg bg-transparent border-0 focus:ring-0 focus:outline-none text-graphite-900 placeholder-graphite-400"
                    />
                </div>

                <!-- Search Results -->
                <div class="max-h-96 overflow-y-auto">
                    
                    <!-- Empty State -->
                    <div 
                        x-show="query.length === 0"
                        class="p-8 text-center"
                    >
                        <svg class="w-16 h-16 mx-auto text-graphite-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <p class="text-graphite-600 mb-2">Rechercher dans l'intranet</p>
                        <p class="text-sm text-graphite-500">
                            Employés · Départements · Annonces · Documents · Événements
                        </p>
                    </div>

                    <!-- No Results -->
                    <div 
                        x-show="query.length > 0 && results.length === 0"
                        class="p-8 text-center"
                    >
                        <svg class="w-16 h-16 mx-auto text-graphite-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-graphite-600 mb-1">Aucun résultat trouvé</p>
                        <p class="text-sm text-graphite-500">
                            Essayez avec d'autres mots-clés
                        </p>
                    </div>

                    <!-- Results List -->
                    <ul x-show="results.length > 0" class="py-2">
                        <template x-for="(result, index) in results" :key="result.id">
                            <li>
                                <button
                                    @click="navigateTo(result)"
                                    :class="{ 'bg-graphite-50': index === selectedIndex }"
                                    class="w-full flex items-center gap-4 px-4 py-3 hover:bg-graphite-50 transition text-left"
                                >
                                    <!-- Icon/Avatar -->
                                    <div class="flex-shrink-0">
                                        <span 
                                            x-text="getResultIcon(result.type)"
                                            class="text-2xl"
                                        ></span>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-graphite-900" x-html="highlight(result, 'title')"></div>
                                        <div 
                                            x-show="result.description"
                                            class="text-sm text-graphite-500 truncate" 
                                            x-html="highlight(result, 'description')"
                                        ></div>
                                    </div>

                                    <!-- Type Badge -->
                                    <div class="flex-shrink-0">
                                        <span 
                                            class="px-2 py-1 text-xs font-medium rounded bg-graphite-100 text-graphite-600"
                                            x-text="getTypeLabel(result.type)"
                                        ></span>
                                    </div>
                                </button>
                            </li>
                        </template>
                    </ul>

                </div>

                <!-- Footer -->
                <div class="border-t border-graphite-200 px-4 py-3 bg-graphite-50 flex items-center justify-between text-xs text-graphite-500">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1">
                            <kbd class="px-2 py-1 bg-white border border-graphite-200 rounded">↑</kbd>
                            <kbd class="px-2 py-1 bg-white border border-graphite-200 rounded">↓</kbd>
                            <span class="ml-1">Naviguer</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="px-2 py-1 bg-white border border-graphite-200 rounded">↵</kbd>
                            <span class="ml-1">Ouvrir</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="px-2 py-1 bg-white border border-graphite-200 rounded">Esc</kbd>
                            <span class="ml-1">Fermer</span>
                        </span>
                    </div>
                    <span>Recherche globale</span>
                </div>

            </div>
        </div>
    </div>
</div>
