@extends('layouts.portal')

@section('titre', 'Modifier le Modèle')
@section('sous-titre', 'Mettre à jour ' . $template->name)

@section('contenu')
<div class="space-y-6 max-w-2xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Modifier {{ $template->name }}</h1>
        <a href="{{ route('templates.show', $template) }}" class="text-gray-400 hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('templates.update', $template) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Nom -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">Nom du Modèle <span class="text-red-400">*</span></label>
            <input type="text" name="name" value="{{ old('name', $template->name) }}" 
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('name') border-red-500 @enderror">
            @error('name')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">Description</label>
            <textarea name="description" rows="2" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">{{ old('description', $template->description) }}</textarea>
        </div>

        <!-- Category & Priority -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-white mb-2">Catégorie</label>
                <select name="ticket_category_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                    <option value="">-- Sélectionner --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected($template->ticket_category_id == $category->id)>{{ $category->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-white mb-2">Priorité</label>
                <select name="priorite_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                    <option value="">-- Sélectionner --</option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority->id }}" @selected($template->priorite_id == $priority->id)>{{ $priority->nom }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Titre Template -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">Titre du Modèle <span class="text-red-400">*</span></label>
            <input type="text" name="titre_template" value="{{ old('titre_template', $template->titre_template) }}"
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('titre_template') border-red-500 @enderror">
            @error('titre_template')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description Template -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">Description du Modèle <span class="text-red-400">*</span></label>
            <textarea name="description_template" rows="10"
                class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('description_template') border-red-500 @enderror">{{ old('description_template', $template->description_template) }}</textarea>
            @error('description_template')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4 border-t border-dark-700">
            <a href="{{ route('templates.show', $template) }}" class="px-6 py-2 border border-dark-700 rounded-lg text-white hover:bg-dark-800 transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection
