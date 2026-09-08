@extends('layouts.app-new')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-container" x-data="dashboardCustomizer()">
    <!-- Header avec options de personnalisation -->
    <div class="dashboard-header">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tableau de Bord</h1>
                <p class="text-sm text-gray-600 mt-1">Personnalisez votre vue en glissant-déposant les widgets</p>
            </div>

            <div class="flex gap-3">
                <!-- Toggle mode édition -->
                <button 
                    @click="editMode = !editMode"
                    :class="editMode ? 'btn-primary' : 'btn-secondary'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg transition-all">
                    <x-icon :name="editMode ? 'check' : 'edit'" size="sm" />
                    <span x-text="editMode ? 'Terminer' : 'Personnaliser'"></span>
                </button>

                <!-- Menu actions -->
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        class="btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-lg">
                        <x-icon name="dots-vertical" size="sm" />
                    </button>

                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50">
                        <button @click="addWidget(); open = false" class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-2">
                            <x-icon name="plus" size="sm" />
                            <span>Ajouter widget</span>
                        </button>
                        <button @click="resetLayout(); open = false" class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-2">
                            <x-icon name="refresh" size="sm" />
                            <span>Réinitialiser</span>
                        </button>
                        <button @click="saveLayout(); open = false" class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-2">
                            <x-icon name="save" size="sm" />
                            <span>Sauvegarder</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre d'outils mode édition -->
        <div x-show="editMode" 
             x-transition
             class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-amber-800">
                    <x-icon name="info" size="sm" />
                    <span class="font-medium">Mode personnalisation activé</span>
                    <span class="text-sm">- Glissez-déposez les widgets pour réorganiser votre dashboard</span>
                </div>
                <button @click="showWidgetLibrary = true" class="btn-primary-sm">
                    <x-icon name="plus" size="sm" />
                    Ajouter un widget
                </button>
            </div>
        </div>
    </div>

    <!-- Grid des widgets -->
    <div class="dashboard-grid" 
         :class="editMode ? 'edit-mode' : ''"
         id="widget-grid">
        
        <template x-for="(item, index) in layout.grid" :key="index">
            <div 
                class="widget-container"
                :style="`grid-column: span ${item.position.w}; grid-row: span ${item.position.h};`"
                :data-widget="item.widget">
                
                <!-- Widget Header -->
                <div class="widget-header">
                    <div class="flex items-center gap-2">
                        <x-icon :name="getWidgetIcon(item.widget)" size="sm" />
                        <h3 class="font-semibold text-gray-900" x-text="getWidgetName(item.widget)"></h3>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button 
                            @click="refreshWidget(item.widget)" 
                            class="text-gray-400 hover:text-gray-600 transition">
                            <x-icon name="refresh" size="xs" />
                        </button>
                        <button 
                            x-show="editMode"
                            @click="removeWidget(index)" 
                            class="text-red-400 hover:text-red-600 transition">
                            <x-icon name="trash" size="xs" />
                        </button>
                    </div>
                </div>

                <!-- Widget Content -->
                <div class="widget-content" x-html="getWidgetContent(item.widget)">
                    <!-- Le contenu est chargé dynamiquement -->
                </div>
            </div>
        </template>

        <!-- Empty state -->
        <div x-show="layout.grid.length === 0" class="col-span-12 text-center py-20">
            <x-icon name="dashboard" size="xl" class="mx-auto text-gray-300 mb-4" />
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucun widget</h3>
            <p class="text-gray-500 mb-4">Commencez par ajouter des widgets à votre dashboard</p>
            <button @click="showWidgetLibrary = true" class="btn-primary">
                <x-icon name="plus" size="sm" />
                Ajouter des widgets
            </button>
        </div>
    </div>

    <!-- Modal Bibliothèque de Widgets -->
    <div x-show="showWidgetLibrary" 
         x-transition
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" @click="showWidgetLibrary = false"></div>
            
            <div class="relative bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-900">Bibliothèque de Widgets</h2>
                        <button @click="showWidgetLibrary = false" class="text-gray-400 hover:text-gray-600">
                            <x-icon name="close" size="md" />
                        </button>
                    </div>

                    <!-- Filtres catégories -->
                    <div class="flex gap-2 mt-4 overflow-x-auto">
                        <button 
                            @click="selectedCategory = null"
                            :class="selectedCategory === null ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-lg whitespace-nowrap transition">
                            Tous
                        </button>
                        @foreach($categories as $key => $category)
                        <button 
                            @click="selectedCategory = '{{ $key }}'"
                            :class="selectedCategory === '{{ $key }}' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-lg whitespace-nowrap transition flex items-center gap-2">
                            <x-icon name="{{ $category['icon'] }}" size="xs" />
                            {{ $category['name'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto" style="max-height: 60vh;">
                    <div class="grid grid-cols-2 gap-4">
                        <template x-for="(widget, key) in filteredWidgets" :key="key">
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-amber-500 transition cursor-pointer"
                                 @click="addWidgetToGrid(key)">
                                <div class="flex items-start gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                                        <x-icon :name="widget.icon" size="md" class="text-amber-600" />
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900" x-text="widget.name"></h3>
                                        <p class="text-sm text-gray-600 mt-1" x-text="widget.description"></p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                                    <span x-text="'Taille: ' + widget.default_size"></span>
                                    <button class="text-amber-600 font-medium hover:text-amber-700">Ajouter</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardCustomizer() {
    return {
        editMode: false,
        showWidgetLibrary: false,
        selectedCategory: null,
        layout: @json($layout),
        availableWidgets: @json($availableWidgets),
        categories: @json($categories),

        get filteredWidgets() {
            if (!this.selectedCategory) return this.availableWidgets;
            
            return Object.fromEntries(
                Object.entries(this.availableWidgets).filter(([key, widget]) => 
                    widget.category === this.selectedCategory
                )
            );
        },

        addWidgetToGrid(widgetKey) {
            const widget = this.availableWidgets[widgetKey];
            const size = this.getSizeValues(widget.default_size);
            
            this.layout.grid.push({
                widget: widgetKey,
                position: {
                    x: 0,
                    y: this.layout.grid.length * 2,
                    w: size.w,
                    h: size.h
                }
            });

            this.showWidgetLibrary = false;
            this.loadWidgetData(widgetKey);
        },

        getSizeValues(size) {
            const sizes = {
                small: { w: 3, h: 2 },
                medium: { w: 4, h: 3 },
                large: { w: 8, h: 4 },
            };
            return sizes[size] || sizes.medium;
        },

        removeWidget(index) {
            if (confirm('Supprimer ce widget ?')) {
                this.layout.grid.splice(index, 1);
            }
        },

        getWidgetName(widgetKey) {
            return this.availableWidgets[widgetKey]?.name || widgetKey;
        },

        getWidgetIcon(widgetKey) {
            return this.availableWidgets[widgetKey]?.icon || 'dashboard';
        },

        async loadWidgetData(widgetKey) {
            try {
                const response = await fetch(`/dashboard/widget/${widgetKey}/data`);
                const data = await response.json();
                // Mettre à jour le contenu du widget
            } catch (error) {
                console.error('Erreur chargement widget:', error);
            }
        },

        getWidgetContent(widgetKey) {
            // Retourne un loading par défaut
            return '<div class="flex items-center justify-center h-full"><div class="animate-spin text-amber-500">⏳</div></div>';
        },

        async saveLayout() {
            try {
                const response = await fetch('/dashboard/layout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ layout: this.layout.grid })
                });

                const result = await response.json();
                if (result.success) {
                    alert('✅ Dashboard sauvegardé !');
                }
            } catch (error) {
                alert('❌ Erreur lors de la sauvegarde');
            }
        },

        async resetLayout() {
            if (!confirm('Réinitialiser au layout par défaut ?')) return;

            try {
                const response = await fetch('/dashboard/layout/reset', { method: 'POST' });
                const result = await response.json();
                if (result.success) {
                    this.layout = result.layout;
                    location.reload();
                }
            } catch (error) {
                alert('❌ Erreur lors de la réinitialisation');
            }
        },

        refreshWidget(widgetKey) {
            this.loadWidgetData(widgetKey);
        }
    };
}
</script>
@endpush

@push('styles')
<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.5rem;
    min-height: 400px;
}

.widget-container {
    background: white;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.2s;
}

.edit-mode .widget-container {
    cursor: move;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.widget-container:hover {
    border-color: #daa520;
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbf8;
}

.widget-content {
    flex: 1;
    padding: 1rem;
    overflow: auto;
}
</style>
@endpush
@endsection
