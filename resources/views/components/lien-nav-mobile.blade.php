@props(['active' => false])

<a {{ $attributes->merge([
        'class' => 'block rounded-md px-3 py-2 text-sm font-medium transition focus:outline-none '
            .($active
                ? 'bg-graphite-800 text-white'
                : 'text-graphite-300 hover:bg-graphite-800 hover:text-white'),
    ]) }}>
    {{ $slot }}
</a>