@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Cartes', 'url' => route('intranet.monitor.maps')], ['label' => $map->name]];
    $statuses = $map->items->map(fn ($item) => $item->device->status());
    $alarms = $map->items->flatMap(fn ($item) => $item->device->sensors)->filter(fn ($s) => $s->displayStatus()->isAlarm())
        ->sortBy(fn ($s) => $s->displayStatus()->rank());
@endphp

<x-intranet-layout :title="$map->name" :breadcrumbs="$breadcrumbs">
    @include('intranet.monitor.partials.nav')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-graphite-900">{{ $map->name }}</h1>
            @if($map->items->isNotEmpty())
                @include('intranet.monitor.partials.badge', ['status' => \App\Enums\MonitorStatus::worst($statuses)])
            @endif
        </div>
        <div class="flex flex-wrap gap-2" @unless($editing) data-auto-refresh data-interval="60000" @endunless>
            @if($editing)
                <a href="{{ route('intranet.monitor.maps.show', $map) }}" class="bouton-secondaire">Terminer la modification</a>
            @else
                <a href="{{ route('intranet.monitor.maps.screen', $map) }}" target="_blank" rel="noopener" class="bouton-primaire">Écran mural</a>
                @can('monitor.manage')
                    <a href="{{ route('intranet.monitor.maps.show', [$map, 'edition' => 1]) }}" class="bouton-secondaire">Modifier la carte</a>
                @endcan
                <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">Suspendre l’actualisation</button>
                <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('intranet.monitor.maps.update', $map) }}" enctype="multipart/form-data" id="map-form">
        @csrf
        @method('PUT')
        @if($editing)
            <div class="carte mb-4 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5">
                <label class="block">
                    <span class="etiquette">Nom</span>
                    <input name="name" value="{{ old('name', $map->name) }}" required maxlength="120" class="champ">
                </label>
                <label class="block">
                    <span class="etiquette">Image de fond (plan, schéma, 20 Mo max.)</span>
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
                <label class="block">
                    <span class="etiquette">Ou tout un groupe</span>
                    <select name="add_group_id" class="champ">
                        <option value="">-</option>
                        @foreach($groups as $id => $path)
                            <option value="{{ $id }}">{{ $path }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex flex-wrap items-end gap-3">
                    <button class="bouton-primaire">Enregistrer</button>
                    @if($map->background_path)
                        <label class="flex items-center gap-2 text-sm text-graphite-700"><input type="checkbox" name="remove_background" value="1" class="rounded border-graphite-300"> Retirer le fond</label>
                    @endif
                </div>
                <p class="text-sm text-graphite-600 sm:col-span-2 lg:col-span-5">
                    Faites glisser les équipements à leur place : chaque déplacement est enregistré aussitôt.
                    « Enregistrer » sert au nom, au plan de fond et aux ajouts ; les nouveaux équipements arrivent en bas de la carte.
                    La croix d’un équipement le retire de la carte. Les lignes relient chaque équipement à celui dont il dépend.
                    <span class="ml-1 font-semibold" data-save-status role="status" aria-live="polite"></span>
                </p>
            </div>
        @else
            <input type="hidden" name="name" value="{{ $map->name }}">
        @endif

        @if($map->items->isEmpty() && ! $editing)
            <div class="carte p-6 text-sm text-graphite-700">
                <p class="font-semibold text-graphite-900">Cette carte est vide</p>
                @can('monitor.manage')
                    <p class="mt-1">Ajoutez un plan de fond (photo du site, schéma du réseau) puis placez-y les équipements.</p>
                    <a href="{{ route('intranet.monitor.maps.show', [$map, 'edition' => 1]) }}" class="bouton-primaire mt-4 inline-flex">Modifier la carte</a>
                @endcan
            </div>
        @else
            <div class="grid gap-4 {{ $editing ? '' : 'xl:grid-cols-[minmax(0,1fr)_18rem]' }}">
                @include('intranet.monitor.partials.map-canvas', ['map' => $map, 'editing' => $editing])
                @unless($editing)
                    <aside class="carte self-start overflow-hidden" aria-label="Alarmes de la carte">
                        <h2 class="border-b border-graphite-100 px-4 py-3 text-sm font-semibold text-graphite-900">Alarmes sur cette carte ({{ $alarms->count() }})</h2>
                        <ul class="max-h-[32rem] divide-y divide-graphite-100 overflow-y-auto text-sm">
                            @forelse($alarms as $sensor)
                                <li class="px-4 py-2.5">
                                    <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" class="block hover:text-nere-700">
                                        <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $sensor->displayStatus()->color() }}"></span><span class="font-medium">{{ $sensor->device->name }}</span></span>
                                        <span class="block pl-4.5 text-xs text-graphite-600">{{ $sensor->name }} : {{ $sensor->last_message }}</span>
                                    </a>
                                </li>
                            @empty
                                <li class="px-4 py-3 text-graphite-600">Tout va bien sur cette carte.</li>
                            @endforelse
                        </ul>
                    </aside>
                @endunless
            </div>
        @endif
    </form>

    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-graphite-600" aria-label="Légende">
        @foreach([\App\Enums\MonitorStatus::Down, \App\Enums\MonitorStatus::Warning, \App\Enums\MonitorStatus::Up, \App\Enums\MonitorStatus::Paused] as $status)
            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full {{ $status->color() }}"></span>{{ $status->label() }}</span>
        @endforeach
        <span class="flex items-center gap-1.5"><span class="inline-block w-5 border-t-2 border-dashed border-red-600"></span>lien vers un équipement en panne</span>
        <span>· Survolez un équipement pour voir ses capteurs.</span>
    </div>

    @if($editing)
        {{-- Formulaires des croix « retirer » (hors du formulaire de la carte). --}}
        @foreach($map->items as $item)
            <form method="POST" action="{{ route('intranet.monitor.maps.items.destroy', [$map, $item->device]) }}" id="retirer-{{ $item->device_id }}" class="hidden"
                  onsubmit="return confirm('Retirer {{ addslashes($item->device->name) }} de la carte ?')">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
        <form method="POST" action="{{ route('intranet.monitor.maps.destroy', $map) }}" class="mt-6" onsubmit="return confirm('Supprimer cette carte ?')">
            @csrf
            @method('DELETE')
            <button class="bouton-danger">Supprimer la carte</button>
        </form>
        <script>
            (() => {
                const canvas = document.querySelector('[data-map-canvas]');
                const status = document.querySelector('[data-save-status]');
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const save = (id, x, y) => {
                    status.textContent = 'Enregistrement…';
                    status.className = 'ml-1 font-semibold text-graphite-500';
                    fetch(@js(route('intranet.monitor.maps.items.move', [$map, '__ID__'])).replace('__ID__', id), {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ x, y }),
                    }).then((response) => {
                        if (! response.ok) throw new Error();
                        status.textContent = 'Position enregistrée.';
                        status.className = 'ml-1 font-semibold text-green-700';
                    }).catch(() => {
                        status.textContent = 'Échec de l’enregistrement : utilisez « Enregistrer ».';
                        status.className = 'ml-1 font-semibold text-red-700';
                    });
                };
                canvas.querySelectorAll('[data-map-item]').forEach((item) => {
                    item.addEventListener('pointerdown', (start) => {
                        start.preventDefault();
                        item.setPointerCapture(start.pointerId);
                        const id = item.dataset.device;
                        let moved = false;
                        const move = (event) => {
                            const box = canvas.getBoundingClientRect();
                            // Grille magnétique de 1 % pour aligner proprement les équipements.
                            const x = Math.min(100, Math.max(0, Math.round((event.clientX - box.left) / box.width * 100)));
                            const y = Math.min(100, Math.max(0, Math.round((event.clientY - box.top) / box.height * 100)));
                            moved = true;
                            item.style.left = x + '%';
                            item.style.top = y + '%';
                            item.querySelector('[data-x]').value = x;
                            item.querySelector('[data-y]').value = y;
                            // Les lignes de dépendance suivent l'équipement déplacé.
                            canvas.querySelectorAll(`[data-line-from="${id}"]`).forEach((line) => { line.setAttribute('x1', x); line.setAttribute('y1', y); });
                            canvas.querySelectorAll(`[data-line-to="${id}"]`).forEach((line) => { line.setAttribute('x2', x); line.setAttribute('y2', y); });
                        };
                        item.addEventListener('pointermove', move);
                        item.addEventListener('pointerup', () => {
                            item.removeEventListener('pointermove', move);
                            if (moved) {
                                save(id, Number(item.querySelector('[data-x]').value), Number(item.querySelector('[data-y]').value));
                            }
                        }, { once: true });
                    });
                });
            })();
        </script>
    @endif
</x-intranet-layout>
