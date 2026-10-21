@props([
    'href',
    'active' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'intranet-filter-link',
        'intranet-filter-link-active' => $active,
    ]) }}
    @if($active) aria-current="page" @endif
>
    {{ $slot }}
</a>
