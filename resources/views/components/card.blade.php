@props([
    'title' => null,
    'flat' => false,
    'padding' => true,
])

@php
    $cardClass = $flat ? 'nere-card-flat' : 'nere-card';
    if (!$padding) {
        $cardClass .= ' !p-0';
    }
@endphp

<div {{ $attributes->merge(['class' => $cardClass]) }}>
    @if($title)
        <div class="mb-4 pb-3 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
        </div>
    @endif
    
    {{ $slot }}
</div>
