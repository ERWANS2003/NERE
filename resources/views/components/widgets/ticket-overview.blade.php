@props(['data' => null])

@php
$data = $data ?? [
    'total' => \App\Models\Ticket::count(),
    'by_status' => \App\Models\Ticket::with('status')->get()->groupBy('status.name')->map->count(),
    'by_priority' => \App\Models\Ticket::with('priority')->get()->groupBy('priority.name')->map->count(),
    'trend' => []
];
@endphp

<div class="widget-body">
    <!-- Stats Grid -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-blue-100 text-blue-600">
                <x-icon name="tickets" size="md" />
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $data['total'] }}</div>
                <div class="stat-label">Total Tickets</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-amber-100 text-amber-600">
                <x-icon name="clock" size="md" />
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $data['by_status']['Ouvert'] ?? 0 }}</div>
                <div class="stat-label">Ouverts</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600">
                <x-icon name="check" size="md" />
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $data['by_status']['Résolu'] ?? 0 }}</div>
                <div class="stat-label">Résolus</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-red-100 text-red-600">
                <x-icon name="alert" size="md" />
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $data['by_priority']['Critique'] ?? 0 }}</div>
                <div class="stat-label">Critiques</div>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="mb-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Distribution par Statut</h4>
        <div class="space-y-2">
            @foreach($data['by_status'] as $status => $count)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" style="background-color: {{ 
                        match($status) {
                            'Nouveau' => '#3b82f6',
                            'Ouvert' => '#f59e0b',
                            'En cours' => '#8b5cf6',
                            'Résolu' => '#10b981',
                            'Fermé' => '#6b7280',
                            default => '#9ca3af'
                        }
                    }}"></div>
                    <span class="text-sm text-gray-700">{{ $status }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-accent-500 rounded-full" 
                             style="width: {{ $data['total'] > 0 ? ($count / $data['total'] * 100) : 0 }}%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 w-8 text-right">{{ $count }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Priority Distribution -->
    <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Distribution par Priorité</h4>
        <div class="flex gap-2">
            @foreach($data['by_priority'] as $priority => $count)
            <div class="flex-1 bg-gray-50 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $count }}</div>
                <div class="text-xs text-gray-600 mt-1">{{ $priority }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>
.stat-card {
    @apply bg-white rounded-lg p-4 border border-gray-200 flex items-center gap-3;
}
.stat-icon {
    @apply w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0;
}
.stat-value {
    @apply text-2xl font-bold text-gray-900;
}
.stat-label {
    @apply text-xs text-gray-600 mt-1;
}
</style>
