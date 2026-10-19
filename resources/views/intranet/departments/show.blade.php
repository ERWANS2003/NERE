@php
    $breadcrumbs = [
        ['label' => 'Départements', 'url' => route('intranet.departments.index')],
        ['label' => $department->name],
    ];
    $accent = $department->accentColor();
@endphp

<x-intranet-layout :title="$department->name" :breadcrumbs="$breadcrumbs">

    {{-- ============================================================
         Hero banner : illustration + catégorie + titre + description
    ============================================================ --}}
    <div class="relative overflow-hidden bg-white rounded-xl border border-graphite-200 mb-6">
        {{-- Bande d'accent à la couleur du département --}}
        <div class="absolute inset-x-0 top-0 h-1.5" style="background-color: {{ $accent }}"></div>

        <div class="p-5 sm:p-8">
            <div class="flex flex-col items-start gap-4 sm:flex-row sm:gap-6">
                <div class="flex-shrink-0">
                    @if($department->image_url)
                        <img
                            src="{{ $department->image_url }}"
                            alt="{{ $department->name }}"
                            class="w-24 h-24 sm:w-32 sm:h-32 object-cover rounded-xl"
                        >
                    @else
                        <div class="w-24 h-24 sm:w-32 sm:h-32 bg-gradient-to-br from-graphite-100 to-graphite-200 rounded-xl flex items-center justify-center">
                            <svg class="w-16 h-16 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <span class="inline-block px-3 py-1 bg-nere-50 text-nere-700 rounded-full text-xs font-bold uppercase tracking-wider mb-3">
                        {{ $department->tag ?? $department->code }}
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-graphite-900 mb-3 break-words">
                        {{ $department->name }}
                    </h1>
                    @if($department->description)
                        <p class="text-graphite-600 leading-relaxed">{{ $department->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Onglets : Services / Formulaires / Équipe (Alpine, sans rechargement)
    ============================================================ --}}
    <div x-data="{ onglet: 'services' }">

        <div class="border-b border-graphite-200 mb-6" role="tablist" aria-label="Sections du département">
            <nav class="flex gap-1 -mb-px overflow-x-auto">
                @php
                    $onglets = [
                        'services' => ['label' => 'Services', 'count' => $services->count()],
                        'formulaires' => ['label' => 'Formulaires', 'count' => $forms->count()],
                        'equipe' => ['label' => 'Équipe', 'count' => $memberCount],
                    ];
                @endphp
                @foreach($onglets as $cle => $meta)
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="onglet === '{{ $cle }}'"
                        @click="onglet = '{{ $cle }}'"
                        class="flex items-center gap-2 whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition"
                        :class="onglet === '{{ $cle }}'
                            ? 'border-nere-600 text-nere-700'
                            : 'border-transparent text-graphite-500 hover:text-graphite-800 hover:border-graphite-300'"
                    >
                        {{ $meta['label'] }}
                        <span class="rounded-full bg-graphite-100 px-2 py-0.5 text-xs text-graphite-600">{{ $meta['count'] }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- ---------- Onglet Services ---------- --}}
        <div x-show="onglet === 'services'" role="tabpanel">
            @if($services->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($services as $service)
                        {{-- Sans URL renseignée, le service n'est pas encore branché : carte non cliquable. --}}
                        @php $actif = filled($service->link_url); @endphp
                        <{{ $actif ? 'a' : 'div' }}
                            @if($actif) href="{{ $service->link_url }}" @endif
                            class="bg-white rounded-lg border border-graphite-200 p-6 transition group {{ $actif ? 'hover:border-nere-500 hover:shadow-md' : 'opacity-75' }}"
                        >
                            <div class="w-12 h-12 bg-nere-50 rounded-lg flex items-center justify-center mb-4 group-hover:bg-nere-100 transition">
                                <svg class="w-6 h-6 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-graphite-900 mb-2 group-hover:text-nere-600 transition">
                                {{ $service->name }}
                            </h3>
                            <p class="text-sm text-graphite-600 line-clamp-2">{{ $service->description }}</p>

                            @if($actif)
                                <div class="flex items-center gap-2 mt-4 text-nere-600 text-sm font-medium">
                                    <span>Ouvrir</span>
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            @else
                                <div class="mt-4 inline-block rounded bg-graphite-100 px-2 py-0.5 text-xs font-medium text-graphite-600">
                                    Bientôt disponible
                                </div>
                            @endif
                        </{{ $actif ? 'a' : 'div' }}>
                    @endforeach
                </div>
            @else
                <x-departments.empty-tab message="Aucun service disponible pour ce département." />
            @endif
        </div>

        {{-- ---------- Onglet Formulaires ---------- --}}
        <div x-show="onglet === 'formulaires'" role="tabpanel" x-cloak>
            @if($forms->count() > 0)
                <div class="bg-white rounded-lg border border-graphite-200 divide-y divide-graphite-100">
                    @foreach($forms as $form)
                        <div class="flex items-center gap-4 p-5">
                            <div class="w-10 h-10 shrink-0 bg-or-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-or-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-graphite-900">{{ $form->name }}</h3>
                                @if($form->description)
                                    <p class="text-sm text-graphite-500 line-clamp-1">{{ $form->description }}</p>
                                @endif
                            </div>
                            @php
                                $formUrl = $department->code === 'RH' && $form->name === 'Demande de congé'
                                    ? route('intranet.time-off.create')
                                    : ($form->form_schema['fields'] ?? []
                                        ? route('intranet.forms.show', $form)
                                        : null);
                            @endphp
                            @if($formUrl)
                                <a href="{{ $formUrl }}" class="shrink-0 rounded-lg bg-nere-700 px-3 py-2 text-sm font-semibold text-white hover:bg-nere-800">
                                    Faire une demande
                                </a>
                            @else
                                <span class="shrink-0 rounded bg-graphite-100 px-2.5 py-1 text-xs font-medium text-graphite-600">
                                    Bientôt disponible
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-departments.empty-tab message="Aucun formulaire disponible pour ce département." />
            @endif
        </div>

        {{-- ---------- Onglet Équipe ---------- --}}
        <div x-show="onglet === 'equipe'" role="tabpanel" x-cloak>
            @if($team->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($team as $membre)
                        <div class="bg-white rounded-lg border border-graphite-200 p-4 flex items-center gap-4 {{ $membre['isHead'] ? 'ring-1 ring-nere-200' : '' }}">
                            <x-avatar :user="$membre['user']" size="lg" />
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-graphite-900 truncate">{{ $membre['user']->name }}</div>
                                <div class="text-sm text-graphite-500 truncate">{{ $membre['user']->job_title ?? '—' }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide
                                        {{ $membre['isHead'] ? 'bg-nere-50 text-nere-700' : 'bg-graphite-100 text-graphite-600' }}">
                                        {{ $membre['roleLabel'] }}
                                    </span>
                                    @if($membre['techLevel'])
                                        <span class="rounded bg-graphite-100 px-1.5 py-0.5 text-[10px] font-medium text-graphite-600">
                                            N{{ $membre['techLevel'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <a href="{{ route('intranet.directory', ['department' => $department->id]) }}"
                       class="text-sm text-nere-600 hover:text-nere-700 font-medium">
                        Voir le département dans l'annuaire →
                    </a>
                </div>
            @else
                <x-departments.empty-tab message="Aucun membre assigné à ce département." />
            @endif
        </div>

    </div>

</x-intranet-layout>
