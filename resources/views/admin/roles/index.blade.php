@extends('admin.layout')

@section('title', 'Gestion des rôles')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestion des rôles</h2>
        <p class="mt-1 text-sm text-gray-600">Gérez les rôles et leurs permissions</p>
    </div>
    <a href="{{ route('admin.roles.create') }}" class="px-4 py-2 bg-nere-600 text-white rounded hover:bg-nere-700">
        <i class="fas fa-plus mr-2"></i>Nouveau rôle
    </a>
</div>

@if(session('success'))
<div class="mb-6 p-4 bg-green-50 border border-green-200 rounded text-green-700">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded text-red-700">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
</div>
@endif

<!-- Filtres -->
<div class="mb-6 bg-white rounded-lg shadow p-4">
    <form method="GET" action="{{ route('admin.roles.index') }}" class="flex gap-4">
        <div class="flex-1">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Rechercher par nom ou slug..."
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            <i class="fas fa-search mr-2"></i>Rechercher
        </button>
        @if(request()->hasAny(['search']))
        <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
            <i class="fas fa-times mr-2"></i>Réinitialiser
        </a>
        @endif
    </form>
</div>

<!-- Liste des rôles -->
<div class="bg-white rounded-lg shadow overflow-hidden">
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
                    {{ $role->users()->count() }} utilisateur(s)
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="{{ route('admin.roles.show', $role) }}" 
                       class="text-blue-600 hover:text-blue-900"
                       title="Voir">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('admin.roles.edit', $role) }}" 
                       class="text-yellow-600 hover:text-yellow-900"
                       title="Modifier">
                        <i class="fas fa-edit"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-user-tag text-4xl mb-2 text-gray-300"></i>
                    <p>Aucun rôle trouvé.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($roles->hasPages())
<div class="mt-6">
    {{ $roles->links() }}
</div>
@endif
@endsection
