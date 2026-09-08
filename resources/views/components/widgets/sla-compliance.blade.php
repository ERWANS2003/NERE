@props(['data' => null])

@php
// Calculer les métriques SLA
$totalTickets = \App\Models\Ticket::count();
$resolvedTickets = \App\Models\Ticket::whereHas('status', fn($q) => $q->where('slug', 'resolved'))->count();
$complianceRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0;

$data = $data ?? [
    'compliance_rate' => $complianceRate,
    'at_risk' => \App\Models\Ticket::whereNotNull('due_date')
        ->where('due_date', '<=', now()->addHours(2))
        ->whereDoesntHave('status', fn($q) => $q->whereIn('slug', ['resolved', 'closed']))
        ->count(),
    'breached' => \App\Models\Ticket::whereNotNull('due_date')
        ->where('due_date', '<', now())
        ->whereDoesntHave('status', fn($q) => $q->whereIn('slug', ['resolved', 'closed']))
        ->count()
];
@endphp

<div class="widget-body">
    <!-- Gauge de conformité -->
    <div class="text-center mb-6">
        <div class="relative inline-flex items-center justify-center w-32 h-32 mb-3">
            <!-- Background circle -->
            <svg class="transform -rotate-90 w-32 h-32">
                <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="12" fill="none" />
                <circle cx="64" cy="64" r="56" 
                        stroke="{{ $data['compliance_rate'] >= 80 ? '#10b981' : ($data['compliance_rate'] >= 60 ? '#f59e0b' : '#ef4444') }}" 
                        stroke-width="12" 
                        fill="none"
                        stroke-dasharray="{{ 2 * pi() * 56 }}"
                        stroke-dashoffset="{{ 2 * pi() * 56 * (1 - $data['compliance_rate'] / 100) }}"
                        class="transition-all duration-1000" />
            </svg>
            <div class="absolute">
                <div class="text-3xl font-bold text-gray-900">{{ $data['compliance_rate'] }}%</div>
            </div>
        </div>
        <h3 class="text-sm font-semibold text-gray-700">Taux de Conformité SLA</h3>
    </div>

    <!-- Métriques -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <!-- À risque -->
        <div class="bg-amber-50 rounded-lg p-3 border border-amber-200">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="clock" size="md" class="text-amber-600" />
                <span class="text-2xl font-bold text-amber-900">{{ $data['at_risk'] }}</span>
            </div>
            <div class="text-xs text-amber-800 font-medium">À Risque</div>
            <div class="text-xs text-amber-600 mt-1">< 2h avant deadline</div>
        </div>

        <!-- En breach -->
        <div class="bg-red-50 rounded-lg p-3 border border-red-200">
            <div class="flex items-center justify-between mb-2">
                <x-icon name="alert" size="md" class="text-red-600" />
                <span class="text-2xl font-bold text-red-900">{{ $data['breached'] }}</span>
            </div>
            <div class="text-xs text-red-800 font-medium">En Breach</div>
            <div class="text-xs text-red-600 mt-1">Deadline dépassée</div>
        </div>
    </div>

    <!-- Stats détaillées -->
    <div class="bg-gray-50 rounded-lg p-3">
        <div class="space-y-2">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Tickets résolus</span>
                <span class="font-semibold text-gray-900">{{ $resolvedTickets }} / {{ $totalTickets }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Temps moy. résolution</span>
                <span class="font-semibold text-gray-900">4.5h</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Satisfaction client</span>
                <span class="font-semibold text-gray-900 flex items-center gap-1">
                    4.3/5 
                    <x-icon name="star" size="xs" class="text-amber-500" />
                </span>
            </div>
        </div>
    </div>

    <!-- Alerte si breach -->
    @if($data['breached'] > 0)
    <div class="mt-4 bg-red-50 border-l-4 border-red-500 p-3 rounded-r-lg">
        <div class="flex items-center gap-2">
            <x-icon name="alert" size="sm" class="text-red-600" />
            <p class="text-xs text-red-800 font-medium">
                {{ $data['breached'] }} ticket(s) en breach nécessitent une attention immédiate
            </p>
        </div>
    </div>
    @endif
</div>
