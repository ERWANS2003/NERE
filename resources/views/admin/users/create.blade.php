@extends('admin.layout')

@section('title', 'Créer un utilisateur')

@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Nouveau compte utilisateur</h3>

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <!-- Informations de base -->
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('name') border-red-500 @enderror">
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('email') border-red-500 @enderror">
                    @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Matricule</label>
                    <input type="text" name="matricule" value="{{ old('matricule') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('matricule') border-red-500 @enderror">
                    @error('matricule')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="telephone" value="{{ old('telephone') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poste</label>
                        <input type="text" name="poste" value="{{ old('poste') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                    </div>
                </div>
            </div>

            <!-- Mot de passe -->
            <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe *</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('password') border-red-500 @enderror">
                    @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                </div>
            </div>

            <!-- Départements -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-2">Départements</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($departments as $dept)
                    <label class="flex items-center">
                        <input type="checkbox" name="departments[]" value="{{ $dept->id }}" 
                               {{ in_array($dept->id, old('departments', [])) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                        <span class="ml-2 text-sm text-gray-700">{{ $dept->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Rôles -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rôles système</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($roles as $role)
                    <label class="flex items-center">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                               {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                        <span class="ml-2 text-sm text-gray-700">{{ $role->nom }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Options -->
            <div class="space-y-3 mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="actif" value="1" checked
                           class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                    <span class="ml-2 text-sm text-gray-700">Compte actif</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" name="is_super_admin" value="1"
                           class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                    <span class="ml-2 text-sm text-gray-700">
                        <i class="fas fa-shield-alt text-nere-600"></i> Super administrateur
                    </span>
                </label>
            </div>

            <!-- Boutons -->
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-4 py-2 bg-nere-600 text-white rounded hover:bg-nere-700">
                    <i class="fas fa-save mr-2"></i>Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
