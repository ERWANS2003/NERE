@extends('layouts.portal')

@section('titre', $incident->title)
@section('sous-titre', 'Détail de l\'incident #' . $incident->incident_number)

@section('contenu')
<div class="px-6 py-4 space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('safety.index') }}" class="inline-flex items-center gap-2 text-primary-400 hover:text-primary-300 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            <span>Retour aux incidents</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Incident Header -->
            <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $incident->title }}</h1>
                        <p class="text-primary-400 font-mono text-sm mt-1">{{ $incident->incident_number }}</p>
                    </div>
                    <div class="flex gap-2">
                        @php
                            $statusColors = [
                                'open' => 'blue',
                                'under_investigation' => 'yellow',
                                'resolved' => 'green',
                                'closed' => 'gray',
                            ];
                            $color = $statusColors[$incident->status] ?? 'gray';
                        @endphp
                        <span class="inline-flex px-4 py-2 rounded-lg text-sm font-semibold bg-{{ $color }}-600/20 text-{{ $color }}-300">
                            {{ ucfirst(str_replace('_', ' ', $incident->status)) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-dark-700">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Sévérité</p>
                        @php
                            $severityColors = ['low' => 'blue', 'medium' => 'yellow', 'high' => 'orange', 'critical' => 'red'];
                            $color = $severityColors[$incident->severity] ?? 'gray';
                        @endphp
                        <p class="text-white mt-1">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-{{ $color }}-600/20 text-{{ $color }}-300">
                                {{ ucfirst($incident->severity) }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Type</p>
                        <p class="text-white mt-1">{{ ucfirst(str_replace('_', ' ', $incident->incident_type)) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Zone</p>
                        <p class="text-white mt-1">{{ $incident->operationalZone?->nom ?? 'Non assignée' }}</p>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Description</h3>
                <p class="text-gray-300 leading-relaxed whitespace-pre-wrap">{{ $incident->description }}</p>
                
                <div class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-dark-700">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Lieu</p>
                        <p class="text-white mt-1">{{ $incident->location }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Date & Heure</p>
                        <p class="text-white mt-1">{{ $incident->reported_at->format('d M Y à H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Reporting & Investigation -->
            <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Signalement & Investigation</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Signalé par</p>
                        <p class="text-white mt-1">{{ $incident->reporter?->name ?? 'Utilisateur supprimé' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Investigateur</p>
                        <p class="text-white mt-1">{{ $incident->investigator?->name ?? 'Non assigné' }}</p>
                    </div>

                    @if($incident->resolved_at)
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Résolu le</p>
                        <p class="text-white mt-1">{{ $incident->resolved_at->format('d M Y à H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Investigation Details -->
            @if($incident->status === 'under_investigation' || $incident->status === 'resolved' || $incident->status === 'closed')
            <div class="bg-dark-800 rounded-xl border border-dark-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Détails d'Investigation</h3>
                
                <div class="space-y-4">
                    @if($incident->root_cause)
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Cause racine</p>
                        <p class="text-white mt-1">{{ $incident->root_cause }}</p>
                    </div>
                    @endif

                    @if($incident->corrective_actions)
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Actions correctives</p>
                        <p class="text-white mt-1 whitespace-pre-wrap">{{ $incident->corrective_actions }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex gap-3">
                <a href="{{ route('safety.edit', $incident) }}" 
                   class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Éditer
                </a>
                <form method="POST" action="{{ route('safety.destroy', $incident) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <div class="bg-dark-800 rounded-xl border border-dark-700 p-6 sticky top-6">
                <h3 class="text-lg font-semibold text-white mb-4">Actions</h3>
                
                <div class="space-y-3">
                    @if($incident->status === 'open')
                    <form method="POST" action="{{ route('safety.assignInvestigation', $incident) }}" class="space-y-2">
                        @csrf
                        <label class="block text-sm font-medium text-gray-300">Assigner investigation</label>
                        <select name="investigated_by" required class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white text-sm">
                            <option value="">-- Sélectionner --</option>
                            @foreach(\App\Models\User::orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                            Assigner & Commencer
                        </button>
                    </form>
                    @elseif($incident->status === 'under_investigation')
                    <form method="POST" action="{{ route('safety.resolve', $incident) }}" class="space-y-2">
                        @csrf
                        <h4 class="text-sm font-semibold text-white">Résoudre l'incident</h4>
                        <textarea name="root_cause" placeholder="Cause racine..." required class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white text-sm"></textarea>
                        <textarea name="corrective_actions" placeholder="Actions correctives..." required class="w-full px-3 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white text-sm"></textarea>
                        <button type="submit" class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                            Marquer Résolu
                        </button>
                    </form>
                    @endif

                    <div class="pt-4 border-t border-dark-700">
                        <h4 class="text-sm font-semibold text-white mb-3">Informations</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Créé</span>
                                <span class="text-white">{{ $incident->created_at->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Modifié</span>
                                <span class="text-white">{{ $incident->updated_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
