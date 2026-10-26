<div
    x-data="{
        open: false,
        step: 0,
        indicator: { top: '0px', left: '0px' },
        steps: @js([
            [
                'title' => 'Bienvenue dans votre intranet',
                'description' => 'Suivez ce guide pour repérer les principales sections et les boutons utiles. À chaque étape, l’élément concerné est encadré en jaune.',
                'label' => 'Vue d’ensemble',
                'target' => null,
            ],
            [
                'title' => 'Accueil',
                'description' => 'Ce bouton vous ramène au tableau de bord. C’est votre point de départ pour consulter les informations importantes.',
                'label' => 'Menu latéral · Accueil',
                'target' => '#intranet-home-link',
            ],
            [
                'title' => 'Mes demandes',
                'description' => 'Retrouvez ici les demandes que vous avez envoyées et consultez leur statut ou les réponses des équipes.',
                'label' => 'Menu latéral · Mes demandes',
                'target' => '#intranet-requests-link',
            ],
            [
                'title' => 'Services',
                'description' => 'Cette rubrique regroupe les formulaires, le support informatique et, selon vos accès, les demandes à traiter.',
                'label' => 'Menu latéral · Services',
                'target' => '#intranet-services-section',
            ],
            [
                'title' => 'Vie de l’entreprise',
                'description' => 'Consultez les annonces, l’agenda, les départements, l’annuaire et les ressources disponibles.',
                'label' => 'Menu latéral · Vie de l’entreprise',
                'target' => '#intranet-company-section',
            ],
            [
                'title' => 'Recherche',
                'description' => 'Cliquez ici pour rechercher rapidement des employés, des départements, des annonces ou des documents. Le raccourci Ctrl+K (ou ⌘K sur Mac) ouvre aussi la recherche.',
                'label' => 'Barre supérieure · Rechercher',
                'target' => '#intranet-search-trigger',
            ],
            [
                'title' => 'Notifications',
                'description' => 'Le bouton cloche vous permet de consulter les nouveautés et les mises à jour qui vous concernent.',
                'label' => 'Barre supérieure · Notifications',
                'target' => '#intranet-notifications-trigger',
            ],
            [
                'title' => 'Profil et aide',
                'description' => 'Ouvrez votre menu de profil pour consulter votre compte ou choisir « Revoir le tutoriel » plus tard.',
                'label' => 'Menu du profil',
                'target' => '#intranet-profile-menu',
            ],
        ]),
        init(shouldOpen) {
            this.open = shouldOpen;
            this.$watch('step', () => this.$nextTick(() => this.syncTarget()));
            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    this.$nextTick(() => this.syncTarget());
                } else {
                    this.clearTarget();
                }
            });
            if (this.open) {
                this.$nextTick(() => this.syncTarget());
            }
        },
        syncTarget() {
            this.clearTarget();
            if (!this.open) return;

            const targetSelector = this.steps[this.step]?.target;
            const target = targetSelector ? document.querySelector(targetSelector) : null;
            if (!target) return;

            target.classList.add('intranet-tour-highlight');

            if (target.closest('#intranet-sidebar')) {
                window.dispatchEvent(new CustomEvent('intranet-tour-open-sidebar'));
            } else {
                window.dispatchEvent(new CustomEvent('intranet-tour-close-sidebar'));
            }

            if (targetSelector === '#intranet-profile-menu') {
                window.dispatchEvent(new CustomEvent('open-intranet-profile'));
            } else {
                window.dispatchEvent(new CustomEvent('close-intranet-profile'));
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            window.setTimeout(() => this.positionIndicator(), 350);
        },
        positionIndicator() {
            const target = document.querySelector('.intranet-tour-highlight');
            if (!target || !this.open) return;

            const rect = target.getBoundingClientRect();
            const top = rect.top > 72 ? rect.top - 48 : Math.min(rect.bottom + 8, window.innerHeight - 56);
            const left = Math.max(12, Math.min(rect.left, window.innerWidth - 220));

            this.indicator = {
                top: `${top}px`,
                left: `${left}px`,
            };
        },
        clearTarget() {
            document.querySelectorAll('.intranet-tour-highlight').forEach((element) => {
                element.classList.remove('intranet-tour-highlight');
            });
            window.dispatchEvent(new CustomEvent('close-intranet-profile'));
        },
        close() {
            this.open = false;
            window.dispatchEvent(new CustomEvent('intranet-tour-close-sidebar'));
        },
    }"
    x-init="init(@js(auth()->user()->tutorial_completed_at === null))"
    @open-intranet-tutorial.window="open = true; step = 0"
    @keydown.escape.window.stop="close()"
    @keydown.arrow-right.window.prevent="step = Math.min(step + 1, steps.length - 1)"
    @keydown.arrow-left.window.prevent="step = Math.max(step - 1, 0)"
    x-show="open"
    x-trap.noscroll="open"
    x-cloak
    role="dialog"
    aria-modal="true"
    aria-label="Tutoriel de prise en main de l’intranet"
>
    <div class="fixed inset-0 z-30 bg-graphite-950/65" aria-hidden="true"></div>

    <div
        x-show="step > 0"
        :style="indicator"
        class="pointer-events-none fixed z-[60] rounded-full border-2 border-graphite-950 bg-or-400 px-3 py-1.5 text-xs font-extrabold uppercase tracking-wide text-graphite-950 shadow-lg"
        aria-hidden="true"
    >
        <span x-text="'Repère ' + step"></span>
        <span class="mx-1">·</span>
        <span x-text="steps[step].label"></span>
    </div>

    <section class="fixed inset-x-3 bottom-3 z-[70] max-h-[55vh] overflow-y-auto rounded-2xl border border-graphite-200 bg-white shadow-2xl sm:inset-x-auto sm:bottom-6 sm:left-1/2 sm:w-full sm:max-w-xl sm:-translate-x-1/2">
        <div class="h-2 bg-nere-700"></div>
        <div class="p-5 sm:p-7">
            <div class="mb-4 flex items-center justify-between gap-4">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-nere-700">Guide de démarrage</p>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="close()"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-graphite-600 hover:bg-graphite-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                        aria-label="Fermer le tutoriel sans le terminer"
                    >
                        Fermer
                    </button>
                    <form method="POST" action="{{ route('intranet.tutorial.complete') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-graphite-600 hover:bg-graphite-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600">
                            Passer
                        </button>
                    </form>
                </div>
            </div>

            <div aria-live="polite" aria-atomic="true">
                <p class="mb-2 text-sm font-semibold text-graphite-500">
                    Étape <span x-text="step + 1"></span> sur <span x-text="steps.length"></span>
                    <span class="mx-1" aria-hidden="true">·</span>
                    <span x-text="steps[step].label"></span>
                </p>
                <h2 class="text-xl font-bold text-graphite-900 sm:text-2xl" x-text="steps[step].title"></h2>
                <p class="mt-2 leading-6 text-graphite-700" x-text="steps[step].description"></p>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-graphite-200 pt-4">
                <div class="flex gap-1.5" role="group" aria-label="Progression du tutoriel">
                    <template x-for="(item, index) in steps" :key="index">
                        <button
                            type="button"
                            @click="step = index"
                            class="flex min-h-11 min-w-11 items-center justify-center rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                            :aria-label="'Aller à l’étape ' + (index + 1) + ' : ' + item.title"
                            :aria-current="step === index ? 'step' : null"
                        >
                            <span
                                class="h-2 rounded-full transition-all"
                                :class="step === index ? 'w-6 bg-nere-700' : 'w-2 bg-graphite-300'"
                            ></span>
                        </button>
                    </template>
                </div>

                <div class="flex gap-2">
                    <p class="hidden self-center text-xs text-graphite-500 md:block">
                        ← → pour naviguer
                    </p>
                    <button
                        type="button"
                        x-show="step > 0"
                        @click="step--"
                        class="min-h-11 rounded-lg border border-graphite-300 px-4 py-2 text-sm font-semibold text-graphite-800 hover:bg-graphite-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                    >
                        Précédent
                    </button>
                    <button
                        type="button"
                        x-show="step < steps.length - 1"
                        @click="step++"
                        class="min-h-11 rounded-lg bg-nere-700 px-5 py-2 text-sm font-semibold text-white hover:bg-nere-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600"
                    >
                        Étape suivante
                    </button>
                    <form
                        method="POST"
                        action="{{ route('intranet.tutorial.complete') }}"
                        x-show="step === steps.length - 1"
                    >
                        @csrf
                        <button type="submit" class="min-h-11 rounded-lg bg-nere-700 px-5 py-2 text-sm font-semibold text-white hover:bg-nere-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600">
                            Terminer le tutoriel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
