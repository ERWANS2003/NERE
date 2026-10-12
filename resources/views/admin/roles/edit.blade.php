@extends('admin.layout')

@section('title', 'Modifier ' . $role->nom)

@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.roles.show', $role) }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux détails
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Modifier le rôle</h3>

        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')

            <!-- Informations de base -->
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <input type="text" name="nom" value="{{ old('nom', $role->nom) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('nom') border-red-500 @enderror">
                    @error('nom')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                    <input type="text" name="slug" value="{{ old('slug', $role->slug) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('slug') border-red-500 @enderror"
                           placeholder="ex: manager-it">
                    <p class="text-xs text-gray-500 mt-1">Identifiant unique (lettres minuscules, chiffres et tirets uniquement)</p>
                    @error('slug')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('description') border-red-500 @enderror">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Permissions -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                @if($permissions->count() > 0)
                <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto border border-gray-200 rounded p-3">
                    @foreach($permissions as $permission)
                    <label class="flex items-center">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                               {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                        <span class="ml-2 text-sm text-gray-700">{{ $permission->nom }}</span>
                    </label>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 italic">Aucune permission disponible.</p>
                @endif
            </div>

            <!-- Boutons -->
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.roles.show', $role) }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-4 py-2 bg-nere-600 text-white rounded hover:bg-nere-700">
                    <i class="fas fa-save mr-2"></i>Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
