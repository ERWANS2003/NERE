@php
    $breadcrumbs = [
        ['label' => 'Organigramme'],
    ];

    // Index code => données, consommé par le modal de détail (Alpine).
    $index = collect($branches)->flatMap(fn ($b) => $b['departments'])->keyBy('code')->all();
@endphp

@push('styles')
<style>
    /* Lignes de connexion : trait vertical sous la Direction, barre horizontale
       au-dessus des branches (grand écran), traits verticaux entre départements. */
    .org-line { background-color: #b1b8be; }

    .org-branches { display: grid; grid-template-columns: minmax(0, 1fr); }
    .org-branch { position: relative; padding: 2rem 0.5rem 0; }
    .org-branch::after {
        content: ''; position: absolute; top: 0; left: 50%; width: 1px; height: 2rem;
        background-color: #b1b8be;
    }
    .org-dept { position: relative; padding-top: 1.25rem; }
    .org-dept::before {
        content: ''; position: absolute; top: 0; left: 50%; width: 1px; height: 1.25rem;
        background-color: #b1b8be;
    }

    @media (min-width: 1280px) {
        .org-branches { grid-template-columns: repeat(var(--org-cols, 4), minmax(0, 1fr)); }
        .org-branch::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background-color: #b1b8be;
        }
        .org-branch:first-child::before { left: 50%; }
        .org-branch:last-child::before { right: 50%; }
        .org-branch:only-child::before { display: none; }
    }

    @media print {
        aside, header, .no-print { display: none !important; }
        .h-screen { height: auto !important; }
        .overflow-hidden, .overflow-y-auto { overflow: visible !important; }
        .org-branch, .org-dept { break-inside: avoid; }
    }
</style>
@endpush

<x-intranet-layout title="Organigramme" :breadcrumbs="$breadcrumbs">

<div
    x-data="{
        departments: @js($index),
        current: null,
        open(code) { this.current = this.departments[code] ?? null; },
        close() { this.current = null; }
    }"
    @keydown.escape.window="close()"
>

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Organigramme</h1>
            <p class="text-graphite-600">
                {{ $stats['departments'] }} départements · {{ $stats['employees'] }} collaborateurs
            </p>
        </div>
        <button type="button" onclick="window.print()" class="bouton-secondaire no-print self-start">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimer / PDF
        </button>
    </div>

    @if(count($branches) === 0)
        <div class="bg-white rounded-lg border border-graphite-200 p-12 text-center">
            <p class="text-graphite-600">Aucun département à afficher.</p>
        </div>
    @else
    <div class="pb-8">

        {{-- Direction générale --}}
        <div class="flex flex-col items-center">
            <div class="w-full max-w-xs rounded-xl bg-nere-600 text-white shadow-lg px-6 py-5 text-center">
                <div class="text-[11px] uppercase tracking-widest text-white/80 mb-2">Direction générale</div>
                @if($director)
                    <div class="mx-auto mb-2 w-12 h-12 rounded-full bg-white/15 flex items-center justify-center font-bold">
                        {{ $director->initials }}
                    </div>
                    <div class="font-bold text-lg leading-tight">{{ $director->name }}</div>
                    <div class="text-sm text-white/85">{{ $director->job_title }}</div>
                @else
                    <div class="font-bold text-lg">NÉRÉ Mining</div>
                @endif
            </div>
            <div class="org-line w-px h-8" aria-hidden="true"></div>
        </div>

        {{-- Branches --}}
        <div class="org-branches max-w-md mx-auto xl:max-w-none" style="--org-cols: {{ count($branches) }}">
            @foreach($branches as $branch)
                <section class="org-branch" aria-label="{{ $branch['name'] }}">

                    {{-- Branche --}}
                    <div class="flex flex-col items-center">
                        <div class="w-full rounded-xl bg-white border border-graphite-200 shadow-md px-4 py-3 text-center">
                            <h2 class="font-bold text-graphite-900 leading-tight">{{ $branch['name'] }}</h2>
                            <p class="text-xs text-graphite-500 mt-0.5">{{ count($branch['departments']) }} département(s)</p>
                        </div>
                    </div>

                    {{-- Départements --}}
                    <div class="mt-0">
                        @foreach($branch['departments'] as $dept)
                            <div class="org-dept">
                                <button
                                    type="button"
                                    @click="open('{{ $dept['code'] }}')"
                                    title="Voir le détail de {{ $dept['name'] }}"
                                    class="w-full text-left rounded-lg bg-graphite-100 hover:bg-white hover:shadow-md border border-graphite-200 border-l-4 px-4 py-3 transition focus-visible:outline-2 focus-visible:outline-nere-600"
                                    style="border-left-color: {{ $dept['color'] }}"
                                >
                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-nere-700">{{ $dept['tag'] }}</div>
                                    <div class="font-semibold text-graphite-900 text-sm leading-snug">{{ $dept['name'] }}</div>
                                    <div class="mt-1 text-xs text-graphite-600">
                                        @if($dept['head'])
                                            {{ $dept['head']['name'] }}
                                        @else
                                            <span class="italic text-graphite-400">Responsable non défini</span>
                                        @endif
                                        <span class="text-graphite-400">· {{ $dept['count'] }} pers.</span>
                                    </div>
                                </button>
                            </div>
                        @endforeach
                    </div>

                </section>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Modal détail département --}}
    <div
        x-show="current"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="org-modal-title"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-graphite-900/50 backdrop-blur-sm" @click="close()"></div>

        <div class="relative w-full max-w-lg max-h-[85vh] overflow-y-auto rounded-xl bg-white shadow-2xl" x-trap.noscroll="current">
            <template x-if="current">
                <div>
                    <div class="p-6 border-b border-graphite-100">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-nere-700" x-text="current.tag"></div>
                                <h2 id="org-modal-title" class="text-xl font-bold text-graphite-900" x-text="current.name"></h2>
                            </div>
                            <button type="button" @click="close()" class="p-1 rounded text-graphite-500 hover:bg-graphite-100" aria-label="Fermer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-2 text-sm text-graphite-600" x-text="current.description" x-show="current.description"></p>
                    </div>

                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-graphite-900 mb-3">
                            Équipe (<span x-text="current.count"></span>)
                        </h3>

                        <p x-show="current.members.length === 0" class="text-sm text-graphite-500">Aucun collaborateur rattaché.</p>

                        <ul class="space-y-3">
                            <template x-for="member in current.members" :key="member.email">
                                <li class="flex items-center gap-3">
                                    <div class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center text-xs font-bold"
                                         :class="member.is_head ? 'bg-nere-600 text-white' : 'bg-graphite-100 text-graphite-700'"
                                         x-text="member.initials"></div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-graphite-900 truncate">
                                            <span x-text="member.name"></span>
                                            <span x-show="member.is_head" class="ml-1 text-[10px] uppercase tracking-wide text-nere-700">Responsable</span>
                                        </div>
                                        <div class="text-xs text-graphite-500 truncate" x-text="member.title"></div>
                                    </div>
                                    <a :href="'mailto:' + member.email" class="ml-auto text-xs text-nere-600 hover:text-nere-700 shrink-0">Écrire</a>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="px-6 py-4 bg-graphite-50 rounded-b-xl flex flex-wrap gap-4 text-sm">
                        <a :href="current.url" class="font-medium text-nere-600 hover:text-nere-700">Page du département →</a>
                        <a :href="current.directory_url" class="font-medium text-nere-600 hover:text-nere-700">Voir dans l'annuaire →</a>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

</x-intranet-layout>
