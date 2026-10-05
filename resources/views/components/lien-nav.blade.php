@props(['active' => false])

<a {{ $attributes->merge([
        'class' => 'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition focus:outline-none '
            .($active
                ? 'border-or-400 text-white'
                : 'border-transparent text-graphite-400 hover:border-graphite-600 hover:text-white'),
    ]) }}>
    {{ $slot }}
</a>