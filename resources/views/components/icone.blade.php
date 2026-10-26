@props([
    'nom',
])

{{--
    Icône Lucide servie localement (voir App\Support\Icons). Sans classe de
    taille, elle suit la taille du texte (1em), comme l'ancienne police
    Font Awesome qu'elle remplace.
--}}
<svg {{ $attributes->merge(['class' => 'inline-block size-[1em] shrink-0 align-[-0.125em]']) }}
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="2"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true"
     focusable="false">{!! \App\Support\Icons::inner($nom) !!}</svg>
