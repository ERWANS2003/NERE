@props([
    'as' => 'div',
    'title' => null,
    'flat' => false,
    'padding' => true,
])

@php
    $tag = in_array($as, ['div', 'article', 'section'], true) ? $as : 'div';
    $cardClass = $flat ? 'carte-plate' : 'carte';
    if (!$padding) {
        $cardClass .= ' !p-0';
    }
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $cardClass]) }}>
    @if($title)
        <div class="mb-4 border-b border-graphite-200 pb-3">
            <h3 class="text-lg font-semibold text-graphite-900">{{ $title }}</h3>
        </div>
    @endif
    
    {{ $slot }}
</{{ $tag }}>
