@props([
    // Styles du design system : default, yellow, red, status-open, status-progress,
    // status-resolved, urgent, important, info.
    // Alias sémantiques : danger = urgent, warning = important, success = status-resolved.
    'type' => 'default',
    'text' => null,
])

@php
    $classes = match($type) {
        'yellow' => 'nere-badge nere-badge-yellow',
        'red' => 'nere-badge nere-badge-red',
        'status-open' => 'nere-badge nere-badge-status-open',
        'status-progress' => 'nere-badge nere-badge-status-progress',
        'status-resolved', 'success' => 'nere-badge nere-badge-status-resolved',
        'urgent', 'danger' => 'nere-badge nere-badge-urgent',
        'important', 'warning' => 'nere-badge nere-badge-important',
        'info' => 'nere-badge nere-badge-info',
        default => 'nere-badge',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $text ?? $slot }}
</span>
