<x-intranet-layout title="Centre d’aide" :breadcrumbs="[['label' => 'Centre d’aide']]">
    @php
        $doneKey = 'nere-guides-termines-'.auth()->id();
        $total = $guides->flatten(1)->count();
    @endphp

    <div class="mx-auto max-w-5xl"
         x-data="{
             termines: [],
             lire() { try { this.termines = JSON.parse(localStorage.getItem(@js($doneKey))) ?? []; } catch { this.termines = []; } },
         }"
         x-init="lire()"
         @intranet-guides-updated.window="lire()">

        <x-intranet.page-header title="Centre d’aide" description="Des guides pas à pas qui vous montrent chaque bouton, directement dans l’intranet, et leur version écrite à lire ou imprimer.">
            <x-slot:actions>
                <button type="button" onclick="window.print()" class="bouton-secondaire print:hidden">Imprimer</button>
            </x-slot:actions>
        </x-intranet.page-header>

        <section class="mb-8 grid gap-4 sm:grid-cols-3 print:hidden">
            <div class="rounded-xl border border-graphite-200 bg-white p-4">
                <p class="text-sm font-semibold text-graphite-900">1. Lancez un guide</p>
                <p class="mt-1 text-sm text-graphite-600">Le guide vous emmène sur la bonne page et met en lumière chaque élément, l’un après l’autre.</p>
            </div>
            <div class="rounded-xl border border-graphite-200 bg-white p-4">
                <p class="text-sm font-semibold text-graphite-900">2. Faites-le vous-même</p>
                <p class="mt-1 text-sm text-graphite-600">Quand une étape dit « À vous », cliquez sur l’élément entouré en vert : le guide continue sur la page suivante.</p>
            </div>
            <div class="rounded-xl border border-graphite-200 bg-white p-4">
                <p class="text-sm font-semibold text-graphite-900">3. Reprenez plus tard</p>
                <p class="mt-1 text-sm text-graphite-600">Échap met le guide en pause. Un encadré en bas à gauche permet de le reprendre là où vous étiez.</p>
            </div>
        </section>

        <p class="mb-6 text-sm text-graphite-600 print:hidden">
            <span x-text="termines.filter((id) => {{ Js::from($guides->flatten(1)->pluck('id')) }}.includes(id)).length"></span> guide(s) terminé(s) sur {{ $total }}.
            Le bouton <strong>?</strong> en bas à droite de chaque page propose les guides de la page affichée.
        </p>

        @foreach(\App\Support\IntranetGuides::ROLES as $role => $roleLabel)
            @continue(! $guides->has($role))
            <section class="mb-10">
                <h2 class="mb-4 text-xl font-bold text-graphite-900">{{ $roleLabel }}</h2>
                <div class="space-y-4">
                    @foreach($guides[$role] as $guide)
                        <article id="guide-{{ $guide['id'] }}" class="rounded-xl border border-graphite-200 bg-white" x-data="{ ouvert: false }">
                            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-semibold text-graphite-900">{{ $guide['title'] }}</h3>
                                        <span x-show="termines.includes(@js($guide['id']))" x-cloak class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-800">Terminé</span>
                                    </div>
                                    <p class="mt-1 text-sm text-graphite-600">{{ $guide['summary'] }}</p>
                                    <p class="mt-1 text-xs text-graphite-500">{{ count($guide['steps']) }} étapes · environ {{ $guide['minutes'] }} min</p>
                                </div>
                                <div class="flex shrink-0 gap-2 print:hidden">
                                    <button type="button" @click="ouvert = !ouvert" :aria-expanded="ouvert" class="bouton-secondaire" x-text="ouvert ? 'Masquer les étapes' : 'Lire les étapes'"></button>
                                    <button type="button" data-guide="{{ $guide['id'] }}" @click="$dispatch('start-intranet-guide', { id: @js($guide['id']) })" class="bouton-primaire">Lancer le guide</button>
                                </div>
                            </div>
                            <ol x-show="ouvert" x-cloak class="space-y-3 border-t border-graphite-100 p-5 print:!block">
                                @foreach($guide['steps'] as $number => $step)
                                    <li class="flex gap-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ ($step['click'] ?? false) ? 'bg-green-50 text-green-800' : 'bg-nere-50 text-nere-800' }} text-xs font-bold">{{ $number + 1 }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-graphite-900">{{ $step['title'] }}</p>
                                            <p class="mt-0.5 text-sm leading-6 text-graphite-700">{{ $step['description'] }}</p>
                                            @if(! empty($step['tip']))
                                                <p class="mt-1 rounded-lg bg-or-50 px-3 py-1.5 text-sm text-graphite-800">Astuce : {{ $step['tip'] }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="mb-10">
            <h2 class="mb-4 text-xl font-bold text-graphite-900">Questions fréquentes</h2>
            <div class="divide-y divide-graphite-100 rounded-xl border border-graphite-200 bg-white">
                @foreach([
                    'Quel mot de passe utiliser pour me connecter ?' => 'Celui de votre session Windows au bureau. Pour le changer ou s’il est bloqué, ouvrez un ticket ou contactez le service informatique.',
                    'Je n’arrive pas à ouvrir l’intranet depuis chez moi ou sur mon téléphone.' => 'L’intranet n’est accessible que depuis le réseau de la mine (Wi-Fi ou câble). Un VPN personnel actif peut aussi empêcher l’accès : désactivez-le.',
                    'Je ne reçois pas les notifications Windows.' => 'Ouvrez Notifications, cliquez sur « Activer les notifications push » et acceptez la demande du navigateur. Vérifiez aussi que les notifications du navigateur sont autorisées dans les paramètres de Windows.',
                    'Puis-je modifier une demande déjà envoyée ?' => 'Non : annulez-la (pour un congé) ou ajoutez une précision en commentaire (pour un ticket), puis refaites une demande si nécessaire.',
                    'Mon congé a été refusé, que faire ?' => 'Le motif du refus figure dans la notification et dans l’historique de Mes congés. Rapprochez-vous de votre responsable, puis déposez une nouvelle demande avec d’autres dates.',
                    'Les inscriptions au menu sont closes, puis-je encore changer mon repas ?' => 'Non depuis l’intranet. Contactez le gestionnaire du menu : il peut rouvrir la semaine.',
                    'Combien de temps les messages du chat sont-ils gardés ?' => 'Six mois. Ils sont ensuite supprimés automatiquement.',
                    'Mon poste ou mon département affiché est faux.' => 'Ces informations viennent de l’annuaire de l’entreprise. Signalez l’erreur au service informatique par un ticket.',
                ] as $question => $answer)
                    <details class="group p-5">
                        <summary class="cursor-pointer list-none font-semibold text-graphite-900 marker:hidden">
                            <span class="mr-2 inline-block text-nere-700 transition group-open:rotate-90">›</span>{{ $question }}
                        </summary>
                        <p class="mt-2 pl-5 text-sm leading-6 text-graphite-700">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </div>
</x-intranet-layout>
