@extends('intranet.layouts.app')

@section('title', 'Mon Profil')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Mon Profil</h1>
        <p class="mt-2 text-gray-600">Informations de votre compte utilisateur</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-6">
            <div class="grid grid-cols-1 gap-6">
                <div class="flex items-center space-x-6">
                    <div class="flex-shrink-0">
                        <img class="h-20 w-20 rounded-full" 
                             src="https://ui-avatars.com/api/?name={{ urlencode($user->first_name . ' ' . $user->last_name) }}&color=7F9CF5&background=EBF4FF&size=80" 
                             alt="{{ $user->first_name }}">
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $user->first_name }} {{ $user->last_name }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        @if($user->departement)
                            <p class="text-sm text-gray-500">{{ $user->departement->name }}</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Prénom</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->first_name }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nom</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->last_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                    </div>

                    @if($user->matricule)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Matricule</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->matricule }}</dd>
                    </div>
                    @endif

                    @if($user->departement)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Département</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->departement->name }}</dd>
                    </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Statut</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Membre depuis</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('d/m/Y') }}</dd>
                    </div>
                </div>

                @if($user->roles->count() > 0)
                <div>
                    <dt class="text-sm font-medium text-gray-500 mb-2">Rôles</dt>
                    <dd class="flex flex-wrap gap-2">
                        @foreach($user->roles as $role)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </dd>
                </div>
                @endif
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
            <a href="{{ route('profile.settings') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-edit mr-2"></i>
                Modifier mes informations
            </a>
        </div>
    </div>
</div>
@endsection