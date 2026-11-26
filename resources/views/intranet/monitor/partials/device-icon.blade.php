{{-- Icône du type d'équipement dans un rond de la couleur de son état. --}}
@php $size = $size ?? 'md'; @endphp
<span class="relative inline-flex shrink-0 items-center justify-center rounded-full {{ $status->color() }} {{ $size === 'lg' ? 'h-14 w-14' : ($size === 'sm' ? 'h-7 w-7' : 'h-9 w-9') }}"
      title="{{ $type->label() }} : {{ $status->label() }}">
    <svg class="{{ $size === 'lg' ? 'h-7 w-7' : ($size === 'sm' ? 'h-3.5 w-3.5' : 'h-4.5 w-4.5') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $type->iconPath() }}"/></svg>
</span>
