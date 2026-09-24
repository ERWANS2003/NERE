@props(['title' => null, 'description' => null])

<div class="bg-gradient-to-r from-charcoal-900 to-charcoal-800 border-b-2 border-gold-500 px-6 py-4 mb-6 rounded-lg">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            @if($title)
                <h1 class="text-2xl font-bold text-gold-400 mb-1">{{ $title }}</h1>
            @endif
            @if($description)
                <p class="text-charcoal-400">{{ $description }}</p>
            @endif
        </div>
        
        <div class="flex items-center gap-2">
            {{ $slot }}
        </div>
    </div>
</div>
