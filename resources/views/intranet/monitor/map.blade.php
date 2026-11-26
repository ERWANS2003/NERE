@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Cartes', 'url' => route('intranet.monitor.maps')], ['label' => $map->name]];
@endphp

<x-intranet-layout :title="$map->name" :breadcrumbs="$breadcrumbs">
    @include('intranet.monitor.partials.nav')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-bold text-graphite-900">{{ $map->name }}</h1>
        <div class="flex gap-2" @unless($editing) data-auto-refresh data-interval="60000" @endunless>
            @if($editing)
                <a href="{{ route('intranet.monitor.maps.show', $map) }}" class="bouton-secondaire">Terminer la modification</a>
            @else
                <a href="{{ route('intranet.monitor.maps.show', [$map, 'edition' => 1]) }}" class="bouton-secondaire">Modifier la carte</a>
                <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
                <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('intranet.monitor.maps.update', $map) }}" enctype="multipart/form-data" id="map-form">
        @csrf
        @method('PUT')
        @if($editing)
            <div class="carte mb-4 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="etiquette">Nom</span>
                    <input name="name" value="{{ old('name', $map->name) }}" required maxlength="120" class="champ">
                </label>
                <label class="block">
                    <span class="etiquette">Image de fond (plan, schéma)</span>
                    <input type="file" name="background" accept="image/png,image/jpeg,image/webp" class="champ">
                    @error('background')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block">
                    <span class="etiquette">Ajouter un équipement</span>
                    <select name="add_device_id" class="champ">
                        <option value="">-</option>
                        @foreach($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->name }} ({{ $device->host }})</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex flex-wrap items-end gap-3">
                    <button class="bouton-primaire">Enregistrer</button>
                    @if($map->background_path)
                        <label class="flex items-center gap-2 text-sm text-graphite-700"><input type="checkbox" name="remove_background" value="1" class="rounded border-graphite-300"> Retirer le fond</label>
                    @endif
                </div>
                <p class="text-sm text-graphite-600 sm:col-span-2 lg:col-span-4">Faites glisser les équipements à leur place, puis enregistrez.</p>
            </div>
        @else
            <input type="hidden" name="name" value="{{ $map->name }}">
        @endif

        <div class="carte relative w-full select-none overflow-hidden {{ $map->background_path ? '' : 'bg-graphite-50' }}" style="aspect-ratio: 16 / 9" data-map-canvas>
            @if($map->background_path)
                <img src="{{ route('intranet.monitor.maps.background', $map) }}" alt="" class="absolute inset-0 h-full w-full object-contain" draggable="false">
            @endif
            @foreach($map->items as $item)
                @php $status = $item->device->status(); @endphp
                <div class="absolute -translate-x-1/2 -translate-y-1/2 {{ $editing ? 'cursor-move' : '' }}" style="left: {{ $item->x }}%; top: {{ $item->y }}%" data-map-item>
                    @if($editing)
                        <input type="hidden" name="items[{{ $item->device_id }}][x]" value="{{ $item->x }}" data-x>
                        <input type="hidden" name="items[{{ $item->device_id }}][y]" value="{{ $item->y }}" data-y>
                    @endif
                    <a @unless($editing) href="{{ route('intranet.monitor.devices.show', $item->device) }}" @endunless title="{{ $status->label() }}"
                       class="flex items-center gap-1.5 whitespace-nowrap rounded-full bg-white/90 py-1 pl-1 pr-3 text-xs font-semibold text-graphite-900 shadow">
                        <span class="h-4 w-4 rounded-full {{ $status->color() }} {{ $status === \App\Enums\MonitorStatus::Down ? 'animate-pulse' : '' }}" aria-hidden="true"></span>
                        {{ $item->device->name }}
                    </a>
                </div>
            @endforeach
        </div>
    </form>

    @if($editing)
        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            @foreach($map->items as $item)
                <form method="POST" action="{{ route('intranet.monitor.maps.items.destroy', [$map, $item->device]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="rounded border border-graphite-300 px-2 py-1 hover:bg-graphite-50">Retirer {{ $item->device->name }}</button>
                </form>
            @endforeach
        </div>
        <form method="POST" action="{{ route('intranet.monitor.maps.destroy', $map) }}" class="mt-6" onsubmit="return confirm('Supprimer cette carte ?')">
            @csrf
            @method('DELETE')
            <button class="bouton-danger">Supprimer la carte</button>
        </form>
        <script>
            (() => {
                const canvas = document.querySelector('[data-map-canvas]');
                canvas.querySelectorAll('[data-map-item]').forEach((item) => {
                    item.addEventListener('pointerdown', (start) => {
                        start.preventDefault();
                        item.setPointerCapture(start.pointerId);
                        const move = (event) => {
                            const box = canvas.getBoundingClientRect();
                            const x = Math.min(100, Math.max(0, (event.clientX - box.left) / box.width * 100)).toFixed(2);
                            const y = Math.min(100, Math.max(0, (event.clientY - box.top) / box.height * 100)).toFixed(2);
                            item.style.left = x + '%';
                            item.style.top = y + '%';
                            item.querySelector('[data-x]').value = x;
                            item.querySelector('[data-y]').value = y;
                        };
                        item.addEventListener('pointermove', move);
                        item.addEventListener('pointerup', () => item.removeEventListener('pointermove', move), { once: true });
                    });
                });
            })();
        </script>
    @endif
</x-intranet-layout>
