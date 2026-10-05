@props([
    'name',
    'maxWidth' => '2xl',
])

@php
    $classes = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth];
@endphp

<div x-data="{ ouvert: false }"
     x-on:keydown.escape.window="ouvert = false"
     x-id="['modale-'.$name]"
     x-show="ouvert"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div x-show="ouvert"
         x-on:click="ouvert = false"
         x-transition.opacity
         class="fixed inset-0 bg-graphite-950/50"></div>

    <div x-show="ouvert"
         x-trap.noscroll="ouvert"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="relative w-full {{ $classes }} rounded-lg bg-white p-6 shadow-xl"
         role="dialog"
         aria-modal="true">
        {{ $slot }}
    </div>
</div>