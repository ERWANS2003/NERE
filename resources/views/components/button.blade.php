@props([
    'variant' => 'primary',
    'href' => null,
    'icon' => null,
    'type' => null,
])

@php
    $classes = match($variant) {
        'secondary', 'outline' => 'bouton-secondaire',
        'danger' => 'bouton-danger',
        default => 'bouton-primaire',
    };
    
    $tag = $href ? 'a' : 'button';
    $extraAttrs = $href ? ['href' => $href] : ['type' => $type ?? 'button'];
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes] + $extraAttrs) }}>
    @if($icon)
        <x-icone :nom="$icon" class="mr-2" />
    @endif
    {{ $slot }}
</{{ $tag }}>
