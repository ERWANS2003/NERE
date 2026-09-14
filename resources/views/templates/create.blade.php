@extends('layouts.portal')

@section('titre', 'Créer un Modèle')
@section('sous-titre', 'Créez un nouveau modèle de ticket réutilisable')

@section('contenu')
<div class="space-y-6 max-w-2xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Nouveau Modèle</h1>
        <a href="{{ route('templates.index') }}" class="text-gray-400 hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('templates.store') }}" class="space-y-6">
        @csrf

        <!-- Nom -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">
                Nom du Modèle <span class="text-red-400">*</span>
            </label>
            <input type="text" name="name" value="{{ old('name') }}" 
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('name') border-red-500 @enderror"
                placeholder="Ex: Demande d'accès réseau">
            @error('name')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">Description</label>
            <textarea name="description" rows="2" 
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition"
                placeholder="Description optionnelle du modèle">{{ old('description') }}</textarea>
            @error('description')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Category & Priority -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-white mb-2">Catégorie</label>
                <select name="ticket_category_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                    <option value="">-- Sélectionner --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('ticket_category_id') == $category->id)>{{ $category->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-white mb-2">Priorité</label>
                <select name="priorite_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                    <option value="">-- Sélectionner --</option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority->id }}" @selected(old('priorite_id') == $priority->id)>{{ $priority->nom }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Titre Template -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">
                Titre du Modèle <span class="text-red-400">*</span>
            </label>
            <input type="text" name="titre_template" value="{{ old('titre_template') }}"
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('titre_template') border-red-500 @enderror"
                placeholder="Ex: Demande d'accès [Application] pour [Utilisateur]">
            <p class="text-xs text-gray-400 mt-1">Ce texte sera pré-rempli lors de la création d'un ticket</p>
            @error('titre_template')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description Template -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">
                Description du Modèle <span class="text-red-400">*</span>
            </label>
            <textarea name="description_template" rows="10"
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('description_template') border-red-500 @enderror"
                placeholder="Entrez le texte pré-rempli pour la description...">{{ old('description_template') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Exemple:
            <pre class="text-xs bg-dark-700 p-2 rounded mt-2 text-gray-300">Demandeur: [Nom]
Application: [Nom de l'application]
Type d'accès: [Lecture/Écriture/Admin]
Justification: [Motif de la demande]</pre>
            @error('description_template')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4 border-t border-dark-700">
            <a href="{{ route('templates.index') }}" class="px-6 py-2 border border-dark-700 rounded-lg text-white hover:bg-dark-800 transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                Créer le Modèle
            </button>
        </div>
    </form>
</div>
@endsection
