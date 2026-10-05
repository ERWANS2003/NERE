<div x-data="{ ouvert: false }"
     x-on:keydown.escape.window="ouvert = false"
     x-id="{{ \Illuminate\Support\Js::from(['menu-'.($attributes->get('id') ?? 'defaut')]) }}"
     @class(['relative'])
     {{ $attributes }}>
    <div x-on:click="ouvert = ! ouvert">
        {{ $trigger }}
    </div>

    <div x-show="ouvert"
         x-cloak
         x-on:click.outside="ouvert = false"
         x-on:keydown.escape.window="ouvert = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;"
         @class([
            'absolute z-30 mt-2 origin-top-right rounded-md border border-graphite-200 bg-white py-1 shadow-lg',
            'right-0 w-48',
            'sm:left-0 sm:right-auto sm:origin-top-left',
         ])>
        {{ $content }}
    </div>
</div>