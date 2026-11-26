{{-- Arborescence : un groupe, ses équipements, puis ses sous-groupes. --}}
@php
    $children = $groups->where('parent_id', $group->id);
    $ids = [$group->id];
    for ($i = 0; $i < count($ids); $i++) {
        array_push($ids, ...$groups->where('parent_id', $ids[$i])->pluck('id')->all());
    }
    $allDevices = collect($ids)->flatMap(fn ($id) => $allDevicesByGroup[$id] ?? []);
    $visibleCount = collect($ids)->sum(fn ($id) => count($devicesByGroup[$id] ?? []));
    $groupStatus = \App\Enums\MonitorStatus::worst($allDevices->map->status());
@endphp
@if(! $filter || $visibleCount > 0)
    <section class="{{ $depth === 0 ? 'carte mb-4 overflow-hidden' : 'border-t border-graphite-100' }}" aria-label="Groupe {{ $group->name }}">
        <h{{ min(6, $depth + 2) }} class="flex flex-wrap items-center gap-3 bg-graphite-50 py-2.5 pr-5 font-semibold text-graphite-900" style="padding-left: {{ 1.25 + $depth * 1.5 }}rem">
            @include('intranet.monitor.partials.badge', ['status' => $groupStatus])
            <span>{{ $group->name }}</span>
            <span class="text-sm font-normal text-graphite-500">{{ $allDevices->count() }} équipement(s)</span>
            <span class="ml-auto flex gap-3 text-sm font-medium">
                <a href="{{ route('intranet.monitor.devices.create', ['group' => $group->id]) }}" class="text-nere-700 hover:underline">+ Équipement</a>
                <a href="{{ route('intranet.monitor.groups.create', ['parent' => $group->id]) }}" class="text-nere-700 hover:underline">+ Sous-groupe</a>
                <a href="{{ route('intranet.monitor.groups.edit', $group) }}" class="text-graphite-600 hover:underline">Réglages</a>
            </span>
        </h{{ min(6, $depth + 2) }}>
        @if(($devicesByGroup[$group->id] ?? collect())->isNotEmpty())
            <ul class="divide-y divide-graphite-100">
                @foreach($devicesByGroup[$group->id] as $device)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-2.5 pr-5" style="padding-left: {{ 1.25 + $depth * 1.5 }}rem">
                        <div class="w-64 min-w-0">
                            <a href="{{ route('intranet.monitor.devices.show', $device) }}" class="flex items-center gap-2 font-medium text-graphite-900 hover:text-nere-700">
                                <span class="h-3 w-3 shrink-0 rounded-full {{ $device->status()->color() }}" aria-hidden="true"></span>
                                <span class="truncate">{{ $device->name }}</span>
                            </a>
                            <p class="truncate pl-5 text-xs text-graphite-500">{{ $device->type->label() }} · {{ $device->host }}</p>
                        </div>
                        <div class="flex flex-1 flex-wrap gap-1.5">
                            @foreach($device->sensors as $sensor)
                                @php $state = $sensor->displayStatus(); @endphp
                                <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" title="{{ $state->label() }} : {{ $sensor->last_message ?? 'pas encore vérifié' }}"
                                   class="rounded px-2 py-1 text-xs font-medium {{ $state->color() }}">
                                    {{ $sensor->name }}@if($sensor->last_value !== null && $state !== \App\Enums\MonitorStatus::Paused) · {{ $sensor->formatValue() }}@endif
                                </a>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        @foreach($children as $child)
            @include('intranet.monitor.partials.group-tree', ['group' => $child, 'depth' => $depth + 1])
        @endforeach
    </section>
@endif
