@props(['variant' => 'default', 'size' => 'md'])

@php
$variantClasses = match($variant) {
    'success' => 'bg-green-900/50 border border-green-700 text-green-400',
    'warning' => 'bg-yellow-900/50 border border-yellow-700 text-yellow-400',
    'danger' => 'bg-crimson-900/50 border border-crimson-700 text-crimson-400',
    'gold' => 'bg-gold-900/50 border border-gold-700 text-gold-400',
    'info' => 'bg-blue-900/50 border border-blue-700 text-blue-400',
    default => 'bg-charcoal-800 border border-charcoal-700 text-charcoal-300'
};

$sizeClasses = match($size) {
    'sm' => 'px-2 py-1 text-xs',
    'md' => 'px-3 py-1.5 text-sm',
    'lg' => 'px-4 py-2 text-base',
    default => 'px-3 py-1.5 text-sm'
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full font-medium $variantClasses $sizeClasses"]) }}>
    {{ $slot }}
</span>
