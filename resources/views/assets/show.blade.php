@extends('layouts.portal')

@section('titre', 'Détails de l\'Actif')
@section('sous-titre', 'Informations de l\'équipement')

@section('contenu')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $asset->nom }}</h1>
            <p class="text-sm text-gray-400 mt-1">Code: <span class="font-mono">{{ $asset->code_inventaire }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('assets.edit', $asset) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Modifier
            </a>
            <a href="{{ route('assets.index') }}" class="px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-800 transition">
                Retour
            </a>
        </div>
    </div>

    <!-- Main Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Informations Générales</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-400">Type</p>
                        <p class="text-white font-medium">{{ $asset->type?->nom ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Statut</p>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium mt-1
                            @if($asset->statut === 'En service') bg-green-900/30 text-green-400
                            @elseif($asset->statut === 'En stock') bg-blue-900/30 text-blue-400
                            @elseif($asset->statut === 'En maintenance') bg-yellow-900/30 text-yellow-400
                            @elseif($asset->statut === 'Hors service') bg-red-900/30 text-red-400
                            @else bg-gray-900/30 text-gray-400 @endif">
                            {{ $asset->statut }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Marque</p>
                        <p class="text-white font-medium">{{ $asset->marque ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Modèle</p>
                        <p class="text-white font-medium">{{ $asset->modele ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Numéro de Série</p>
                        <p class="text-white font-mono text-sm">{{ $asset->numero_serie ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Coût (€)</p>
                        <p class="text-white font-medium">{{ $asset->cout ? number_format($asset->cout, 2, ',', ' ') : 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Dates -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Dates</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-400">Date d'Acquisition</p>
                        <p class="text-white font-medium">{{ $asset->date_acquisition?->format('d/m/Y') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Fin de Garantie</p>
                        @if($asset->date_garantie_fin)
                            <p class="text-white font-medium">{{ $asset->date_garantie_fin->format('d/m/Y') }}</p>
                            @if($asset->date_garantie_fin->isPast())
                                <p class="text-red-400 text-sm mt-1">⚠️ Garantie expirée</p>
                            @elseif($asset->date_garantie_fin->diffInDays(now()) < 30)
                                <p class="text-yellow-400 text-sm mt-1">⚠️ Expire bientôt</p>
                            @else
                                <p class="text-green-400 text-sm mt-1">✓ Sous garantie</p>
                            @endif
                        @else
                            <p class="text-white font-medium">N/A</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Créé le</p>
                        <p class="text-white font-medium">{{ $asset->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Modifié le</p>
                        <p class="text-white font-medium">{{ $asset->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Assignment -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Attribution</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-400">Utilisateur</p>
                        <p class="text-white font-medium">{{ $asset->utilisateur?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Département</p>
                        <p class="text-white font-medium">{{ $asset->departement?->nom ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Site</p>
                        <p class="text-white font-medium">{{ $asset->site?->nom ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Quick Stats -->
        <div class="space-y-4">
            <!-- Description -->
            @if($asset->description)
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                    <h2 class="text-lg font-semibold text-white mb-3">Description</h2>
                    <p class="text-gray-300 text-sm">{{ $asset->description }}</p>
                </div>
            @endif

            <!-- Tickets -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Tickets Associés</h2>
                @if($asset->tickets->count() > 0)
                    <div class="space-y-2">
                        @foreach($asset->tickets as $ticket)
                            <a href="{{ route('tickets.show', $ticket) }}" class="block p-2 bg-dark-700 hover:bg-dark-600 rounded transition">
                                <p class="text-sm text-primary-400 font-mono">{{ $ticket->reference }}</p>
                                <p class="text-xs text-gray-400">{{ Str::limit($ticket->titre, 40) }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-sm">Aucun ticket associé</p>
                @endif
            </div>

            <!-- Actions -->
            <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Actions</h2>
                <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="space-y-2" onsubmit="return confirm('Êtes-vous sûr?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                        Supprimer cet Actif
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
