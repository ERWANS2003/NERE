@extends('layouts.portal')

@section('titre', 'Modifier le SLA')
@section('sous-titre', 'Mettre à jour l\'accord de niveau de service')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Modifier {{ $sla->nom }}</h1>
        <a href="{{ route('sla.show', $sla) }}" class="text-gray-400 hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('sla.update', $sla) }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <!-- Nom -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">
                Nom <span class="text-red-400">*</span>
            </label>
            <input type="text" name="nom" value="{{ old('nom', $sla->nom) }}"
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('nom') border-red-500 @enderror">
            @error('nom')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Priorité -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">
                Priorité <span class="text-red-400">*</span>
            </label>
            <select name="ticket_priority_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('ticket_priority_id') border-red-500 @enderror">
                @foreach($priorities as $priority)
                    <option value="{{ $priority->id }}" @selected($sla->ticket_priority_id == $priority->id)>
                        {{ $priority->nom }} (Niveau: {{ $priority->niveau }})
                    </option>
                @endforeach
            </select>
            @error('ticket_priority_id')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Site -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">Site</label>
            <select name="site_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                <option value="">-- Global --</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" @selected($sla->site_id == $site->id)>{{ $site->nom }}</option>
                @endforeach
            </select>
            @error('site_id')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Temps de Réponse & Résolution -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Temps de Réponse (heures) <span class="text-red-400">*</span>
                </label>
                <input type="number" name="temps_reponse_heures" value="{{ old('temps_reponse_heures', $sla->temps_reponse_heures) }}" step="0.25" min="0.25" max="168"
                    class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('temps_reponse_heures') border-red-500 @enderror">
                @error('temps_reponse_heures')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Temps de Résolution (heures) <span class="text-red-400">*</span>
                </label>
                <input type="number" name="temps_resolution_heures" value="{{ old('temps_resolution_heures', $sla->temps_resolution_heures) }}" step="0.25" min="0.25" max="720"
                    class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('temps_resolution_heures') border-red-500 @enderror">
                @error('temps_resolution_heures')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actif -->
        <div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="actif" value="1" @checked(old('actif', $sla->actif)) class="w-4 h-4 rounded">
                <span class="text-sm text-white">Activer ce SLA</span>
            </label>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4">
            <a href="{{ route('sla.show', $sla) }}" class="px-6 py-2 border border-dark-700 rounded-lg text-white hover:bg-dark-800 transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection
