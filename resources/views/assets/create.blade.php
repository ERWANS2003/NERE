@extends('layouts.portal')

@section('titre', 'Créer un Actif')
@section('sous-titre', 'Ajouter un nouvel équipement au inventaire')

@section('contenu')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Nouvel Actif</h1>
            <p class="text-sm text-gray-400 mt-1">Remplissez les informations de l'équipement</p>
        </div>
        <a href="{{ route('assets.index') }}" class="text-gray-400 hover:text-white transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('assets.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Type d'Actif -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Type d'Actif <span class="text-red-400">*</span>
                </label>
                <select name="asset_type_id" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('asset_type_id') border-red-500 @enderror">
                    <option value="">-- Sélectionner --</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" @selected(old('asset_type_id') == $type->id)>{{ $type->nom }}</option>
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
                    <option value="">-- Sélectionner --</option>
                    <option value="En stock" @selected(old('statut') == 'En stock')>En stock</option>
                    <option value="En service" @selected(old('statut') == 'En service')>En service</option>
                    <option value="En maintenance" @selected(old('statut') == 'En maintenance')>En maintenance</option>
                    <option value="Hors service" @selected(old('statut') == 'Hors service')>Hors service</option>
                    <option value="Réformé" @selected(old('statut') == 'Réformé')>Réformé</option>
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
                <input type="text" name="nom" value="{{ old('nom') }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('nom') border-red-500 @enderror" placeholder="Ex: Ordinateur de Bureau">
                @error('nom')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Code Inventaire -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">
                    Code Inventaire <span class="text-red-400">*</span>
                </label>
                <input type="text" name="code_inventaire" value="{{ old('code_inventaire') }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition @error('code_inventaire') border-red-500 @enderror" placeholder="Ex: INV-2026-001">
                @error('code_inventaire')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Marque -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Marque</label>
                <input type="text" name="marque" value="{{ old('marque') }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Ex: Dell">
                @error('marque')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Modèle -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Modèle</label>
                <input type="text" name="modele" value="{{ old('modele') }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Ex: OptiPlex 7090">
                @error('modele')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Numéro de Série -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Numéro de Série</label>
                <input type="text" name="numero_serie" value="{{ old('numero_serie') }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Ex: ABC123XYZ">
                @error('numero_serie')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Coût -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Coût (€)</label>
                <input type="number" name="cout" value="{{ old('cout') }}" step="0.01" min="0" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition" placeholder="0.00">
                @error('cout')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date d'Acquisition -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Date d'Acquisition</label>
                <input type="date" name="date_acquisition" value="{{ old('date_acquisition') }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
                @error('date_acquisition')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date de Garantie -->
            <div>
                <label class="block text-sm font-medium text-white mb-2">Date de Fin de Garantie</label>
                <input type="date" name="date_garantie_fin" value="{{ old('date_garantie_fin') }}" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition">
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
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
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
                        <option value="{{ $dept->id }}" @selected(old('departement_id') == $dept->id)>{{ $dept->nom }}</option>
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
                        <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->nom }}</option>
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
            <textarea name="description" rows="4" class="w-full px-4 py-2 bg-dark-800 border border-dark-700 rounded-lg text-white focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Notes supplémentaires sur l'équipement...">{{ old('description') }}</textarea>
            @error('description')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-end">
            <a href="{{ route('assets.index') }}" class="px-6 py-2 border border-dark-700 rounded-lg text-white hover:bg-dark-800 transition">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition">
                Créer l'Actif
            </button>
        </div>
    </form>
</div>
@endsection
