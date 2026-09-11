@extends('layouts.app-new')

@section('titre', 'Créer un Ticket - Intelligence Assistée')

@section('contenu')
<div class="max-w-7xl mx-auto" x-data="smartTicketCreation()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Créer un Nouveau Ticket</h1>
        <p class="text-gray-600 mt-1">Notre assistant intelligent vous aide à optimiser votre demande</p>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <!-- Formulaire Principal -->
        <div class="col-span-2">
            <form @submit.prevent="submitTicket" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <!-- Titre -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Titre du Ticket <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        x-model="form.title"
                        @input.debounce.500ms="analyzeTicket()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500"
                        placeholder="Ex: Problème avec l'imprimante du bureau"
                        required>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Description Détaillée <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        x-model="form.description"
                        @input.debounce.500ms="analyzeTicket()"
                        rows="6"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500"
                        placeholder="Décrivez le problème en détail..."
                        required></textarea>
                    
                    <!-- Indicateur d'analyse -->
                    <div x-show="analyzing" class="mt-2 flex items-center gap-2 text-sm text-blue-600">
                        <x-icon name="refresh" size="sm" class="animate-spin" />
                        <span>Analyse en cours...</span>
                    </div>
                </div>

                <!-- Suggestions Appliquées -->
                <div x-show="suggestions.priority || suggestions.category" class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">✨ Suggestions Appliquées</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Priorité Suggérée -->
                        <div x-show="suggestions.priority" class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-blue-800">Priorité Suggérée</span>
                                <span class="text-xs text-blue-600" x-text="(suggestions.priority?.confidence * 100).toFixed(0) + '% confiance'"></span>
                            </div>
                            <div class="font-semibold text-blue-900" x-text="suggestions.priority?.name"></div>
                            <p class="text-xs text-blue-700 mt-1" x-text="suggestions.priority?.reason"></p>
                        </div>

                        <!-- Catégorie Suggérée -->
                        <div x-show="suggestions.category" class="bg-green-50 rounded-lg p-3 border border-green-200">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-green-800">Catégorie Suggérée</span>
                            </div>
                            <div class="font-semibold text-green-900" x-text="suggestions.category?.name"></div>
                        </div>
                    </div>
                </div>

                <!-- Sélecteurs Standard -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catégorie</label>
                        <select 
                            x-model="form.category_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500">
                            <option value="">-- Sélectionner --</option>
                            @foreach(\App\Models\TicketCategory::all() as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Priorité</label>
                        <select 
                            x-model="form.priority_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500">
                            <option value="">-- Sélectionner --</option>
                            @foreach(\App\Models\TicketPriority::all() as $priority)
                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('tickets.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                        Annuler
                    </a>
                    <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                        <x-icon name="check" size="sm" />
                        Créer le Ticket
                    </button>
                </div>
            </form>
        </div>

        <!-- Panneau de Suggestions -->
        <div class="col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sticky top-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <x-icon name="lightbulb" size="md" class="text-amber-500" />
                    Assistant Intelligent
                </h3>

                <!-- Articles Suggérés -->
                <div x-show="suggestions.knowledge_articles?.length > 0" class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">📚 Articles Suggérés</h4>
                    <p class="text-xs text-gray-600 mb-3">Ces articles pourraient résoudre votre problème</p>
                    <div class="space-y-2">
                        <template x-for="article in suggestions.knowledge_articles" :key="article.id">
                            <a :href="article.url" target="_blank"
                               class="block bg-blue-50 hover:bg-blue-100 rounded-lg p-3 border border-blue-200 transition">
                                <div class="flex items-start gap-2">
                                    <x-icon name="book" size="sm" class="text-blue-600 flex-shrink-0 mt-0.5" />
                                    <span class="text-sm text-blue-900 font-medium" x-text="article.title"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                <!-- Tickets Similaires -->
                <div x-show="suggestions.similar_tickets?.length > 0" class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">🔍 Tickets Similaires</h4>
                    <p class="text-xs text-gray-600 mb-3">Ces tickets ressemblent au vôtre</p>
                    <div class="space-y-2">
                        <template x-for="ticket in suggestions.similar_tickets" :key="ticket.id">
                            <a :href="ticket.url" target="_blank"
                               class="block bg-amber-50 hover:bg-amber-100 rounded-lg p-3 border border-amber-200 transition">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <span class="text-xs font-mono text-amber-700">#<span x-text="ticket.id"></span></span>
                                    <span class="px-2 py-0.5 bg-amber-200 text-amber-800 rounded text-xs font-semibold" x-text="ticket.status"></span>
                                </div>
                                <p class="text-sm text-amber-900 font-medium" x-text="ticket.title"></p>
                            </a>
                        </template>
                    </div>
                </div>

                <!-- Analyse Sentiment -->
                <div x-show="suggestions.sentiment" class="mb-6">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">💭 Analyse de Sentiment</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Urgence:</span>
                            <span class="font-semibold" 
                                  :class="suggestions.sentiment?.urgency === 'high' ? 'text-red-600' : 'text-green-600'"
                                  x-text="suggestions.sentiment?.urgency === 'high' ? 'Haute' : 'Normale'"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Frustration:</span>
                            <span class="font-semibold"
                                  :class="{
                                      'text-red-600': suggestions.sentiment?.frustration === 'high',
                                      'text-amber-600': suggestions.sentiment?.frustration === 'medium',
                                      'text-green-600': suggestions.sentiment?.frustration === 'low'
                                  }"
                                  x-text="suggestions.sentiment?.frustration"></span>
                        </div>
                        <div x-show="suggestions.sentiment?.recommendation" 
                             class="mt-3 bg-red-50 border-l-4 border-red-500 p-2 rounded-r-lg">
                            <p class="text-xs text-red-800 font-medium" x-text="suggestions.sentiment?.recommendation"></p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div x-show="!analyzing && !suggestions.knowledge_articles?.length && !suggestions.similar_tickets?.length" 
                     class="text-center py-8">
                    <x-icon name="lightbulb" size="xl" class="mx-auto text-gray-300 mb-3" />
                    <p class="text-sm text-gray-500">Commencez à écrire pour obtenir des suggestions</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function smartTicketCreation() {
    return {
        form: {
            title: '',
            description: '',
            category_id: '',
            priority_id: ''
        },
        analyzing: false,
        suggestions: {},

        async analyzeTicket() {
            if (!this.form.title || !this.form.description) return;
            if (this.form.title.length < 5 || this.form.description.length < 10) return;

            this.analyzing = true;

            try {
                const response = await fetch('/tickets/intelligence/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        title: this.form.title,
                        description: this.form.description
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    this.suggestions = result.suggestions;
                    
                    // Auto-appliquer les suggestions si pas encore sélectionné
                    if (!this.form.priority_id && result.suggestions.priority) {
                        // Trouver l'ID de priorité correspondant
                        const priorityElement = document.querySelector(`select[name="priority_id"] option[value]`);
                        // TODO: Mapper correctement les priorités
                    }
                    
                    if (!this.form.category_id && result.suggestions.category) {
                        this.form.category_id = result.suggestions.category.id;
                    }
                }
            } catch (error) {
                console.error('Erreur analyse:', error);
            } finally {
                this.analyzing = false;
            }
        },

        async submitTicket() {
            // Soumettre le formulaire normalement
            const formData = new FormData();
            formData.append('title', this.form.title);
            formData.append('description', this.form.description);
            if (this.form.category_id) formData.append('category_id', this.form.category_id);
            if (this.form.priority_id) formData.append('priority_id', this.form.priority_id);

            try {
                const response = await fetch('{{ route("tickets.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                if (response.ok) {
                    window.location.href = '{{ route("tickets.index") }}';
                }
            } catch (error) {
                console.error('Erreur création:', error);
                alert('Erreur lors de la création du ticket');
            }
        }
    };
}
</script>
@endpush
@endsection
