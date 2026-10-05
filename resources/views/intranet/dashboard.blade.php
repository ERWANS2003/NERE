@extends('intranet.layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Tableau de bord</h1>
        <p class="mt-2 text-gray-600">Bienvenue, {{ auth()->user()->first_name }}!</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Departments -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-sitemap text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Départements
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $stats['departments'] ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Forms -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-wpforms text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Formulaires
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $stats['forms'] ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Submissions -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-paper-plane text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Soumissions
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $stats['submissions'] ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-users text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Utilisateurs
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ $stats['users'] ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick Actions -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Actions rapides
                </h3>
                <div class="space-y-3">
                    <a href="{{ route('intranet.departments.create') }}" 
                       class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-plus text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">
                                Créer un département
                            </p>
                            <p class="text-sm text-gray-500">
                                Ajouter un nouveau département à l'organisation
                            </p>
                        </div>
                    </a>

                    @can('manage_forms')
                    <a href="{{ route('intranet.forms.create') }}" 
                       class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-wpforms text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">
                                Créer un formulaire
                            </p>
                            <p class="text-sm text-gray-500">
                                Concevoir un nouveau formulaire dynamique
                            </p>
                        </div>
                    </a>
                    @endcan

                    <a href="{{ route('intranet.departments.index') }}" 
                       class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-list text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">
                                Voir tous les départements
                            </p>
                            <p class="text-sm text-gray-500">
                                Gérer l'organisation des départements
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Activité récente
                </h3>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @forelse($recentActivity ?? [] as $index => $activity)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-{{ $activity['color'] ?? 'gray' }}-500 flex items-center justify-center ring-8 ring-white">
                                            <i class="fas fa-{{ $activity['icon'] ?? 'info' }} text-white text-xs"></i>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                {!! $activity['description'] ?? 'Action inconnue' !!}
                                            </p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                            <time datetime="{{ $activity['created_at'] ?? now() }}">
                                                {{ $activity['time_ago'] ?? 'À l\'instant' }}
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="text-center py-8">
                            <div class="text-gray-500">
                                <i class="fas fa-history text-2xl mb-2"></i>
                                <p class="text-sm">Aucune activité récente</p>
                            </div>
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Departments Overview -->
    @if(isset($departments) && $departments->count() > 0)
    <div class="mt-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Aperçu des départements
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($departments as $department)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-900">
                                {{ $department->name }}
                            </h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $department->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        @if($department->description)
                        <p class="text-xs text-gray-500 mb-2">
                            {{ Str::limit($department->description, 100) }}
                        </p>
                        @endif
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>{{ $department->users_count ?? 0 }} utilisateur(s)</span>
                            <a href="{{ route('intranet.departments.show', $department) }}" 
                               class="text-blue-600 hover:text-blue-800">
                                Voir détails
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if($departments->count() >= 6)
                <div class="mt-4 text-center">
                    <a href="{{ route('intranet.departments.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Voir tous les départements
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection