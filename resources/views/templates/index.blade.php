@extends('layouts.portal')

@section('titre', 'Modèles de Tickets')
@section('sous-titre', 'Créez des tickets rapidement à partir de modèles')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Modèles de Tickets</h1>
            <p class="text-sm text-gray-400 mt-1">{{ $templates->total() }} modèle{{ $templates->total() !== 1 ? 's' : '' }} disponible{{ $templates->total() !== 1 ? 's' : '' }}</p>
        </div>
        <a href="{{ route('templates.create') }}" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
            + Nouveau Modèle
        </a>
    </div>

    <!-- Templates Grid -->
    @if($templates->isEmpty())
        <div class="bg-dark-800 border border-dark-700 rounded-xl p-12 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>Aucun modèle créé. Créez-en un pour accélérer la création de tickets.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($templates as $template)
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-6 hover:border-primary-500 transition">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white">{{ $template->name }}</h3>
                            <p class="text-xs text-gray-500 mt-1">Par {{ $template->creator?->name }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    @if($template->description)
                        <p class="text-sm text-gray-400 mb-3">{{ Str::limit($template->description, 100) }}</p>
                    @endif

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($template->category)
                            <span class="inline-block px-2 py-1 text-xs bg-blue-900/30 text-blue-400 rounded">
                                {{ $template->category->nom }}
                            </span>
                        @endif
                        @if($template->priorite)
                            <span class="inline-block px-2 py-1 text-xs rounded
                                @if($template->priorite->niveau >= 3) bg-red-900/30 text-red-400
                                @elseif($template->priorite->niveau >= 2) bg-yellow-900/30 text-yellow-400
                                @else bg-green-900/30 text-green-400 @endif">
                                {{ $template->priorite->nom }}
                            </span>
                        @endif
                    </div>

                    <!-- Template Preview -->
                    <div class="bg-dark-700 rounded-lg p-3 mb-4">
                        <p class="text-xs text-gray-400">Titre:</p>
                        <p class="text-sm text-white line-clamp-2">{{ $template->titre_template }}</p>
                        <p class="text-xs text-gray-400 mt-2">Description:</p>
                        <p class="text-xs text-gray-300 line-clamp-2">{{ $template->description_template }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('templates.use', $template) }}" class="flex-1 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition text-center">
                            ➜ Utiliser
                        </a>
                        <a href="{{ route('templates.show', $template) }}" class="px-3 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-700 transition text-sm">
                            Voir
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            {{ $templates->links('pagination::simple-bootstrap-4') }}
        </div>
    @endif
</div>
@endsection
