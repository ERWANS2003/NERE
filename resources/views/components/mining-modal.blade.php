@props(['title' => null, 'size' => 'md'])

@php
$sizeClasses = match($size) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    default => 'max-w-md'
};
@endphp

<div x-data="{ open: false }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div @click="open = false" class="fixed inset-0 bg-charcoal-950/80 backdrop-blur-sm"></div>
    
    <!-- Modal -->
    <div class="relative {{ $sizeClasses }} w-full mx-4">
        <div class="bg-gradient-to-br from-charcoal-800 to-charcoal-900 border border-gold-500/30 rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            @if($title)
                <div class="bg-gradient-to-r from-gold-900/30 to-crimson-900/30 border-b border-gold-500/20 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gold-300">{{ $title }}</h3>
                    <button @click="open = false" class="text-charcoal-400 hover:text-charcoal-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @endif
            
            <!-- Content -->
            <div class="px-6 py-4">
                {{ $slot }}
            </div>
            
            <!-- Footer (if provided) -->
            @if(isset($footer))
                <div class="bg-charcoal-900/50 border-t border-charcoal-700 px-6 py-4 flex gap-3 justify-end">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
