@extends('intranet.layouts.app')

@section('title', $department->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('intranet.dashboard') }}" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-home"></i>
                        <span class="sr-only">Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                        <a href="{{ route('intranet.departments.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                            Départements
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mr-4"></i>
                        <span class="text-sm font-medium text-gray-500">{{ $department->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>
        
        <div class="mt-4 sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full {{ $department->is_active ? 'bg-green-100' : 'bg-gray-100' }} mr-4">
                        <i class="fas fa-sitemap text-{{ $department->is_active ? 'green' : 'gray' }}-600 text-xl"></i>
                    </span>
                    {{ $department->name }}
                    @if($department->code)
                        <span class="ml-3 text-lg text-gray-500">({{ $department->code }})</span>
                    @endif
                </h1>
                @if($department->description)
                    <p class="mt-2 text-gray-600">{{ $department->description }}</p>
                @endif
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                @can('manage_departments')
                <a href="{{ route('intranet.departments.edit', $department) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-edit mr-2"></i>
                    Modifier
                </a>
                @endcan
                <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $department->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Department Info -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                        Informations générales
                    </h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nom du département</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $department->name }}</dd>
                        </div>
                        
                        @if($department->code)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Code</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $department->code }}</dd>
                        </div>
                        @endif

                        @if($department->manager)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Responsable</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <div class="flex items-center">
                                    <img class="h-6 w-6 rounded-full mr-2" 
                                         src="https://ui-avatars.com/api/?name={{ urlencode($department->manager->first_name . ' ' . $department->manager->last_name) }}&color=7F9CF5&background=EBF4FF&size=24" 
                                         alt="{{ $department->manager->first_name }}">
                                    {{ $department->manager->first_name }} {{ $department->manager->last_name }}
                                </div>
                            </dd>
                        </div>
                        @endif

                        @if($department->parent)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Département parent</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('intranet.departments.show', $department->parent) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    {{ $department->parent->name }}
                                </a>
                            </dd>
                        </div>
                        @endif

                        @if($department->email)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="mailto:{{ $department->email }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $department->email }}
                                </a>
                            </dd>
                        </div>
                        @endif

                        @if($department->phone)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="tel:{{ $department->phone }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $department->phone }}
                                </a>
                            </dd>
                        </div>
                        @endif

                        @if($department->location)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Localisation</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $department->location }}</dd>
                        </div>
                        @endif

                        @if($department->budget)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Budget annuel</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($department->budget, 2) }} €</dd>
                        </div>
                        @endif

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Date de création</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $department->created_at->format('d/m/Y à H:i') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dernière modification</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $department->updated_at->format('d/m/Y à H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Sub-departments -->
            @if($department->children && $department->children->count() > 0)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">
                        Sous-départements ({{ $department->children->count() }})
                    </h3>
                    <div class="space-y-3">
                        @foreach($department->children as $child)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full {{ $child->is_active ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                        <i class="fas fa-sitemap text-{{ $child->is_active ? 'green' : 'gray' }}-600 text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $child->name }}</p>
                                    @if($child->description)
                                    <p class="text-xs text-gray-500">{{ Str::limit($child->description, 80) }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="text-xs text-gray-500">
                                    {{ $child->users_count ?? 0 }} utilisateur(s)
                                </span>
                                <a href="{{ route('intranet.departments.show', $child) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    Voir détails
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Users -->
            @if($users && $users->count() > 0)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Membres du département ({{ $users->count() }})
                        </h3>
                        @can('manage_users')
                        <button type="button" 
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-1"></i>
                            Ajouter un membre
                        </button>
                        @endcan
                    </div>
                    <div class="space-y-3">
                        @foreach($users as $user)
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <div class="flex items-center">
                                <img class="h-10 w-10 rounded-full" 
                                     src="https://ui-avatars.com/api/?name={{ urlencode($user->first_name . ' ' . $user->last_name) }}&color=7F9CF5&background=EBF4FF" 
                                     alt="{{ $user->first_name }}">
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                        @if($department->manager_id === $user->id)
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Responsable
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                    @if($user->roles->count() > 0)
                                    <div class="mt-1">
                                        @foreach($user->roles as $role)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">
                                            {{ $role->name }}
                                        </span>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                @can('manage_users')
                                <a href="{{ route('admin.users.show', $user) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                    <span class="sr-only">Voir profil</span>
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Statistiques rapides
                    </h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nombre d'utilisateurs</dt>
                            <dd class="mt-1 text-2xl font-semibold text-blue-600">{{ $users->count() ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sous-départements</dt>
                            <dd class="mt-1 text-2xl font-semibold text-green-600">{{ $department->children->count() ?? 0 }}</dd>
                        </div>
                        @if(isset($ticketStats))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tickets ouverts</dt>
                            <dd class="mt-1 text-2xl font-semibold text-yellow-600">{{ $ticketStats['open'] ?? 0 }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tickets ce mois</dt>
                            <dd class="mt-1 text-2xl font-semibold text-purple-600">{{ $ticketStats['this_month'] ?? 0 }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Actions rapides
                    </h3>
                    <div class="space-y-3">
                        @can('create_tickets')
                        <a href="{{ route('tickets.create') }}?department={{ $department->id }}" 
                           class="flex items-center p-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-ticket-alt text-blue-500 mr-3"></i>
                            Créer un ticket
                        </a>
                        @endcan
                        
                        @can('manage_departments')
                        <a href="{{ route('intranet.departments.create') }}?parent={{ $department->id }}" 
                           class="flex items-center p-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-plus text-green-500 mr-3"></i>
                            Ajouter sous-département
                        </a>
                        @endcan

                        @can('manage_users')
                        <button type="button" 
                                class="flex items-center p-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 w-full text-left">
                            <i class="fas fa-user-plus text-purple-500 mr-3"></i>
                            Ajouter un membre
                        </button>
                        @endcan

                        <a href="{{ route('reports.departments', $department) }}" 
                           class="flex items-center p-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-chart-bar text-yellow-500 mr-3"></i>
                            Voir les rapports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Department Settings -->
            @can('manage_departments')
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Paramètres
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Tickets autorisés</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->allow_ticket_creation ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $department->allow_ticket_creation ? 'Oui' : 'Non' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Statut</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $department->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>
@endsection