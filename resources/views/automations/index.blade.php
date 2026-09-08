@extends('layouts.app-new')

@section('title', 'Automations Workflow')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Automations Workflow</h1>
            <p class="text-gray-600 mt-2">Automatisez vos processus métier sans code</p>
        </div>
        <a href="{{ route('automations.create') }}" class="btn-primary px-6 py-3 rounded-lg shadow-md hover:shadow-lg transition">
            <x-icon name="plus" size="sm" />
            Créer une Automation
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600">Total</div>
                    <div class="text-3xl font-bold text-gray-900 mt-1">{{ $automations->total() }}</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <x-icon name="zap" size="lg" class="text-blue-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600">Actives</div>
                    <div class="text-3xl font-bold text-green-600 mt-1">
                        {{ $automations->where('is_active', true)->count() }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <x-icon name="check-circle" size="lg" class="text-green-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600">Inactives</div>
                    <div class="text-3xl font-bold text-gray-600 mt-1">
                        {{ $automations->where('is_active', false)->count() }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                    <x-icon name="pause" size="lg" class="text-gray-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600">Exécutions</div>
                    <div class="text-3xl font-bold text-accent-600 mt-1">
                        {{ $automations->sum('execution_count') }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-accent-100 flex items-center justify-center">
                    <x-icon name="activity" size="lg" class="text-accent-600" />
                </div>
            </div>
        </div>
    </div>

    <!-- Automations List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        @if($automations->isEmpty())
        <div class="p-12 text-center">
            <x-icon name="zap" size="xl" class="mx-auto text-gray-300 mb-4" />
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Aucune Automation</h3>
            <p class="text-gray-500 mb-6">Créez votre première automation pour automatiser vos workflows</p>
            <a href="{{ route('automations.create') }}" class="btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-lg">
                <x-icon name="plus" size="sm" />
                Créer une Automation
            </a>
        </div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($automations as $automation)
            <div class="p-6 hover:bg-gray-50 transition">
                <div class="flex items-start justify-between gap-4">
                    <!-- Content -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-gray-900">{{ $automation->name }}</h3>
                            
                            <!-- Status Badge -->
                            @if($automation->is_active)
                            <span class="px-2.5 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold flex items-center gap-1">
                                <x-icon name="check-circle" size="xs" />
                                Active
                            </span>
                            @else
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold flex items-center gap-1">
                                <x-icon name="pause" size="xs" />
                                Inactive
                            </span>
                            @endif
                        </div>

                        @if($automation->description)
                        <p class="text-gray-600 mb-3">{{ $automation->description }}</p>
                        @endif

                        <!-- Meta Info -->
                        <div class="flex items-center gap-6 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                <x-icon name="lightning" size="xs" />
                                {{ $automation->trigger_event }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="filter" size="xs" />
                                {{ count($automation->conditions) }} condition(s)
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="zap" size="xs" />
                                {{ count($automation->actions) }} action(s)
                            </span>
                            <span class="flex items-center gap-1">
                                <x-icon name="activity" size="xs" />
                                {{ $automation->execution_count }} exécution(s)
                            </span>
                        </div>

                        @if($automation->last_executed_at)
                        <div class="mt-2 text-xs text-gray-500">
                            Dernière exécution: {{ $automation->last_executed_at->diffForHumans() }}
                        </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <!-- Toggle -->
                        <form action="{{ route('automations.toggle', $automation) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="p-2 rounded-lg transition {{ $automation->is_active ? 'text-gray-600 hover:bg-gray-100' : 'text-green-600 hover:bg-green-50' }}"
                                    title="{{ $automation->is_active ? 'Désactiver' : 'Activer' }}">
                                <x-icon :name="$automation->is_active ? 'pause' : 'play'" size="sm" />
                            </button>
                        </form>

                        <!-- View -->
                        <a href="{{ route('automations.show', $automation) }}" 
                           class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                           title="Voir détails">
                            <x-icon name="eye" size="sm" />
                        </a>

                        <!-- Edit -->
                        <a href="{{ route('automations.edit', $automation) }}" 
                           class="p-2 text-accent-600 hover:bg-accent-50 rounded-lg transition"
                           title="Modifier">
                            <x-icon name="edit" size="sm" />
                        </a>

                        <!-- Delete -->
                        <form action="{{ route('automations.destroy', $automation) }}" 
                              method="POST" 
                              class="inline"
                              onsubmit="return confirm('Supprimer cette automation ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                    title="Supprimer">
                                <x-icon name="trash" size="sm" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($automations->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $automations->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
