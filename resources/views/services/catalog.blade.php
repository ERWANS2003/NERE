@extends('layouts.app-new')

@section('titre', 'Catalogue de Services')

@section('contenu')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Catalogue de Services</h1>
        <p class="text-gray-600 mt-2">Soumettez vos demandes rapidement via notre catalogue de services</p>
    </div>

    <!-- Mes Demandes Récentes -->
    @if($myRequests->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900">Mes Demandes Récentes</h2>
            <a href="{{ route('services.my-requests') }}" class="text-sm text-accent-600 hover:text-accent-700 font-medium">
                Voir tout →
            </a>
        </div>
        
        <div class="space-y-2">
            @foreach($myRequests as $request)
            <div class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">{{ $request->service->name }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $request->created_at->diffForHumans() }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ 
                    match($request->status) {
                        'pending' => 'bg-amber-100 text-amber-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800'
                    }
                }}">
                    {{ ucfirst($request->status) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Services par Catégorie -->
    @foreach($services as $categoryName => $categoryServices)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <x-icon name="folder" size="md" class="text-accent-600" />
            {{ $categoryName ?? 'Services Généraux' }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categoryServices as $service)
            <a href="{{ route('services.show', $service) }}" 
               class="service-card group bg-white rounded-lg shadow-sm border-2 border-gray-200 hover:border-accent-500 hover:shadow-md transition-all p-6 block">
                <!-- Icon -->
                <div class="w-12 h-12 rounded-lg bg-accent-100 flex items-center justify-center mb-4 group-hover:bg-accent-200 transition">
                    <x-icon :name="$service->icon ?? 'service'" size="lg" class="text-accent-600" />
                </div>

                <!-- Content -->
                <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-accent-600 transition">
                    {{ $service->name }}
                </h3>
                
                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                    {{ $service->description }}
                </p>

                <!-- Meta Info -->
                <div class="flex items-center justify-between text-xs text-gray-500 pt-4 border-t border-gray-100">
                    @if($service->estimated_time)
                    <span class="flex items-center gap-1">
                        <x-icon name="clock" size="xs" />
                        ~{{ round($service->estimated_time / 60, 1) }}h
                    </span>
                    @endif
                    
                    @if($service->requires_approval)
                    <span class="flex items-center gap-1 text-amber-600">
                        <x-icon name="shield-check" size="xs" />
                        Approbation requise
                    </span>
                    @else
                    <span class="flex items-center gap-1 text-green-600">
                        <x-icon name="lightning" size="xs" />
                        Instantané
                    </span>
                    @endif
                </div>

                <!-- Action -->
                <div class="mt-4">
                    <span class="inline-flex items-center gap-2 text-accent-600 font-medium text-sm group-hover:gap-3 transition-all">
                        Faire une demande
                        <x-icon name="arrow-right" size="sm" />
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endforeach

    @if($services->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <x-icon name="inbox" size="xl" class="mx-auto text-gray-300 mb-4" />
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Aucun service disponible</h3>
        <p class="text-gray-500">Les services seront bientôt disponibles dans le catalogue</p>
    </div>
    @endif
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.service-card {
    cursor: pointer;
}
</style>
@endsection
