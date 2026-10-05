@props(['active' => false])

<a {{ $attributes->merge([
        'class' => 'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition focus:outline-none '
            .($active
                ? 'border-amber-600 text-graphite-900'
                : 'border-transparent text-graphite-500 hover:border-graphite-300 hover:text-graphite-800'),
    ]) }}>
    {{ $slot }}
</a>