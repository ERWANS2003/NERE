@extends('intranet.layouts.app')

@section('title', 'Formulaires')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Formulaires dynamiques</h1>
            <p class="mt-2 text-gray-600">Créez et gérez des formulaires personnalisés pour votre organisation</p>
        </div>
        <div class="mt-4 sm:mt-0">
            @can('manage_forms')
            <a href="{{ route('intranet.forms.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>
                Nouveau formulaire
            </a>
            @endcan
        </div>
    </div>

    <!-- Coming Soon Message -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-wpforms text-blue-600 text-2xl"></i>
        </div>
        <h3 class="text-lg font-medium text-blue-900 mb-2">
            Système de formulaires dynamiques
        </h3>
        <p class="text-blue-700 mb-6">
            Cette fonctionnalité permettra de créer des formulaires personnalisés avec des champs dynamiques, 
            des règles de validation et des workflows automatisés.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-600">
            <div class="flex items-center justify-center">
                <i class="fas fa-check-circle mr-2"></i>
                Champs dynamiques
            </div>
            <div class="flex items-center justify-center">
                <i class="fas fa-check-circle mr-2"></i>
                Validation avancée
            </div>
            <div class="flex items-center justify-center">
                <i class="fas fa-check-circle mr-2"></i>
                Workflows automatisés
            </div>
        </div>
        
        @can('manage_forms')
        <div class="mt-6">
            <a href="{{ route('intranet.forms.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50">
                <i class="fas fa-cog mr-2"></i>
                Commencer la configuration
            </a>
        </div>
        @endcan
    </div>

    <!-- Quick Actions for Development -->
    @can('manage_forms')
    <div class="mt-8 bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Actions de développement</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="#" class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-database text-blue-600 mr-2"></i>
                        <span class="font-medium">Modèles de données</span>
                    </div>
                    <p class="text-sm text-gray-600">Configurer les structures de formulaires</p>
                </a>
                
                <a href="#" class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-palette text-green-600 mr-2"></i>
                        <span class="font-medium">Concepteur visuel</span>
                    </div>
                    <p class="text-sm text-gray-600">Interface drag & drop pour formulaires</p>
                </a>
                
                <a href="#" class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-chart-line text-yellow-600 mr-2"></i>
                        <span class="font-medium">Analytics</span>
                    </div>
                    <p class="text-sm text-gray-600">Statistiques et rapports de soumissions</p>
                </a>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection