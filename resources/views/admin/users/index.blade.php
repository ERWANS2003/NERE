@extends('admin.layout')

@section('title', 'Gestion des utilisateurs')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h3 class="text-lg font-semibold text-gray-900">Liste des utilisateurs</h3>
        <p class="text-sm text-gray-600">Gérez les comptes utilisateurs de l'intranet</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-nere-600 text-white rounded hover:bg-nere-700">
        <i class="fas fa-plus mr-2"></i>Nouvel utilisateur
    </a>
</div>

<!-- Filtres -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" data-live-search class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Rechercher..." 
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
        </div>
        <div>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                <option value="">Tous les statuts</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
            </select>
        </div>
        <div>
            <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-nere-500 focus:border-nere-500">
                <option value="">Tous les départements</option>
                @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="flex space-x-2">
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                <i class="fas fa-search"></i>
            </button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Utilisateur</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Matricule</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Départements</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Statut</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-nere-100 rounded-full flex items-center justify-center text-nere-600 font-semibold mr-3">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ $user->name }}</div>
                            @if($user->is_super_admin)
                            <span class="text-xs text-nere-600">
                                <i class="fas fa-shield-alt"></i> Admin système
                            </span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $user->matricule ?: '-' }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    {{ $user->departments->pluck('name')->join(', ') ?: '-' }}
                </td>
                <td class="px-6 py-4">
                    @if($user->actif)
                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">Actif</span>
                    @else
                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">Inactif</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-yellow-600 hover:text-yellow-800">
                        <i class="fas fa-edit"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    Aucun utilisateur trouvé
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
