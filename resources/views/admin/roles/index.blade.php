@extends('admin.layout')

@section('title', 'Gestion des rôles')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des rôles</h2>
        <p class="mt-1 text-sm text-gray-600">Organisez les responsabilités et les permissions des utilisateurs.</p>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center rounded-md bg-nere-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-nere-700 focus:outline-none focus:ring-2 focus:ring-nere-500 focus:ring-offset-2">
        <x-icone nom="plus" class="mr-2" />Nouveau rôle
    </a>
</div>

@if(session('success'))
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded text-green-700">
    <x-icone nom="circle-check" class="mr-2" />{{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded text-red-700">
    <x-icone nom="circle-alert" class="mr-2" />{{ session('error') }}
</div>
@endif

<!-- Filtres -->
<div class="mb-6 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
    <form method="GET" action="{{ route('admin.roles.index') }}" data-live-search class="flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <label for="role-search" class="sr-only">Rechercher un rôle</label>
            <input type="text" 
                   id="role-search"
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Rechercher par nom ou slug..."
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-nere-500 focus:outline-none focus:ring-2 focus:ring-nere-500">
        </div>
        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
            <x-icone nom="search" class="mr-2" />Rechercher
        </button>
        @if(request()->hasAny(['search']))
        <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <x-icone nom="x" class="mr-2" />Réinitialiser
        </a>
        @endif
    </form>
</div>

<!-- Liste des rôles -->
<div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nom
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Slug
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Permissions
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Utilisateurs
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($roles as $role)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ $role->nom }}</div>
                    @if($role->description)
                    <div class="text-xs text-gray-500">{{ Str::limit($role->description, 60) }}</div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $role->slug }}</code>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ $role->permissions->count() }} permission(s)
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ $role->users_count }} utilisateur(s)
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="{{ route('admin.roles.show', $role) }}" 
                       class="inline-flex items-center gap-1 text-blue-700 hover:text-blue-900"
                       aria-label="Voir le rôle {{ $role->nom }}">
                        <x-icone nom="eye" />
                        <span class="sr-only">Voir</span>
                    </a>
                    <a href="{{ route('admin.roles.edit', $role) }}" 
                       class="inline-flex items-center gap-1 text-amber-700 hover:text-amber-900"
                       aria-label="Modifier le rôle {{ $role->nom }}">
                        <x-icone nom="pencil" />
                        <span class="sr-only">Modifier</span>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                    <x-icone nom="user-cog" class="text-4xl mb-2 text-gray-300" />
                    <p class="font-medium text-gray-700">{{ request('search') ? 'Aucun rôle ne correspond à votre recherche.' : 'Aucun rôle n’a encore été créé.' }}</p>
                    @unless(request('search'))
                        <a href="{{ route('admin.roles.create') }}" class="mt-4 inline-flex items-center rounded-md bg-nere-600 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-700">
                            <x-icone nom="plus" class="mr-2" />Créer le premier rôle
                        </a>
                    @endunless
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($roles->hasPages())
<div class="mt-6">
    {{ $roles->links() }}
</div>
@endif
@endsection
