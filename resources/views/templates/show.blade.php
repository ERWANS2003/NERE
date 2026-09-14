@extends('layouts.portal')

@section('titre', 'Modèle: ' . $template->name)
@section('sous-titre', 'Détails et utilisation du modèle')

@section('contenu')
<div class="space-y-6 max-w-2xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">{{ $template->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('templates.edit', $template) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Modifier
            </a>
            <a href="{{ route('templates.index') }}" class="px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-800 transition">
                Retour
            </a>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-6 space-y-4">
        @if($template->description)
            <div>
                <p class="text-sm text-gray-400">Description</p>
                <p class="text-white mt-1">{{ $template->description }}</p>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            @if($template->category)
                <div>
                    <p class="text-sm text-gray-400">Catégorie</p>
                    <p class="text-white mt-1">{{ $template->category->nom }}</p>
                </div>
            @endif
            @if($template->priorite)
                <div>
                    <p class="text-sm text-gray-400">Priorité</p>
                    <p class="text-white mt-1">{{ $template->priorite->nom }}</p>
                </div>
            @endif
        </div>

        <div>
            <p class="text-sm text-gray-400">Créé par</p>
            <p class="text-white mt-1">{{ $template->creator?->name }} le {{ $template->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <!-- Template Preview -->
    <div class="space-y-4">
        <h2 class="text-lg font-semibold text-white">Aperçu du Modèle</h2>
        
        <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
            <p class="text-sm text-gray-400 mb-2">Titre:</p>
            <div class="bg-dark-700 p-3 rounded-lg mb-4">
                <p class="text-white">{{ $template->titre_template }}</p>
            </div>

            <p class="text-sm text-gray-400 mb-2">Description:</p>
            <div class="bg-dark-700 p-3 rounded-lg">
                <p class="text-white whitespace-pre-wrap">{{ $template->description_template }}</p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2 pt-4 border-t border-dark-700">
        <a href="{{ route('templates.use', $template) }}" class="flex-1 px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition text-center">
            ➜ Créer un Ticket avec ce Modèle
        </a>
        <form method="POST" action="{{ route('templates.destroy', $template) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                Supprimer
            </button>
        </form>
    </div>
</div>
@endsection
