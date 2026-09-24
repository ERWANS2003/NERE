@props(['title' => null, 'subtitle' => null, 'action' => '#', 'method' => 'POST'])

<x-mining-card accent="gold" class="max-w-2xl">
    @if($title)
        <div class="mb-6 pb-6 border-b border-charcoal-700">
            <h2 class="text-2xl font-bold text-gold-400 mb-2">{{ $title }}</h2>
            @if($subtitle)
                <p class="text-charcoal-400">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <form action="{{ $action }}" method="{{ $method }}" class="space-y-6">
        @csrf
        @if($method !== 'GET')
            @method($method)
        @endif

        {{ $slot }}

        <div class="flex gap-3 pt-6 border-t border-charcoal-700">
            <a href="javascript:history.back()" class="flex-1">
                <x-mining-button variant="secondary" size="md" class="w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Annuler</span>
                </x-mining-button>
            </a>
            <button type="submit" class="flex-1">
                <x-mining-button variant="gold" size="md" class="w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Envoyer</span>
                </x-mining-button>
            </button>
        </div>
    </form>
</x-mining-card>
