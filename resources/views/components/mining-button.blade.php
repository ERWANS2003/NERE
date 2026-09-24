@props(['variant' => 'gold', 'size' => 'md', 'type' => 'button'])

@php
$baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold transition-all rounded-lg disabled:opacity-50 disabled:cursor-not-allowed';

$variantClasses = match($variant) {
    'gold' => 'bg-gradient-to-r from-gold-500 to-gold-600 hover:from-gold-600 hover:to-gold-700 text-charcoal-900 shadow-gold-glow hover:shadow-lg',
    'crimson' => 'bg-gradient-to-r from-crimson-500 to-crimson-600 hover:from-crimson-600 hover:to-crimson-700 text-white shadow-crimson-glow hover:shadow-lg',
    'outline' => 'bg-transparent border-2 border-gold-500 text-gold-400 hover:bg-gold-500/10',
    'secondary' => 'bg-charcoal-800 text-charcoal-200 hover:bg-charcoal-700 border border-charcoal-700',
    default => 'bg-charcoal-800 text-charcoal-200 hover:bg-charcoal-700'
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-base',
    'lg' => 'px-6 py-3 text-lg',
    'xl' => 'px-8 py-4 text-xl',
    default => 'px-4 py-2'
};
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "$baseClasses $variantClasses $sizeClasses"]) }}>
    {{ $slot }}
</button>
