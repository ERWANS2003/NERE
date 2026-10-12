@props([
    'variant' => 'primary', // primary, secondary, outline
    'href' => null,
    'icon' => null,
])

@php
    $classes = match($variant) {
        'secondary' => 'nere-btn nere-btn-secondary',
        'outline' => 'nere-btn nere-btn-outline',
        default => 'nere-btn nere-btn-primary',
    };
    
    $tag = $href ? 'a' : 'button';
    $extraAttrs = $href ? ['href' => $href] : ['type' => 'button'];
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes] + $extraAttrs) }}>
    @if($icon)
        <i class="fas fa-{{ $icon }} mr-2"></i>
    @endif
    {{ $slot }}
</{{ $tag }}>
