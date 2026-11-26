{{-- Dessin d'une carte : fond, lignes de dépendance, repères des équipements. --}}
@php
    $editing ??= false;
    $positions = $map->items->keyBy('device_id');
    $grid = $editing || ! $map->background_path;
@endphp
<div class="relative w-full select-none rounded-xl border {{ ($dark ?? false) ? 'border-graphite-700 bg-graphite-800' : 'border-graphite-200 bg-white' }}"
     style="aspect-ratio: 16 / 9; {{ $grid ? 'background-image: radial-gradient(circle, '.(($dark ?? false) ? '#475569' : '#cbd5e1').' 1px, transparent 1px); background-size: 24px 24px;' : '' }}"
     data-map-canvas>
    @if($map->background_path)
        <img src="{{ route('intranet.monitor.maps.background', $map) }}" alt="Plan de fond de la carte {{ $map->name }}" class="absolute inset-0 h-full w-full rounded-xl object-contain {{ $editing ? 'opacity-70' : '' }}" draggable="false">
    @endif

    {{-- Lignes entre un équipement et celui dont il dépend : rouge si le parent est en panne. --}}
    <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        @foreach($map->items as $item)
            @php $parent = $item->device->parent_device_id ? $positions->get($item->device->parent_device_id) : null; @endphp
            @if($parent)
                @php $parentDown = in_array($parent->device->status(), [\App\Enums\MonitorStatus::Down, \App\Enums\MonitorStatus::DownAcknowledged], true); @endphp
                <line x1="{{ $parent->x }}" y1="{{ $parent->y }}" x2="{{ $item->x }}" y2="{{ $item->y }}"
                      data-line-from="{{ $parent->device_id }}" data-line-to="{{ $item->device_id }}"
                      stroke="{{ $parentDown ? '#dc2626' : (($dark ?? false) ? '#64748b' : '#94a3b8') }}" stroke-width="2" vector-effect="non-scaling-stroke"
                      @if($parentDown) stroke-dasharray="6 4" @endif />
            @endif
        @endforeach
    </svg>

    @foreach($map->items as $item)
        @php
            $device = $item->device;
            $status = $device->status();
            $problems = $device->sensors->filter(fn ($s) => $s->displayStatus()->isAlarm());
        @endphp
        <div class="group absolute z-10 -translate-x-1/2 -translate-y-1/2 hover:z-40 focus-within:z-40 {{ $editing ? 'cursor-move' : '' }}" style="left: {{ $item->x }}%; top: {{ $item->y }}%"
             data-map-item data-device="{{ $device->id }}">
            @if($editing)
                <input type="hidden" name="items[{{ $device->id }}][x]" value="{{ $item->x }}" data-x>
                <input type="hidden" name="items[{{ $device->id }}][y]" value="{{ $item->y }}" data-y>
            @endif
            <a @unless($editing) href="{{ route('intranet.monitor.devices.show', $device) }}" @endunless
               class="flex flex-col items-center gap-1 focus:outline-none" aria-label="{{ $device->name }} : {{ $status->label() }}">
                <span class="relative flex h-10 w-10 items-center justify-center rounded-full shadow-lg ring-4 {{ $status->color() }} {{ ($dark ?? false) ? 'ring-graphite-900/60' : 'ring-white/80' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $device->type->iconPath() }}"/></svg>
                    @if($status === \App\Enums\MonitorStatus::Down)
                        <span class="absolute inset-0 animate-ping rounded-full bg-red-500 opacity-40" aria-hidden="true"></span>
                    @endif
                </span>
                <span class="max-w-40 truncate rounded px-1.5 py-0.5 text-xs font-semibold shadow-sm {{ ($dark ?? false) ? 'bg-graphite-900/80 text-white' : 'bg-white/90 text-graphite-900' }}">{{ $device->name }}</span>
            </a>
            @unless($editing)
                {{-- Détail au survol ou au focus : chaque capteur avec son état. --}}
                <div class="pointer-events-none invisible absolute {{ $item->y < 40 ? 'top-full mt-2' : 'bottom-full mb-2' }} left-1/2 z-30 w-64 -translate-x-1/2 rounded-xl border border-graphite-200 bg-white p-3 text-left text-xs text-graphite-800 opacity-0 shadow-xl transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                    <p class="font-semibold text-graphite-900">{{ $device->name }}</p>
                    <p class="mb-2 text-graphite-500">{{ $device->type->label() }} · {{ $device->host }}</p>
                    <ul class="space-y-1">
                        @foreach($device->sensors as $sensor)
                            <li class="flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-1.5"><span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $sensor->displayStatus()->color() }}"></span><span class="truncate">{{ $sensor->name }}</span></span>
                                <span class="shrink-0 font-medium">{{ $sensor->formatValue() }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($problems->isNotEmpty())
                        <p class="mt-2 border-t border-graphite-100 pt-2 text-red-700">{{ $problems->first()->last_message }}</p>
                    @endif
                </div>
            @endunless
        </div>
    @endforeach
</div>
