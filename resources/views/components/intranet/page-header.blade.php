@props([
    'title',
    'description' => null,
])

<header {{ $attributes->class('mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between') }}>
    <div class="min-w-0">
        <h1 class="text-3xl font-bold text-graphite-900">{{ $title }}</h1>
        @if($description)
            <p class="mt-2 max-w-3xl text-graphite-600">{{ $description }}</p>
        @endif
    </div>

    @if(isset($actions) && $actions->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</header>
