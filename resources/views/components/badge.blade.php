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
        'red', 'urgent', 'danger' => 'nere-badge nere-badge-danger',
        'status-open', 'info' => 'nere-badge nere-badge-info',
        'status-progress', 'important', 'warning' => 'nere-badge nere-badge-warning',
        'status-resolved', 'success' => 'nere-badge nere-badge-success',
        default => 'nere-badge',
    };
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $text ?? $slot }}
</span>
