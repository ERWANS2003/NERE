@extends('admin.layout')

@section('title', $role->nom)

@section('content')
<div class="max-w-4xl">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.roles.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
        </a>
        <div class="space-x-2">
            <a href="{{ route('admin.roles.edit', $role) }}" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                        {{ $role->users()->count() > 0 ? 'disabled' : '' }}>
                    <i class="fas fa-trash mr-2"></i>Supprimer
                </button>
            </form>
        </div>
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

    <!-- Informations du rôle -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du rôle</h3>
        
        <dl class="grid grid-cols-1 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Nom</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $role->nom }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Slug</dt>
                <dd class="mt-1">
                    <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $role->slug }}</code>
                </dd>
            </div>

            @if($role->description)
            <div>
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $role->description }}</dd>
            </div>
            @endif

            <div>
                <dt class="text-sm font-medium text-gray-500">Créé le</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $role->created_at->format('d/m/Y à H:i') }}</dd>
            </div>

            @if($role->updated_at != $role->created_at)
            <div>
                <dt class="text-sm font-medium text-gray-500">Dernière modification</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $role->updated_at->format('d/m/Y à H:i') }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <!-- Permissions -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Permissions ({{ $role->permissions->count() }})
        </h3>
        
        @if($role->permissions->count() > 0)
        <div class="grid grid-cols-2 gap-2">
            @foreach($role->permissions as $permission)
            <div class="flex items-center text-sm text-gray-700">
                <i class="fas fa-check text-green-500 mr-2"></i>
                {{ $permission->nom }}
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-500 italic">Aucune permission attribuée à ce rôle.</p>
        @endif
    </div>

    <!-- Utilisateurs -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Utilisateurs ({{ $role->users->count() }})
        </h3>
        
        @if($role->users->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($role->users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $user->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-2">
                            @if($user->actif)
                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Actif
                            </span>
                            @else
                            <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Inactif
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-sm">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-gray-500 italic">Aucun utilisateur n'a ce rôle.</p>
        @endif
    </div>
</div>
@endsection
