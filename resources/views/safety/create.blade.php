@extends('layouts.portal')

@section('titre', 'Signaler un Incident Sécurité')
@section('sous-titre', 'Créer un nouvel incident de sécurité')

@section('contenu')
<div class="px-6 py-4">
    <div class="max-w-2xl">
        <a href="{{ route('safety.index') }}" class="inline-flex items-center gap-2 text-primary-400 hover:text-primary-300 transition mb-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            <span>Retour à la liste</span>
        </a>

        <form method="POST" action="{{ route('safety.store') }}" class="bg-dark-800 rounded-xl border border-dark-700 p-8 space-y-6">
            @csrf

            <!-- Incident Number -->
            <div>
                <label for="incident_number" class="block text-sm font-medium text-gray-300 mb-2">
                    Numéro d'incident <span class="text-red-400">*</span>
                </label>
                <input type="text" id="incident_number" name="incident_number" value="{{ old('incident_number') }}" required
                       placeholder="INC-2026-001"
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition @error('incident_number') border-red-500 @enderror">
                @error('incident_number')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-300 mb-2">
                    Titre <span class="text-red-400">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                       placeholder="Résumé de l'incident"
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition @error('title') border-red-500 @enderror">
                @error('title')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
                    Description <span class="text-red-400">*</span>
                </label>
                <textarea id="description" name="description" rows="5" required
                          placeholder="Détails de l'incident, contexte, circonstances..."
                          class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition resize-none @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Severity -->
                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-300 mb-2">
                        Sévérité <span class="text-red-400">*</span>
                    </label>
                    <select id="severity" name="severity" required
                            class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition @error('severity') border-red-500 @enderror">
                        <option value="">-- Sélectionner --</option>
                        <option value="low" @selected(old('severity') == 'low')>Basse</option>
                        <option value="medium" @selected(old('severity') == 'medium')>Moyenne</option>
                        <option value="high" @selected(old('severity') == 'high')>Élevée</option>
                        <option value="critical" @selected(old('severity') == 'critical')>Critique</option>
                    </select>
                    @error('severity')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="incident_type" class="block text-sm font-medium text-gray-300 mb-2">
                        Type <span class="text-red-400">*</span>
                    </label>
                    <select id="incident_type" name="incident_type" required
                            class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition @error('incident_type') border-red-500 @enderror">
                        <option value="">-- Sélectionner --</option>
                        <option value="near_miss" @selected(old('incident_type') == 'near_miss')>Presque accident</option>
                        <option value="injury" @selected(old('incident_type') == 'injury')>Blessure</option>
                        <option value="equipment_damage" @selected(old('incident_type') == 'equipment_damage')>Dégâts équipement</option>
                        <option value="environmental" @selected(old('incident_type') == 'environmental')>Incident environnemental</option>
                        <option value="security" @selected(old('incident_type') == 'security')>Incident sécurité</option>
                        <option value="other" @selected(old('incident_type') == 'other')>Autre</option>
                    </select>
                    @error('incident_type')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Location -->
            <div>
                <label for="location" class="block text-sm font-medium text-gray-300 mb-2">
                    Lieu <span class="text-red-400">*</span>
                </label>
                <input type="text" id="location" name="location" value="{{ old('location') }}" required
                       placeholder="Localisation précise de l'incident"
                       class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary-500 transition @error('location') border-red-500 @enderror">
                @error('location')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Operational Zone -->
                <div>
                    <label for="operational_zone_id" class="block text-sm font-medium text-gray-300 mb-2">
                        Zone opérationnelle
                    </label>
                    <select id="operational_zone_id" name="operational_zone_id"
                            class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition">
                        <option value="">-- Sélectionner une zone --</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('operational_zone_id') == $zone->id)>
                                {{ $zone->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date & Time -->
                <div>
                    <label for="reported_at" class="block text-sm font-medium text-gray-300 mb-2">
                        Date et heure <span class="text-red-400">*</span>
                    </label>
                    <input type="datetime-local" id="reported_at" name="reported_at" value="{{ old('reported_at', now()->format('Y-m-d\TH:i')) }}" required
                           class="w-full px-4 py-2 bg-dark-900 border border-dark-700 rounded-lg text-white focus:outline-none focus:border-primary-500 transition @error('reported_at') border-red-500 @enderror">
                    @error('reported_at')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4">
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Signaler l'incident
                </button>
                <a href="{{ route('safety.index') }}" class="px-6 py-2 bg-dark-700 hover:bg-dark-600 text-white rounded-lg font-medium transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
