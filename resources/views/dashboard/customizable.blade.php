@extends('layouts.app-new')

@section('title', 'Portail de Services')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-16 px-6">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-4xl font-bold mb-4">
                Néré Mining - Portail de Services
            </h1>
            <p class="text-xl text-gray-300 max-w-2xl">
                Une demande unique, dirigée automatiquement vers le bon service et suivie jusqu'à sa résolution.
            </p>
            
            <!-- Quick Action Button -->
            <div class="mt-8">
                <a href="{{ route('tickets.create') }}" 
                   class="inline-flex items-center gap-3 bg-amber-500 hover:bg-amber-600 text-white px-8 py-4 rounded-lg font-semibold text-lg transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Créer une demande
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-12">
        
        <!-- Title Section -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                Que souhaitez-vous faire?
            </h2>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Achats -->
            <a href="{{ route('tickets.create', ['department' => 'achats']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">AC</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">Achats</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Demande d'achat · Commande fournisseur</p>
            </a>

            <!-- Finance -->
            <a href="{{ route('tickets.create', ['department' => 'finance']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center group-hover:bg-green-200 transition">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">FI</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">Finance</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Demande de paiement · Budget · Facturation · Avance de fonds / note de frais</p>
            </a>

            <!-- Géologie -->
            <a href="{{ route('tickets.create', ['department' => 'geologie']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center group-hover:bg-amber-200 transition">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">GÉ</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">Géologie</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Échantillonnage · Données géologiques · Support logiciel SIG / modélisation · Matériel de terrain</p>
            </a>

            <!-- HSE -->
            <a href="{{ route('tickets.create', ['department' => 'hse']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center group-hover:bg-red-200 transition">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">HS</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">HSE</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Accident · Presqu'accident · Inspection · Observation</p>
            </a>

            <!-- Logistique -->
            <a href="{{ route('tickets.create', ['department' => 'logistique']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">LO</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">Logistique</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Véhicule · Transport · Carburant · Transport de personnel</p>
            </a>

            <!-- Production -->
            <a href="{{ route('tickets.create', ['department' => 'production']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200 transition">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">PR</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">Production</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Incident de production · Équipement de production · Anomalie de process · Arrêt de production</p>
            </a>

            <!-- RH -->
            <a href="{{ route('tickets.create', ['department' => 'rh']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-pink-100 flex items-center justify-center group-hover:bg-pink-200 transition">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">RH</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">RH</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Congé · Attestation · Recrutement · Formation</p>
            </a>

            <!-- IT -->
            <a href="{{ route('tickets.create', ['department' => 'it']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-cyan-100 flex items-center justify-center group-hover:bg-cyan-200 transition">
                            <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">IT</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">Informatique</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Support technique · Accès logiciel · Matériel informatique · Réseau</p>
            </a>

            <!-- Maintenance -->
            <a href="{{ route('tickets.create', ['department' => 'maintenance']) }}" 
               class="service-card group">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-gray-900">MA</div>
                    </div>
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-sm font-medium text-gray-700">
                        0
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-2">Maintenance</h3>
                <p class="text-sm text-gray-600 mb-3">1 équipe de traitement</p>
                <p class="text-sm text-gray-700">Réparation équipement · Maintenance préventive · Pièces de rechange</p>
            </a>

        </div>

        <!-- Quick Stats Section -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ \App\Models\Ticket::whereIn('status_id', [3, 4])->count() }}</div>
                        <div class="text-sm text-gray-600">Demandes résolues</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ \App\Models\Ticket::whereIn('status_id', [2])->count() }}</div>
                        <div class="text-sm text-gray-600">En cours de traitement</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">< 2h</div>
                        <div class="text-sm text-gray-600">Temps moyen de réponse</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
.service-card {
    @apply bg-white rounded-lg p-6 shadow-sm border border-gray-200 hover:shadow-md hover:border-amber-400 transition-all cursor-pointer block;
}

.service-card:hover {
    transform: translateY(-2px);
}
</style>
@endpush
@endsection
