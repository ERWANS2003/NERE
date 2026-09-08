@props(['data' => null])

@php
$data = $data ?? [
    'critical_count' => \App\Models\SafetyIncident::where('severity', 'critical')->whereNull('resolved_at')->count(),
    'alerts' => \App\Models\SafetyIncident::where('severity', 'critical')
        ->whereNull('resolved_at')
        ->orderBy('reported_at', 'desc')
        ->limit(5)
        ->get()
];
@endphp

<div class="widget-body">
    <!-- Header avec compteur -->
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700">Alertes Critiques</h3>
        @if($data['critical_count'] > 0)
        <span class="px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold animate-pulse">
            {{ $data['critical_count'] }}
        </span>
        @else
        <span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
            ✓ Aucune alerte
        </span>
        @endif
    </div>

    @if($data['alerts']->isEmpty())
    <!-- Empty state -->
    <div class="text-center py-6">
        <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-3">
            <x-icon name="check" size="lg" class="text-green-600" />
        </div>
        <p class="text-sm text-gray-600">Aucun incident critique</p>
        <p class="text-xs text-gray-500 mt-1">Tous les sites sont sécurisés</p>
    </div>
    @else
    <!-- Liste des alertes -->
    <div class="space-y-2">
        @foreach($data['alerts'] as $incident)
        <div class="bg-red-50 border-l-4 border-red-500 rounded-r-lg p-3 hover:bg-red-100 transition cursor-pointer">
            <div class="flex items-start justify-between mb-1">
                <div class="flex items-center gap-2">
                    <x-icon name="alert" size="sm" class="text-red-600 flex-shrink-0" />
                    <h4 class="font-semibold text-sm text-gray-900 line-clamp-1">
                        {{ $incident->title }}
                    </h4>
                </div>
                <span class="px-2 py-0.5 bg-red-200 text-red-800 rounded text-xs font-semibold uppercase">
                    {{ $incident->severity }}
                </span>
            </div>
            
            <p class="text-xs text-gray-700 ml-6 mb-2 line-clamp-2">
                {{ $incident->description }}
            </p>
            
            <div class="flex items-center gap-3 ml-6 text-xs text-gray-600">
                @if($incident->location)
                <span class="flex items-center gap-1">
                    <x-icon name="location" size="xs" />
                    {{ $incident->location }}
                </span>
                @endif
                <span class="flex items-center gap-1">
                    <x-icon name="clock" size="xs" />
                    {{ $incident->reported_at->diffForHumans() }}
                </span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Lien voir tout -->
    <div class="mt-4 text-center">
        <a href="#" class="text-sm text-red-600 hover:text-red-700 font-medium">
            Voir tous les incidents →
        </a>
    </div>
    @endif
</div>
