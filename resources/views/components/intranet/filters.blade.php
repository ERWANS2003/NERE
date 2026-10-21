@props([
    'label' => 'Filtres',
    'asForm' => false,
    'action' => null,
    'method' => 'GET',
])

@php
    $tag = $asForm ? 'form' : 'div';
    $defaults = [
        'class' => 'intranet-filters',
    ];

    if ($asForm) {
        $defaults['method'] = $method;
        $defaults['aria-label'] = $label;

        if ($action) {
            $defaults['action'] = $action;
        }
    } else {
        $defaults['role'] = 'group';
        $defaults['aria-label'] = $label;
    }
@endphp

<{{ $tag }} {{ $attributes->merge($defaults) }}>
    {{ $slot }}
</{{ $tag }}>
