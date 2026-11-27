{{-- Arborescence : un groupe repliable, ses équipements, puis ses sous-groupes. --}}
@php
    $children = $groups->where('parent_id', $group->id);
    $ids = [$group->id];
    for ($i = 0; $i < count($ids); $i++) {
        array_push($ids, ...$groups->where('parent_id', $ids[$i])->pluck('id')->all());
    }
    $allDevices = collect($ids)->flatMap(fn ($id) => $allDevicesByGroup[$id] ?? []);
    $visibleCount = collect($ids)->sum(fn ($id) => count($devicesByGroup[$id] ?? []));
    $groupStatus = \App\Enums\MonitorStatus::worst($allDevices->map->status());
    $statusCounts = $allDevices->map->status()->countBy(fn ($s) => $s->value);
    $indent = 1.25 + $depth * 1.5;
@endphp
@if(! $filter || $visibleCount > 0)
    <section class="{{ $depth === 0 ? 'carte mb-4' : 'border-t border-graphite-100' }}" aria-label="Groupe {{ $group->name }}"
             x-data="{ open: (() => { try { return localStorage.getItem('supervision-groupe-{{ $group->id }}') !== '0' } catch (e) { return true } })() }"
             x-init="$watch('open', v => { try { localStorage.setItem('supervision-groupe-{{ $group->id }}', v ? '1' : '0') } catch (e) {} })"
             x-show="matches($el)">
        <div class="flex flex-wrap items-center gap-3 bg-graphite-50 py-2.5 pr-5 {{ $depth === 0 ? 'rounded-t-[inherit]' : '' }}" style="padding-left: {{ $indent }}rem">
            <button type="button" @click="open = ! open" :aria-expanded="(open || q !== '').toString()" class="flex items-center gap-2 text-left font-semibold text-graphite-900">
                <svg class="h-4 w-4 shrink-0 text-graphite-500 transition-transform" :class="(open || q) && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @include('intranet.monitor.partials.badge', ['status' => $groupStatus])
                <span>{{ $group->name }}</span>
            </button>
            <span class="text-sm text-graphite-500">
                {{ $allDevices->count() }} équipement(s)
                @foreach(['down' => 'en panne', 'warning' => 'en avertissement', 'paused' => 'en pause'] as $value => $text)
                    @if($statusCounts[$value] ?? 0) · {{ $statusCounts[$value] }} {{ $text }} @endif
                @endforeach
            </span>
            <details class="relative ml-auto">
                <summary class="cursor-pointer list-none rounded-md px-2 py-1 text-sm font-medium text-graphite-600 hover:bg-graphite-100" aria-label="Actions du groupe {{ $group->name }}">Actions ▾</summary>
                <div class="absolute right-0 z-30 mt-1 w-56 rounded-xl border border-graphite-200 bg-white py-1 text-sm shadow-xl">
                    @can('monitor.manage')
                        <a href="{{ route('intranet.monitor.devices.create', ['group' => $group->id]) }}" class="block px-4 py-2 hover:bg-graphite-50">Ajouter un équipement</a>
                        <a href="{{ route('intranet.monitor.groups.create', ['parent' => $group->id]) }}" class="block px-4 py-2 hover:bg-graphite-50">Ajouter un sous-groupe</a>
                    @endcan
                    <a href="{{ route('intranet.monitor.reports', ['groupe' => $group->id]) }}" class="block px-4 py-2 hover:bg-graphite-50">Rapport de disponibilité</a>
                    @can('monitor.manage')
                        <a href="{{ route('intranet.monitor.groups.edit', $group) }}" class="block px-4 py-2 hover:bg-graphite-50">Réglages du groupe</a>
                    @endcan
                </div>
            </details>
        </div>
        <div x-show="open || q" x-cloak>
            @if(($devicesByGroup[$group->id] ?? collect())->isNotEmpty())
                <ul class="divide-y divide-graphite-100">
                    @foreach($devicesByGroup[$group->id] as $device)
                        @php $deviceStatus = $device->status(); @endphp
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-2.5 pr-5 transition hover:bg-graphite-50/70" style="padding-left: {{ $indent + 1.5 }}rem"
                            data-search="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($device->name.' '.$device->host.' '.$device->type->label())) }}"
                            x-show="! q || $el.dataset.search.includes(norm(q))">
                            <a href="{{ route('intranet.monitor.devices.show', $device) }}" class="flex w-72 min-w-0 items-center gap-3 hover:text-nere-700">
                                @include('intranet.monitor.partials.device-icon', ['type' => $device->type, 'status' => $deviceStatus])
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-graphite-900">{{ $device->name }}</span>
                                    <span class="block truncate font-mono text-xs text-graphite-500">{{ $device->host }}</span>
                                </span>
                            </a>
                            <div class="flex flex-1 flex-wrap gap-1.5">
                                @foreach($device->sensors as $sensor)
                                    @php $state = $sensor->displayStatus(); @endphp
                                    <a href="{{ route('intranet.monitor.sensors.show', $sensor) }}" title="{{ $state->label() }} : {{ $sensor->last_message ?? 'pas encore vérifié' }}"
                                       class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium {{ $state->soft() }} hover:shadow-sm">
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $state->dot() }}"></span>
                                        {{ $sensor->name }}
                                        @if($sensor->last_value !== null && $state !== \App\Enums\MonitorStatus::Paused)
                                            <span class="font-semibold tabular-nums">{{ $sensor->formatValue() }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ul>
            @elseif($children->isEmpty())
                <p class="py-3 pr-5 text-sm text-graphite-500" style="padding-left: {{ $indent + 1.5 }}rem">
                    Aucun équipement.
                    @can('monitor.manage')
                        <a href="{{ route('intranet.monitor.devices.create', ['group' => $group->id]) }}" class="font-medium text-nere-700 hover:underline">En ajouter un</a>
                        ou lancer la <a href="{{ route('intranet.monitor.discovery') }}" class="font-medium text-nere-700 hover:underline">découverte automatique</a>.
                    @endcan
                </p>
            @endif
            @foreach($children as $child)
                @include('intranet.monitor.partials.group-tree', ['group' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    </section>
@endif
