@php
    $breadcrumbs = [
        ['label' => 'Calendrier'],
    ];
@endphp

<x-intranet-layout title="Calendrier" :breadcrumbs="$breadcrumbs">

<div
    x-data="window.IntranetModules.calendarComponent({
        eventsUrl: @js(route('intranet.calendar.events', [], false)),
        initialDate: @js($initialDate),
    })"
    @keydown.escape.window="close()"
>

    {{-- En-tête --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Calendrier</h1>
            <p class="text-graphite-600">Jours fériés du Burkina Faso, événements globaux et événements de vos départements.</p>
        </div>
        @if($canManageDepartmentEvents)
            <a href="{{ route('intranet.calendar.department-events.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-nere-600 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-700">
                <x-icone nom="calendar-plus" />
                Gérer mes événements départementaux
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

        {{-- Calendrier --}}
        <div class="xl:col-span-3">
            <div class="bg-white rounded-lg border border-graphite-200 p-4 sm:p-6">

                <div x-show="loadError" x-cloak style="display: none;"
                     class="mb-4 flex items-center justify-between gap-4 rounded-lg bg-nere-50 border border-nere-200 px-4 py-3 text-sm text-nere-800" role="alert">
                    <span>Impossible de charger les événements.</span>
                    <button type="button" @click="refresh()" class="font-semibold underline">Réessayer</button>
                </div>

                <div x-ref="calendar" aria-label="Calendrier des événements"></div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="space-y-6">

            {{-- À venir --}}
            <div class="bg-white rounded-lg border border-graphite-200 p-6">
                <h2 class="text-lg font-bold text-graphite-900 mb-4">À venir</h2>

                @forelse($upcoming as $event)
                    @php
                        $props = $event['extendedProps'];
                        $date = \Illuminate\Support\Carbon::parse($props['date'])->locale('fr');
                    @endphp
                    <a href="{{ route('intranet.calendar', ['date' => $props['date']]) }}"
                       class="flex gap-3 py-3 first:pt-0 last:pb-0 border-b border-graphite-100 last:border-0 hover:bg-graphite-50 -mx-2 px-2 rounded transition">
                        <div class="flex-shrink-0 w-12 text-center bg-graphite-50 rounded-lg py-1">
                            <div class="text-[10px] text-graphite-500 uppercase">{{ $date->isoFormat('MMM') }}</div>
                            <div class="text-xl font-bold text-graphite-900 leading-none">{{ $date->format('d') }}</div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                  style="background-color: {{ $props['color'] }}; color: {{ $props['textColor'] }}">
                                {{ $props['typeLabel'] }}
                            </span>
                            <div class="mt-1 text-sm font-medium text-graphite-900 leading-snug">{{ $event['title'] }}</div>
                            <div class="text-xs text-graphite-500">
                                {{ $date->isoFormat('dddd') }}@if($props['time']) · {{ $props['time'] }}@endif
                            </div>
                            <div class="text-xs text-graphite-500">
                                {{ $props['scope'] === 'department' ? 'Agenda départemental' . ($props['department'] ? ' · ' . $props['department'] : '') : 'Agenda global' }}
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-graphite-500 text-center py-4">Aucun événement à venir</p>
                @endforelse
            </div>

            <p class="text-xs text-graphite-500">
                Les fêtes liées au calendrier lunaire ne sont pas calculées automatiquement ; leurs dates peuvent être ajoutées après l’annonce officielle.
            </p>

            {{-- Légende --}}
            <div class="bg-white rounded-lg border border-graphite-200 p-6">
                <h2 class="text-lg font-bold text-graphite-900 mb-4">Légende</h2>
                <ul class="space-y-2">
                    @foreach($types as $type)
                        <li class="flex items-center gap-2 text-sm text-graphite-700">
                            <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background-color: {{ $type['color'] }}" aria-hidden="true"></span>
                            {{ $type['name'] }}
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>

    {{-- Détail d'un événement --}}
    <div
        x-show="selected"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="event-modal-title"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-graphite-900/50 backdrop-blur-sm" @click="close()"></div>

        <div class="relative w-full max-w-md rounded-xl bg-white shadow-2xl" x-trap.noscroll="selected">
            <template x-if="selected">
                <div>
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <span class="inline-block rounded px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
                                  :style="`background-color: ${selected.color}; color: ${selected.textColor}`"
                                  x-text="selected.typeLabel"></span>
                            <button type="button" @click="close()" class="p-1 -m-1 rounded text-graphite-500 hover:bg-graphite-100" aria-label="Fermer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <h2 id="event-modal-title" class="mt-3 text-xl font-bold text-graphite-900" x-text="selected.title"></h2>

                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex gap-2">
                                <dt class="text-graphite-500 w-24 shrink-0">Date</dt>
                                <dd class="text-graphite-900" x-text="formatDate(selected.date)"></dd>
                            </div>
                            <div class="flex gap-2" x-show="selected.time">
                                <dt class="text-graphite-500 w-24 shrink-0">Heure</dt>
                                <dd class="text-graphite-900" x-text="selected.time"></dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="text-graphite-500 w-24 shrink-0">Agenda</dt>
                                <dd class="text-graphite-900" x-text="selected.scope === 'department' ? `Département · ${selected.department ?? ''}` : 'Global'"></dd>
                            </div>
                        </dl>

                        <p class="mt-4 text-sm text-graphite-700" x-show="selected.description" x-text="selected.description"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

</x-intranet-layout>
