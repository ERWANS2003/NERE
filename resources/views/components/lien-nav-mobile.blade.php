@props(['active' => false])

<a {{ $attributes->merge([
        'class' => 'block rounded-md px-3 py-2 text-sm font-medium transition focus:outline-none '
            .($active
                ? 'bg-graphite-100 text-graphite-900'
                : 'text-graphite-600 hover:bg-graphite-100 hover:text-graphite-900'),
    ]) }}>
    {{ $slot }}
</a>