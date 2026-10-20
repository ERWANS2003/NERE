@extends('admin.layout')

@section('title', 'Modifier ' . $role->nom)

@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.roles.show', $role) }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux détails
        </a>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-gray-900">Modifier « {{ $role->nom }} »</h2>
            <p class="mt-1 text-sm text-gray-600">Mettez à jour les informations et les permissions de ce rôle.</p>
        </div>

        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf
            @method('PUT')

            <!-- Informations de base -->
            <div class="space-y-5 border-b border-gray-200 px-6 py-6">
                <div>
                    <label for="role-name" class="mb-1 block text-sm font-medium text-gray-700">Nom du rôle <span class="text-red-600">*</span></label>
                    <input id="role-name" type="text" name="nom" value="{{ old('nom', $role->nom) }}" required maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('nom') border-red-500 @enderror">
                    @error('nom')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role-slug" class="mb-1 block text-sm font-medium text-gray-700">Identifiant technique <span class="text-red-600">*</span></label>
                    <input id="role-slug" type="text" name="slug" value="{{ old('slug', $role->slug) }}" required maxlength="255" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('slug') border-red-500 @enderror"
                           placeholder="ex: manager-it">
                    <p class="mt-1 text-xs text-gray-500">Identifiant unique en minuscules, chiffres et tirets.</p>
                    @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role-description" class="mb-1 block text-sm font-medium text-gray-700">Description <span class="font-normal text-gray-500">(facultatif)</span></label>
                    <textarea id="role-description" name="description" rows="3" maxlength="2000"
                              class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('description') border-red-500 @enderror">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="px-6 py-6">
                @include('admin.roles._permission-picker', [
                    'permissionGroups' => $permissionGroups,
                    'selectedPermissions' => old('permissions', $role->permissions->pluck('id')->all()),
                ])
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-between">
                <a href="{{ route('admin.roles.show', $role) }}" class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Annuler
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded bg-nere-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-nere-700 focus:outline-none focus:ring-2 focus:ring-nere-500 focus:ring-offset-2">
                    <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
