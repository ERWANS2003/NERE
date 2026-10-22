@extends('admin.layout')

@section('title', 'Gestion des ressources')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Bibliothèque de ressources</h3>
            <p class="mt-1 text-sm text-gray-600">Gérez les catégories et les ressources sans exposer les fichiers privés.</p>
        </div>
        @if($moduleEnabled)
            <a href="{{ route('intranet.resources.create') }}" class="bouton-primaire">Ajouter une ressource</a>
        @else
            <a href="{{ route('admin.modules.index') }}" class="rounded bg-gray-100 px-4 py-2 text-sm text-gray-600">Activez le module pour ajouter une ressource</a>
        @endif
    </div>

    <section class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        <h4 class="mb-4 font-semibold text-gray-900">Catégories</h4>
        <form method="POST" action="{{ route('admin.resources.categories.store') }}" class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-4">
            @csrf
            <input name="name" required maxlength="100" placeholder="Nom" class="rounded border-gray-300">
            <input name="slug" required maxlength="100" pattern="[A-Za-z0-9_-]+" placeholder="Identifiant (slug)" class="rounded border-gray-300">
            <input name="description" maxlength="1000" placeholder="Description" class="rounded border-gray-300">
            <div class="flex gap-2">
                <input name="order" type="number" min="0" max="9999" value="0" required aria-label="Ordre" class="w-24 rounded border-gray-300">
                <button class="bouton-primaire">Créer</button>
            </div>
        </form>

        <div class="space-y-3">
            @foreach($categories as $category)
                <div class="rounded border border-gray-200 p-3">
                    <form method="POST" action="{{ route('admin.resources.categories.update', $category) }}" class="grid grid-cols-1 gap-3 md:grid-cols-6">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ $category->name }}" required maxlength="100" class="rounded border-gray-300">
                        <input name="slug" value="{{ $category->slug }}" required maxlength="100" pattern="[A-Za-z0-9_-]+" class="rounded border-gray-300">
                        <input name="description" value="{{ $category->description }}" maxlength="1000" placeholder="Description" class="rounded border-gray-300">
                        <input name="icon" value="{{ $category->icon }}" maxlength="100" placeholder="Icône" class="rounded border-gray-300">
                        <input name="color" value="{{ $category->color }}" maxlength="30" placeholder="Couleur" class="rounded border-gray-300">
                        <div class="flex items-center gap-2">
                            <input name="order" type="number" min="0" max="9999" value="{{ $category->order }}" required aria-label="Ordre" class="w-20 rounded border-gray-300">
                            <button class="rounded bg-gray-800 px-3 py-2 text-sm text-white">Enregistrer</button>
                        </div>
                    </form>
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ $category->resources_count }} ressource(s)</span>
                        @if($category->resources_count === 0)
                            <form method="POST" action="{{ route('admin.resources.categories.destroy', $category) }}" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-700 hover:underline">Supprimer</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-lg bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h4 class="font-semibold text-gray-900">Ressources</h4>
            <form method="GET" class="flex flex-wrap gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="Titre ou description" class="rounded border-gray-300">
                <select name="status" class="rounded border-gray-300">
                    <option value="">Tous les états</option>
                    <option value="active" @selected(request('status') === 'active')>Actives</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Désactivées</option>
                </select>
                <button class="rounded bg-gray-800 px-3 py-2 text-sm text-white">Filtrer</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($resources as $resource)
                <form method="POST" action="{{ route('admin.resources.update', $resource) }}" class="grid grid-cols-1 gap-3 rounded border border-gray-200 p-4 xl:grid-cols-7">
                    @csrf
                    @method('PUT')
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Titre</label>
                        <input name="title" value="{{ $resource->title }}" required maxlength="255" class="w-full rounded border-gray-300">
                        <p class="mt-1 text-xs text-gray-500">{{ strtoupper($resource->file_type) }} · {{ $resource->uploader?->name ?? 'Compte supprimé' }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Catégorie</label>
                        <select name="category_id" required class="w-full rounded border-gray-300">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected($resource->category_id === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Département</label>
                        <select name="department_id" class="w-full rounded border-gray-300">
                            <option value="">Tous</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected($resource->department_id === $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">État</label>
                        <select name="is_active" required class="w-full rounded border-gray-300">
                            <option value="1" @selected($resource->is_active)>Active</option>
                            <option value="0" @selected(! $resource->is_active)>Désactivée</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Tags (séparés par des virgules)</label>
                        <input name="tags" value="{{ implode(', ', $resource->tags ?? []) }}" maxlength="1000" class="w-full rounded border-gray-300">
                    </div>
                    <div class="flex items-end">
                        <button class="bouton-primaire w-full">Enregistrer</button>
                    </div>
                    <div class="xl:col-span-7">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Description</label>
                        <textarea name="description" rows="2" maxlength="5000" class="w-full rounded border-gray-300">{{ $resource->description }}</textarea>
                    </div>
                </form>
            @empty
                <p class="py-8 text-center text-sm text-gray-500">Aucune ressource ne correspond à ces critères.</p>
            @endforelse
        </div>

        <div class="mt-5">{{ $resources->links() }}</div>
    </section>
@endsection
