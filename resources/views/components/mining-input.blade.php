@props(['label' => null, 'type' => 'text', 'accent' => 'gold', 'error' => null])

@php
$accentColor = match($accent) {
    'gold' => 'focus:ring-gold-500 focus:border-gold-400',
    'crimson' => 'focus:ring-crimson-500 focus:border-crimson-400',
    default => 'focus:ring-gold-500 focus:border-gold-400'
};

$borderColor = $error ? 'border-crimson-500' : 'border-charcoal-700';
@endphp

<div class="space-y-2">
    @if($label)
        <label class="block text-sm font-medium text-charcoal-300">
            {{ $label }}
            @if($attributes->get('required'))
                <span class="text-crimson-500">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}"
        {{ $attributes->merge(['class' => "w-full px-4 py-3 bg-charcoal-900 border $borderColor rounded-lg text-charcoal-100 placeholder-charcoal-500 transition-all $accentColor ring-0 focus:ring-2"]) }}
    />
    
    @if($error)
        <p class="text-sm text-crimson-400 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ $error }}
        </p>
    @endif
</div>
