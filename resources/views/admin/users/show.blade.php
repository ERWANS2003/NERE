@extends('admin.layout')

@section('title', 'Détails utilisateur')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
        </a>
        <div class="space-x-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            @if($user->actif && ! $user->guid)
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                        onclick="return confirm('Désactiver cet utilisateur local ?')">
                    <i class="fas fa-ban mr-2"></i>Désactiver
                </button>
            </form>
            @elseif($user->guid)
                <span class="inline-flex items-center px-3 py-2 text-sm text-gray-500 bg-gray-100 rounded">
                    <i class="fas fa-lock mr-2"></i>Statut géré par l'AD
                </span>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-nere-100 rounded-full flex items-center justify-center text-nere-600 text-2xl font-bold mr-4">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $user->name }}</h3>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    @if($user->guid)
                    <span class="inline-flex items-center mt-1 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                        <i class="fas fa-network-wired mr-1"></i> Utilisateur Active Directory
                    </span>
                    @endif
                    @if($user->is_super_admin)
                    <span class="inline-block mt-1 px-2 py-1 text-xs bg-nere-100 text-nere-700 rounded">
                        <i class="fas fa-shield-alt"></i> Super Administrateur intranet
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Informations -->
        <div class="p-6">
            <h4 class="font-semibold text-gray-900 mb-4">Informations</h4>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Matricule</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->matricule ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Statut</dt>
                    <dd class="mt-1">
                        @if($user->actif)
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Actif</span>
                        @else
                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">Inactif</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->telephone ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Poste</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->poste ?: '-' }}</dd>
                </div>
                @if($user->guid)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Identifiant AD (GUID)</dt>
                    <dd class="mt-1 text-xs font-mono text-gray-900 break-all">{{ $user->guid }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Source des droits</dt>
                    <dd class="mt-1 text-sm text-gray-900">Rôles intranet uniquement</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Départements -->
        <div class="p-6 border-t border-gray-200">
            <h4 class="font-semibold text-gray-900 mb-4">Départements</h4>
            @if($user->departments->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($user->departments as $dept)
                @php
                    $roleLabel = match($dept->pivot->role) {
                        'director' => 'Directeur',
                        'technician' => 'Technicien'.($dept->pivot->tech_level ? ' N'.$dept->pivot->tech_level : ''),
                        default => 'Utilisateur',
                    };
                @endphp
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                    {{ $dept->name }} · {{ $roleLabel }}
                </span>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-sm">Aucun département assigné</p>
            @endif
        </div>

        <!-- Rôles système -->
        <div class="p-6 border-t border-gray-200">
            <h4 class="font-semibold text-gray-900 mb-4">Rôles système</h4>
            @if($user->roles->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($user->roles as $role)
                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                    {{ $role->nom }}
                </span>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-sm">Aucun rôle assigné</p>
            @endif
        </div>
    </div>
</div>
@endsection
