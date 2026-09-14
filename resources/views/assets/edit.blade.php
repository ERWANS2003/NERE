@extends('layouts.portal')

@section('titre', 'Modifier l\'Actif')
@section('sous-titre', 'Mettre à jour les informations')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Modifier {{ $asset->nom }}</h1>
            <p class="text-sm text-gray-400 mt-1">Code: <span class="font-mono">{{ $asset->code_inventaire }}</span></p>
        </div>
        <a href="{{ route('assets.show', $asset) }}" class="text-gray-400 hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('assets.update', $asset) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Type d'Actif -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Type d'Actif <span class="text-red-400">*</span>
                </label>
                <select name="asset_type_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('asset_type_id') border-red-500 @enderror">
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" @selected($asset->asset_type_id == $type->id)>{{ $type->nom }}</option>
                    @endforeach
                </select>
                @error('asset_type_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Statut -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Statut <span class="text-red-400">*</span>
                </label>
                <select name="statut" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('statut') border-red-500 @enderror">
                    <option value="En stock" @selected($asset->statut == 'En stock')>En stock</option>
                    <option value="En service" @selected($asset->statut == 'En service')>En service</option>
                    <option value="En maintenance" @selected($asset->statut == 'En maintenance')>En maintenance</option>
                    <option value="Hors service" @selected($asset->statut == 'Hors service')>Hors service</option>
                    <option value="Réformé" @selected($asset->statut == 'Réformé')>Réformé</option>
                </select>
                @error('statut')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nom -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Nom <span class="text-red-400">*</span>
                </label>
                <input type="text" name="nom" value="{{ old('nom', $asset->nom) }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('nom') border-red-500 @enderror">
                @error('nom')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Code Inventaire -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Code Inventaire <span class="text-red-400">*</span>
                </label>
                <input type="text" name="code_inventaire" value="{{ old('code_inventaire', $asset->code_inventaire) }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('code_inventaire') border-red-500 @enderror">
                @error('code_inventaire')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Marque -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Marque</label>
                <input type="text" name="marque" value="{{ old('marque', $asset->marque) }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                @error('marque')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Modèle -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Modèle</label>
                <input type="text" name="modele" value="{{ old('modele', $asset->modele) }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                @error('modele')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Numéro de Série -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Numéro de Série</label>
                <input type="text" name="numero_serie" value="{{ old('numero_serie', $asset->numero_serie) }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                @error('numero_serie')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Coût -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Coût (€)</label>
                <input type="number" name="cout" value="{{ old('cout', $asset->cout) }}" step="0.01" min="0" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                @error('cout')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date d'Acquisition -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Date d'Acquisition</label>
                <input type="date" name="date_acquisition" value="{{ old('date_acquisition', $asset->date_acquisition?->format('Y-m-d')) }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                @error('date_acquisition')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date de Garantie -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Date de Fin de Garantie</label>
                <input type="date" name="date_garantie_fin" value="{{ old('date_garantie_fin', $asset->date_garantie_fin?->format('Y-m-d')) }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                @error('date_garantie_fin')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Utilisateur -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Utilisateur</label>
                <select name="user_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                    <option value="">-- Non assigné --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($asset->user_id == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Département -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Département</label>
                <select name="departement_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                    <option value="">-- Non assigné --</option>
                    @foreach($departements as $dept)
                        <option value="{{ $dept->id }}" @selected($asset->departement_id == $dept->id)>{{ $dept->nom }}</option>
                    @endforeach
                </select>
                @error('departement_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Site -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Site</label>
                <select name="site_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                    <option value="">-- Non assigné --</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected($asset->site_id == $site->id)>{{ $site->nom }}</option>
                    @endforeach
                </select>
                @error('site_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-white mb-2">Description</label>
            <textarea name="description" rows="4" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">{{ old('description', $asset->description) }}</textarea>
            @error('description')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-end">
            <a href="{{ route('assets.show', $asset) }}" class="px-6 py-2 border border-dark-700 rounded-lg text-white hover:bg-dark-800 transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection
