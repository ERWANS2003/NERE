@props(['data' => null])

@php
$data = $data ?? [
    'avg_resolution_time' => '4.5',
    'tickets_resolved_today' => \App\Models\Ticket::whereHas('status', fn($q) => $q->where('slug', 'resolved'))
        ->whereDate('resolved_at', today())
        ->count(),
    'customer_satisfaction' => 4.3,
    'team_members' => \App\Models\User::whereHas('role', fn($q) => $q->whereIn('slug', ['technicien', 'technicien_senior']))->count(),
    'active_tickets' => \App\Models\Ticket::whereHas('status', fn($q) => $q->whereIn('slug', ['open', 'in_progress']))->count()
];
@endphp

<div class="widget-body">
    <!-- Métriques Principales -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="clock" size="md" class="opacity-80" />
                <span class="text-2xl font-bold">{{ $data['avg_resolution_time'] }}h</span>
            </div>
            <div class="text-sm font-medium opacity-90">Temps Moy. Résolution</div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="check" size="md" class="opacity-80" />
                <span class="text-2xl font-bold">{{ $data['tickets_resolved_today'] }}</span>
            </div>
            <div class="text-sm font-medium opacity-90">Résolus Aujourd'hui</div>
        </div>
    </div>

    <!-- Satisfaction Client -->
    <div class="mb-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Satisfaction Client</h4>
        <div class="flex items-center gap-3">
            <div class="flex-1 bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-amber-400 to-amber-500 rounded-full" 
                     style="width: {{ ($data['customer_satisfaction'] / 5) * 100 }}%"></div>
            </div>
            <div class="flex items-center gap-1">
                <span class="text-2xl font-bold text-gray-900">{{ $data['customer_satisfaction'] }}</span>
                <span class="text-gray-600">/5</span>
                <x-icon name="star" size="sm" class="text-amber-500 ml-1" />
            </div>
        </div>
    </div>

    <!-- Stats Équipe -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $data['team_members'] }}</div>
            <div class="text-xs text-gray-600 mt-1">Membres Équipe</div>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $data['active_tickets'] }}</div>
            <div class="text-xs text-gray-600 mt-1">Tickets Actifs</div>
        </div>
    </div>

    <!-- Tendance -->
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Tendance sur 7 jours</span>
            <span class="flex items-center gap-1 text-green-600 font-semibold">
                <x-icon name="arrow-up" size="xs" />
                +12%
            </span>
        </div>
    </div>
</div>
