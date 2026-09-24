@props(['title' => null, 'value' => 0, 'trend' => null, 'icon' => null, 'color' => 'gold'])

@php
$colorClasses = match($color) {
    'gold' => 'border-gold-500/30 text-gold-400',
    'crimson' => 'border-crimson-500/30 text-crimson-400',
    'green' => 'border-green-500/30 text-green-400',
    'blue' => 'border-blue-500/30 text-blue-400',
    default => 'border-gold-500/30 text-gold-400'
};

$trendColor = $trend > 0 ? 'text-green-400' : 'text-crimson-400';
$trendIcon = $trend > 0 ? '↑' : '↓';
@endphp

<div {{ $attributes->merge(['class' => "mining-card border $colorClasses group"]) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-charcoal-400 text-sm font-medium mb-1">{{ $title }}</p>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-bold text-white">{{ $value }}</p>
                @if($trend !== null)
                    <span class="text-sm font-semibold {{ $trendColor }}">
                        {{ $trendIcon }} {{ abs($trend) }}%
                    </span>
                @endif
            </div>
        </div>
        
        @if($icon)
            <div class="flex-shrink-0 p-3 rounded-lg bg-charcoal-800 group-hover:bg-gold-500/10 transition">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
