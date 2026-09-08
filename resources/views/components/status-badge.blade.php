{{-- Status Badge Component - Replaces emoji status indicators --}}
@props(['status', 'type' => 'status'])

@php
    $statusConfig = [
        'status' => [
            'new' => ['label' => 'Nouveau', 'class' => 'status-info', 'icon' => 'status-new'],
            'open' => ['label' => 'Ouvert', 'class' => 'status-warning', 'icon' => 'status-open'],
            'assigned' => ['label' => 'Assigné', 'class' => 'status-warning', 'icon' => 'status-assigned'],
            'in_progress' => ['label' => 'En cours', 'class' => 'status-warning', 'icon' => 'status-open'],
            'resolved' => ['label' => 'Résolu', 'class' => 'status-success', 'icon' => 'status-resolved'],
            'closed' => ['label' => 'Fermé', 'class' => 'status-neutral', 'icon' => 'status-closed'],
            'waiting' => ['label' => 'En attente', 'class' => 'status-info', 'icon' => 'status-open'],
        ],
        'priority' => [
            'critical' => ['label' => 'Critique', 'class' => 'status-critical', 'icon' => 'priority-critical'],
            'high' => ['label' => 'Haute', 'class' => 'status-warning', 'icon' => 'priority-high'],
            'medium' => ['label' => 'Moyenne', 'class' => 'status-info', 'icon' => 'priority-medium'],
            'low' => ['label' => 'Basse', 'class' => 'status-neutral', 'icon' => 'priority-low'],
        ],
        'operational' => [
            'operational' => ['label' => 'Opérationnel', 'class' => 'status-success', 'icon' => 'status-resolved'],
            'maintenance' => ['label' => 'Maintenance', 'class' => 'status-warning', 'icon' => 'maintenance'],
            'offline' => ['label' => 'Hors ligne', 'class' => 'status-critical', 'icon' => 'status-closed'],
            'alert' => ['label' => 'Alerte', 'class' => 'status-critical', 'icon' => 'alert'],
        ],
    ];
    
    $config = $statusConfig[$type][$status] ?? ['label' => ucfirst($status), 'class' => 'status-neutral', 'icon' => 'info'];
@endphp

<span class="badge {{ $config['class'] }} inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold rounded-full">
    <x-icon name="{{ $config['icon'] }}" size="xs" />
    <span>{{ $config['label'] }}</span>
</span>
