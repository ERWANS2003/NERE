<div
    x-data="{ open: false, step: 0 }"
    x-init="open = @js(auth()->user()->tutorial_completed_at === null)"
    @open-intranet-tutorial.window="open = true; step = 0"
    x-show="open"
    x-trap.noscroll="open"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center bg-graphite-950/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-label="Tutoriel de prise en main de l’intranet"
>
    <form method="POST" action="{{ route('intranet.tutorial.complete') }}" class="w-full max-w-lg">
        @csrf
        <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="h-2 bg-nere-700"></div>
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-sm font-semibold uppercase tracking-wide text-nere-700">Guide de démarrage</p>
                    <button type="submit" class="rounded-lg px-3 py-2 text-sm text-graphite-500 hover:bg-graphite-100 hover:text-graphite-900">
                        Passer le tutoriel
                    </button>
                </div>

                <div x-show="step === 0">
                    <h2 class="text-2xl font-bold text-graphite-900">Bienvenue dans votre intranet</h2>
                    <p class="mt-3 leading-6 text-graphite-600">Ce guide vous présente les principaux espaces pour trouver une information, effectuer une demande et suivre son traitement.</p>
                </div>

                <div x-show="step === 1" x-cloak>
                    <h2 class="text-2xl font-bold text-graphite-900">Naviguez dans les espaces</h2>
                    <p class="mt-3 leading-6 text-graphite-600">Le menu à gauche donne accès à l’accueil, à vos demandes, aux formulaires, au support informatique et aux informations de l’entreprise. La recherche en haut de page vous aide à retrouver rapidement un contenu.</p>
                </div>

                <div x-show="step === 2" x-cloak>
                    <h2 class="text-2xl font-bold text-graphite-900">Effectuez et suivez vos demandes</h2>
                    <p class="mt-3 leading-6 text-graphite-600">Choisissez « Faire une demande » pour transmettre un besoin à un service, ou « Support informatique » pour signaler un problème. Retrouvez ensuite les mises à jour dans « Mes demandes ».</p>
                </div>

                <div x-show="step === 3" x-cloak>
                    <h2 class="text-2xl font-bold text-graphite-900">Retrouvez votre profil et ce guide</h2>
                    <p class="mt-3 leading-6 text-graphite-600">Ouvrez le menu avec votre nom et votre avatar pour consulter votre profil. Vous pourrez relancer ce tutoriel à tout moment depuis ce même menu.</p>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <div class="flex gap-2" role="group" aria-label="Progression du tutoriel">
                        <template x-for="index in 4" :key="index">
                            <span class="h-2 w-2 rounded-full" :class="step === index - 1 ? 'bg-nere-700' : 'bg-graphite-200'"></span>
                        </template>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            x-show="step > 0"
                            @click="step--"
                            class="rounded-lg border border-graphite-300 px-4 py-2 text-sm font-semibold text-graphite-700 hover:bg-graphite-50"
                        >
                            Précédent
                        </button>
                        <button
                            type="button"
                            x-show="step < 3"
                            @click="step++"
                            class="rounded-lg bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800"
                        >
                            Suivant
                        </button>
                        <button
                            type="submit"
                            x-show="step === 3"
                            class="rounded-lg bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800"
                        >
                            Terminer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
