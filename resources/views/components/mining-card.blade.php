@props(['accent' => 'gold', 'hover' => true])

@php
$accentColor = match($accent) {
    'gold' => 'border-gold-500 hover:shadow-gold-glow',
    'crimson' => 'border-crimson-500 hover:shadow-crimson-glow',
    'charcoal' => 'border-charcoal-700',
    default => 'border-gold-500'
};

$hoverClass = $hover ? 'hover:border-opacity-100 transition-all duration-300' : '';
@endphp

<div {{ $attributes->merge(['class' => "bg-gradient-to-br from-charcoal-800 to-charcoal-900 border border-$accent border-opacity-30 $hoverClass rounded-xl p-6 backdrop-blur-sm"]) }}>
    {{ $slot }}
</div>
