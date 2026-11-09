@php
    $tutorialUser = auth()->user();
    $tutorialGuides = \App\Support\IntranetGuides::for($tutorialUser);
    $tutorialRoute = request()->route()?->getName();
    $tutorialConfig = [
        // Seuls les titres sont dans la page ; les étapes sont chargées au lancement d'un guide.
        'guides' => collect($tutorialGuides)->map(fn (array $g) => ['id' => $g['id'], 'title' => $g['title'], 'count' => count($g['steps'])])->all(),
        'pageGuides' => collect($tutorialGuides)
            ->filter(fn (array $g) => collect($g['steps'])->contains(fn (array $s) => in_array($tutorialRoute, (array) $s['route'], true)))
            ->keys()->values()->all(),
        'stepsUrl' => route('intranet.help.guide', '__ID__'),
        'currentRoute' => $tutorialRoute,
        'autoOpen' => $tutorialUser->tutorial_completed_at === null,
        'completeUrl' => route('intranet.tutorial.complete'),
        'csrf' => csrf_token(),
        'stateKey' => 'nere-guide-en-cours-'.$tutorialUser->id,
        'doneKey' => 'nere-guides-termines-'.$tutorialUser->id,
    ];
@endphp

<div
    x-data="intranetTutorial(@js($tutorialConfig))"
    @keydown.escape.window="open && plusTard()"
    @keydown.arrow-right.window="open && !attendClic && suivant()"
    @keydown.arrow-left.window="open && precedent()"
>
    <div
        x-show="open"
        x-trap="open && !attendClic"
        x-cloak
        class="intranet-tour"
        role="dialog"
        aria-modal="true"
        aria-labelledby="intranet-tour-title"
        aria-describedby="intranet-tour-description"
    >
        {{-- Pendant une explication, capte les clics pour éviter de quitter la page par erreur.
             Pendant une étape « cliquez ici », la page reste utilisable. --}}
        <div x-show="!attendClic" class="fixed inset-0 z-[80]" @click="plusTard()" aria-hidden="true"></div>

        <div
            class="intranet-tour-spotlight pointer-events-none fixed z-[81]"
            :class="[projecteurVisible ? 'is-target' : '', attendClic ? 'is-clickable' : '']"
            :style="projecteur"
            aria-hidden="true"
        ></div>

        <section
            x-ref="bulle"
            class="intranet-tour-card fixed z-[82] w-[min(23.75rem,calc(100vw-1.5rem))]"
            :class="[mobile ? 'is-sheet' : '', pret ? 'opacity-100' : 'opacity-0']"
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

            <div class="p-4 sm:p-5" aria-live="polite" aria-atomic="true">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <p class="min-w-0 truncate text-xs font-bold uppercase tracking-[0.12em] text-nere-700">
                        <span x-text="guide?.title"></span> · <span x-text="(index + 1) + ' / ' + etapes.length"></span>
                    </p>
                    <button
                        type="button"
                        @click="plusTard()"
                        class="-mr-2 shrink-0 rounded-full p-2 text-graphite-500 hover:bg-graphite-100 hover:text-graphite-800 focus-visible:outline-2 focus-visible:outline-nere-600"
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

                <template x-if="attendClic">
                    <p class="mt-3 flex items-center gap-2 rounded-xl border border-nere-200 bg-nere-50 px-3 py-2 text-sm font-semibold text-nere-800">
                        <svg class="h-4 w-4 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                        À vous : cliquez sur l’élément en surbrillance, le guide continue ensuite.
                    </p>
                </template>

                <template x-if="cibleAbsente && etape?.click">
                    <p class="mt-3 rounded-xl bg-graphite-50 px-3 py-2 text-sm text-graphite-700">
                        Cet élément n’est pas disponible sur cette page pour le moment (aucune donnée à afficher). Vous pouvez passer à la suite.
                    </p>
                </template>

                <template x-if="etape?.checklist">
                    <ul class="mt-3 space-y-2">
                        <template x-for="action in etape.checklist" :key="action.url">
                            <li>
                                <a
                                    :href="action.url"
                                    @click.prevent="finir(false); window.location.assign(action.url)"
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

                <div class="mt-4 flex items-center justify-between gap-2">
                    <p class="text-xs font-medium text-graphite-500" x-text="etapes.length - index - 1 > 0 ? (etapes.length - index - 1) + ' étape(s) restante(s)' : 'Dernière étape'"></p>

                    <div class="flex shrink-0 gap-2">
                        <button type="button" x-show="index > 0" @click="precedent()"
                                class="min-h-10 rounded-full border border-graphite-300 px-4 text-sm font-semibold text-graphite-800 hover:bg-graphite-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600">
                            Précédent
                        </button>
                        <button type="button" x-show="!attendClic && index < etapes.length - 1" @click="suivant()"
                                class="min-h-10 rounded-full bg-nere-700 px-5 text-sm font-semibold text-white hover:bg-nere-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                                x-text="index === 0 ? 'Commencer' : 'Suivant'"></button>
                        <button type="button" x-show="!attendClic && index === etapes.length - 1" @click="terminer()" :disabled="envoi"
                                class="min-h-10 rounded-full bg-nere-700 px-5 text-sm font-semibold text-white hover:bg-nere-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600 disabled:opacity-60">
                            Terminer
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between gap-3 border-t border-graphite-100 pt-2.5 text-xs text-graphite-500">
                    <span class="hidden sm:inline" x-text="attendClic ? 'Échap : reprendre plus tard' : '← → pour naviguer · Échap : plus tard'"></span>
                    <form method="POST" action="{{ route('intranet.tutorial.complete') }}" class="ml-auto" @submit.prevent="abandonner()">
                        @csrf
                        <button type="submit" class="font-medium text-graphite-600 underline-offset-2 hover:text-nere-700 hover:underline"
                                x-text="guideId === 'decouverte' ? 'Ne plus afficher le guide' : 'Quitter ce guide'">Ne plus afficher le guide</button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    {{-- Guide interrompu : proposition de reprise, sur n'importe quelle page. --}}
    <div x-show="reprise && !open" x-cloak x-transition.opacity
         class="fixed bottom-24 left-4 z-40 w-[min(22rem,calc(100vw-2rem))] sm:bottom-4 rounded-2xl border border-graphite-200 bg-white p-4 shadow-xl"
         role="status">
        <p class="text-xs font-bold uppercase tracking-[0.12em] text-nere-700">Guide en cours</p>
        <p class="mt-1 text-sm font-semibold text-graphite-900" x-text="guide?.title"></p>
        <p class="text-xs text-graphite-500" x-text="'Étape ' + (index + 1) + ' sur ' + etapes.length"></p>
        <div class="mt-3 flex gap-2">
            <button type="button" @click="reprendre()" class="min-h-9 rounded-full bg-nere-700 px-4 text-sm font-semibold text-white hover:bg-nere-800">Reprendre</button>
            <button type="button" @click="abandonner()" class="min-h-9 rounded-full border border-graphite-300 px-4 text-sm font-semibold text-graphite-700 hover:bg-graphite-50">Arrêter</button>
        </div>
    </div>

    {{-- Aide contextuelle : les guides qui concernent la page affichée. --}}
    <div x-show="!open && !reprise" x-cloak class="fixed bottom-24 right-4 z-40 sm:bottom-4" @click.outside="aideOuverte = false">
        <div x-show="aideOuverte" x-transition.origin.bottom.right
             class="mb-3 w-[min(20rem,calc(100vw-2rem))] rounded-2xl border border-graphite-200 bg-white p-3 shadow-xl">
            <p class="px-2 pb-2 text-xs font-bold uppercase tracking-[0.12em] text-nere-700">Guides de cette page</p>
            <template x-for="g in guidesDeLaPage" :key="g.id">
                <button type="button" @click="lancer(g.id)" class="flex w-full items-center justify-between gap-3 rounded-xl px-2 py-2 text-left text-sm text-graphite-800 hover:bg-nere-50">
                    <span x-text="g.title"></span>
                    <span class="shrink-0 text-xs text-graphite-500" x-text="g.count + ' étapes'"></span>
                </button>
            </template>
            <template x-if="guidesDeLaPage.length === 0">
                <p class="px-2 py-2 text-sm text-graphite-500">Aucun guide pour cette page.</p>
            </template>
            <a href="{{ route('intranet.help') }}" class="mt-1 block rounded-xl px-2 py-2 text-sm font-semibold text-nere-700 hover:bg-nere-50">Tous les guides et l’aide écrite</a>
        </div>
        <button type="button" @click="aideOuverte = !aideOuverte" :aria-expanded="aideOuverte"
                class="ml-auto flex h-12 w-12 items-center justify-center rounded-full bg-nere-700 text-white shadow-lg transition hover:bg-nere-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                aria-label="Aide : guides de cette page">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>
    </div>
</div>
