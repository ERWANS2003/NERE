@props([
    'status',
    'context' => null,
])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    [$label, $type] = match ($value) {
        'pending' => ['En attente', 'warning'],
        'approved' => [$context === 'leave' ? 'Approuvé' : 'Approuvée', 'success'],
        'rejected' => ['Rejetée', 'danger'],
        'denied' => ['Refusé', 'danger'],
        'cancelled' => ['Annulé', 'info'],
        'new' => ['Nouveau', 'info'],
        'in_progress' => ['En cours', 'warning'],
        'waiting' => ['En attente', 'warning'],
        'resolved' => ['Résolu', 'success'],
        'closed' => ['Fermé', 'default'],
        default => [ucfirst(str_replace('_', ' ', $value)), 'info'],
    };
@endphp

<x-badge :type="$type">{{ $label }}</x-badge>
