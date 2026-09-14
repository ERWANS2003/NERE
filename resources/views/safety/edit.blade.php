@extends('layouts.portal')

@section('titre', 'Éditer Incident Sécurité')
@section('sous-titre', $incident->incident_number)

@section('contenu')
<div class="px-6 py-4">
    <div class="max-w-2xl">
        <a href="{{ route('safety.show', $incident) }}" class="inline-flex items-center gap-2 text-primary-400 hover:text-primary-300 transition mb-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            <span>Retour</span>
        </a>

        <form method="POST" action="{{ route('safety.update', $incident) }}" class="bg-dark-800 rounded-xl border border-dark-700 p-8 space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-300 mb-2">
                    Titre <span class="text-red-400">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title', $incident->title) }}" required
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
                    Description <span class="text-red-400">*</span>
                </label>
                <textarea id="description" name="description" rows="5" required
                          class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition resize-none">{{ old('description', $incident->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Severity -->
                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-300 mb-2">Sévérité</label>
                    <select id="severity" name="severity" required class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="low" @selected(old('severity', $incident->severity) == 'low')>Basse</option>
                        <option value="medium" @selected(old('severity', $incident->severity) == 'medium')>Moyenne</option>
                        <option value="high" @selected(old('severity', $incident->severity) == 'high')>Élevée</option>
                        <option value="critical" @selected(old('severity', $incident->severity) == 'critical')>Critique</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-300 mb-2">Statut</label>
                    <select id="status" name="status" required class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="open" @selected(old('status', $incident->status) == 'open')>Ouvert</option>
                        <option value="under_investigation" @selected(old('status', $incident->status) == 'under_investigation')>En investigation</option>
                        <option value="resolved" @selected(old('status', $incident->status) == 'resolved')>Résolu</option>
                        <option value="closed" @selected(old('status', $incident->status) == 'closed')>Fermé</option>
                    </select>
                </div>
            </div>

            <!-- Location -->
            <div>
                <label for="location" class="block text-sm font-medium text-gray-300 mb-2">Lieu</label>
                <input type="text" id="location" name="location" value="{{ old('location', $incident->location) }}" required
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Zone -->
                <div>
                    <label for="operational_zone_id" class="block text-sm font-medium text-gray-300 mb-2">Zone Opérationnelle</label>
                    <select id="operational_zone_id" name="operational_zone_id" class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="">-- Sélectionner --</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('operational_zone_id', $incident->operational_zone_id) == $zone->id)>
                                {{ $zone->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Investigator -->
                <div>
                    <label for="investigated_by" class="block text-sm font-medium text-gray-300 mb-2">Investigateur</label>
                    <select id="investigated_by" name="investigated_by" class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="">-- Sélectionner --</option>
                        @foreach(\App\Models\User::orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" @selected(old('investigated_by', $incident->investigated_by) == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Root Cause -->
            <div>
                <label for="root_cause" class="block text-sm font-medium text-gray-300 mb-2">Cause Racine</label>
                <textarea id="root_cause" name="root_cause" rows="3"
                          class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition resize-none">{{ old('root_cause', $incident->root_cause) }}</textarea>
            </div>

            <!-- Corrective Actions -->
            <div>
                <label for="corrective_actions" class="block text-sm font-medium text-gray-300 mb-2">Actions Correctives</label>
                <textarea id="corrective_actions" name="corrective_actions" rows="3"
                          class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition resize-none">{{ old('corrective_actions', $incident->corrective_actions) }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                    Mettre à jour
                </button>
                <a href="{{ route('safety.show', $incident) }}" class="px-6 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg font-medium transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
