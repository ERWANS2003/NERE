@extends('admin.layout')

@section('title', 'Créer un rôle')

@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
        </a>
    </div>

    <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
        <p><i class="fas fa-circle-info mr-2" aria-hidden="true"></i>Un rôle regroupe des permissions réutilisables. Les droits d’administrateur système et les responsabilités propres à un département restent gérés séparément.</p>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-lg font-semibold text-gray-900">Créer un rôle</h2>
            <p class="mt-1 text-sm text-gray-600">Donnez-lui un nom clair, puis choisissez les permissions adaptées.</p>
        </div>
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf

            <!-- Informations de base -->
            <div class="space-y-5 border-b border-gray-200 px-6 py-6">
                <div>
                    <label for="role-name" class="mb-1 block text-sm font-medium text-gray-700">Nom du rôle <span class="text-red-600">*</span></label>
                    <input id="role-name" type="text" name="nom" value="{{ old('nom') }}" required maxlength="255" autocomplete="off"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('nom') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Exemple : Gestionnaire des demandes RH.</p>
                    @error('nom')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role-slug" class="mb-1 block text-sm font-medium text-gray-700">Identifiant technique <span class="text-red-600">*</span></label>
                    <input id="role-slug" type="text" name="slug" value="{{ old('slug') }}" required maxlength="255" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" autocomplete="off"
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('slug') border-red-500 @enderror"
                           placeholder="ex: manager-it">
                    <p class="mt-1 text-xs text-gray-500">Généré depuis le nom ; vous pouvez le modifier. Minuscules, chiffres et tirets.</p>
                    @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="role-description" class="mb-1 block text-sm font-medium text-gray-700">Description <span class="font-normal text-gray-500">(facultatif)</span></label>
                    <textarea id="role-description" name="description" rows="3" maxlength="2000"
                              class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="px-6 py-6">
                @include('admin.roles._permission-picker', [
                    'permissionGroups' => $permissionGroups,
                    'selectedPermissions' => old('permissions', []),
                ])
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-between">
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    Annuler
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded bg-nere-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-nere-700 focus:outline-none focus:ring-2 focus:ring-nere-500 focus:ring-offset-2">
                    <i class="fas fa-check mr-2"></i>Créer le rôle
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const name = document.getElementById('role-name');
    const slug = document.getElementById('role-slug');
    let slugWasEdited = slug.value !== '';

    slug.addEventListener('input', () => { slugWasEdited = true; });
    name.addEventListener('input', () => {
        if (!slugWasEdited) {
            slug.value = name.value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });
});
</script>
@endpush
