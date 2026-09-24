@props(['type' => 'info', 'icon' => true, 'dismissible' => false])

@php
$typeClasses = match($type) {
    'success' => 'bg-green-900/50 border-l-4 border-green-500 text-green-200',
    'error' => 'bg-crimson-900/50 border-l-4 border-crimson-500 text-crimson-200',
    'warning' => 'bg-yellow-900/50 border-l-4 border-yellow-500 text-yellow-200',
    'info' => 'bg-blue-900/50 border-l-4 border-blue-500 text-blue-200',
    default => 'bg-charcoal-800 border-l-4 border-charcoal-700 text-charcoal-200'
};

$iconSvg = match($type) {
    'success' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    'error' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    'warning' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-8a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    'info' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    default => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg p-4 mb-4 $typeClasses"]) }} x-data="{ open: true }" x-show="open">
    <div class="flex items-start gap-3">
        @if($icon)
            <div class="flex-shrink-0 mt-0.5">
                {!! $iconSvg !!}
            </div>
        @endif
        
        <div class="flex-1">
            {{ $slot }}
        </div>
        
        @if($dismissible)
            <button @click="open = false" class="flex-shrink-0 text-opacity-70 hover:text-opacity-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        @endif
    </div>
</div>
