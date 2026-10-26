@php
    $tutorialUser = auth()->user();
    $tutorialConfig = [
        'steps' => \App\Support\IntranetTutorial::stepsFor($tutorialUser),
        'autoOpen' => $tutorialUser->tutorial_completed_at === null,
        'completeUrl' => route('intranet.tutorial.complete'),
        'csrf' => csrf_token(),
        'storageKey' => 'nere-tutoriel-etape-'.$tutorialUser->id,
    ];
@endphp

<div
    x-data="intranetTutorial(@js($tutorialConfig))"
    @keydown.escape.window="open && plusTard()"
    @keydown.arrow-right.window="open && suivant()"
    @keydown.arrow-left.window="open && precedent()"
    x-show="open"
    x-trap="open"
    x-cloak
    class="intranet-tour"
    role="dialog"
    aria-modal="true"
    aria-labelledby="intranet-tour-title"
    aria-describedby="intranet-tour-description"
>
    {{-- Capte les clics pendant le guide pour éviter de quitter la page par erreur. --}}
    <div class="fixed inset-0 z-[80]" @click="plusTard()" aria-hidden="true"></div>

    {{-- Projecteur : rectangle transparent entouré d'un voile sombre. --}}
    <div
        class="intranet-tour-spotlight pointer-events-none fixed z-[81]"
        :class="projecteurVisible ? 'is-target' : ''"
        :style="projecteur"
        aria-hidden="true"
    ></div>

    {{-- Bulle explicative ancrée près de l'élément présenté. --}}
    <section
        x-ref="bulle"
        class="intranet-tour-card fixed z-[82] w-[min(22.5rem,calc(100vw-1.5rem))]"
        :class="mobile ? 'is-sheet' : ''"
        :style="bulle"
        @click.stop
    >
        <span
            x-show="fleche.side"
            class="intranet-tour-arrow"
            :class="'is-' + fleche.side"
            :style="fleche.side === 'left' ? { top: fleche.offset } : { left: fleche.offset }"
            aria-hidden="true"
        ></span>

        <div class="h-1.5 overflow-hidden rounded-t-[1.25rem] bg-graphite-100" aria-hidden="true">
            <div class="h-full bg-gradient-to-r from-nere-600 to-or-400 transition-all duration-300" :style="{ width: progression + '%' }"></div>
        </div>

        <div class="p-5" aria-live="polite" aria-atomic="true">
            <div class="mb-2 flex items-center justify-between gap-3">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-nere-700">
                    Guide de démarrage · <span x-text="(index + 1) + ' / ' + etapes.length"></span>
                </p>
                <button
                    type="button"
                    @click="plusTard()"
                    class="-mr-2 rounded-full p-2 text-graphite-500 hover:bg-graphite-100 hover:text-graphite-800 focus-visible:outline-2 focus-visible:outline-nere-600"
                    aria-label="Fermer le guide et le reprendre plus tard"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <h2 id="intranet-tour-title" x-ref="titre" tabindex="-1" class="text-lg font-bold leading-snug text-graphite-900 outline-none" x-text="etape?.title"></h2>
            <p id="intranet-tour-description" class="mt-2 text-sm leading-6 text-graphite-700" x-text="etape?.description"></p>

            <template x-if="etape?.tip">
                <p class="mt-3 flex items-start gap-2 rounded-xl bg-or-50 px-3 py-2 text-sm text-graphite-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-or-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <span x-text="etape.tip"></span>
                </p>
            </template>

            <template x-if="etape?.checklist">
                <ul class="mt-3 space-y-2">
                    <template x-for="action in etape.checklist" :key="action.url">
                        <li>
                            <a
                                :href="action.url"
                                @click.prevent="terminer().then(() => window.location.assign(action.url))"
                                class="flex items-center gap-3 rounded-xl border border-graphite-200 px-3 py-2.5 text-sm font-medium text-graphite-800 transition hover:border-nere-300 hover:bg-nere-50"
                            >
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-nere-50 text-nere-700" aria-hidden="true">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                                </span>
                                <span x-text="action.label"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>

            <div class="mt-5 flex items-center justify-between gap-2">
                <p class="text-xs font-medium text-graphite-500" x-text="etapes.length - index - 1 > 0 ? (etapes.length - index - 1) + ' étape(s) restante(s)' : 'Dernière étape'"></p>

                <div class="flex shrink-0 gap-2">
                    <button
                        type="button"
                        x-show="index > 0"
                        @click="precedent()"
                        class="min-h-10 rounded-full border border-graphite-300 px-4 text-sm font-semibold text-graphite-800 hover:bg-graphite-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                    >
                        Précédent
                    </button>
                    <button
                        type="button"
                        x-show="index < etapes.length - 1"
                        @click="suivant()"
                        class="min-h-10 rounded-full bg-nere-700 px-5 text-sm font-semibold text-white hover:bg-nere-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                        x-text="index === 0 ? 'Commencer' : 'Suivant'"
                    ></button>
                    <button
                        type="button"
                        x-show="index === etapes.length - 1"
                        @click="terminer()"
                        :disabled="envoi"
                        class="min-h-10 rounded-full bg-nere-700 px-5 text-sm font-semibold text-white hover:bg-nere-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600 disabled:opacity-60"
                    >
                        Terminer
                    </button>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-graphite-100 pt-3 text-xs text-graphite-500">
                <span class="hidden sm:inline">← → pour naviguer · Échap : plus tard</span>
                <form x-ref="formulaireFin" method="POST" action="{{ route('intranet.tutorial.complete') }}" class="ml-auto">
                    @csrf
                    <button type="submit" @click.prevent="terminer()" class="font-medium text-graphite-600 underline-offset-2 hover:text-nere-700 hover:underline">
                        Ne plus afficher le guide
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>
