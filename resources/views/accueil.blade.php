<x-app-layout>
    <x-slot name="titre">Accueil</x-slot>

    {{-- Salutation : la page doit situer l'agent dans le temps et lui rappeler
         l'objet du portail avant de lui proposer des services. --}}
    <section class="border-b border-graphite-200 pb-6">
        <p class="tag-service">Portail des services</p>

        <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-graphite-900 sm:text-3xl">
            Bonjour {{ $utilisateur->name }}
        </h1>

        <p class="mt-2 text-sm text-graphite-500">
            <time datetime="{{ now()->toDateString() }}">{{ now()->translatedFormat('l j F Y') }}</time>
            <span aria-hidden="true">·</span>
            Choisissez le service concerné pour y déposer une demande.
        </p>
    </section>

    {{-- Services : la seule liste utile ici est celle des services réellement
         accessibles. Le scope visibleTo est aussi la porte d'entrée de la page
         d'un service : les deux affichages ne peuvent pas diverger. --}}
    <section class="mt-8" aria-labelledby="titre-services">
        <div class="flex items-baseline justify-between gap-4">
            <h2 id="titre-services" class="text-lg font-semibold tracking-tight text-graphite-900">
                Mes services
            </h2>

            <p class="text-sm tabular-nums text-graphite-500">
                {{ $services->count() }} {{ $services->count() > 1 ? 'services' : 'service' }}
            </p>
        </div>

        @if ($services->isEmpty())
            {{-- Cas normal d'un compte cree par les RH mais pas encore affecte.
                 On explique l'attente et vers qui se tourner plutot que d'afficher
                 une grille vide sans explication. --}}
            <div class="carte mt-4 px-6 py-12 text-center">
                <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-graphite-100 text-graphite-400">
                    <x-app-icon nom="batiment" class="size-6" />
                </span>

                <h3 class="mt-4 text-base font-semibold text-graphite-900">
                    Aucun service ne vous est encore affecté
                </h3>

                <p class="mx-auto mt-2 max-w-md text-sm text-graphite-600">
                    Vos demandes apparaîtront ici dès qu'un responsable vous aura affecté
                    à un service. Si ce n'est pas encore le cas, contactez votre
                    responsable ou les ressources humaines.
                </p>
            </div>
        @else
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($services as $service)
                    @php
                        $accent = $service->accentColor();
                        $role = $utilisateur->roleIn($service->getKey());
                    @endphp

                    <li>
                        {{-- Nom accessible limite au libelle du service : le role et la
                             description sont accessibles separement, sinon un lecteur
                             d'ecran concatene trois informations avant le lien. --}}
                        <a href="{{ route('departement.show', ['code' => $service->code]) }}"
                           aria-labelledby="service-{{ $service->code }}"
                           aria-describedby="description-{{ $service->code }}"
                           class="carte group flex h-full flex-col p-5 transition hover:border-graphite-300 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                {{-- L'accent du service ne colore que cette pastille : la
                                     carte reste graphite, seule la pastille porte la
                                     couleur du service. --}}
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg"
                                      style="background-color: {{ $accent }}1a; color: {{ $accent }}">
                                    <x-app-icon :nom="$service->icon" class="size-6" />
                                </span>

                                @if ($role !== null)
                                    <span class="rounded-full bg-graphite-100 px-2.5 py-1 text-xs font-medium text-graphite-700">
                                        {{ $role->label() }}
                                    </span>
                                @endif
                            </div>

                            <p class="tag-service mt-4">{{ $service->tag }}</p>

                            <h3 id="service-{{ $service->code }}"
                                class="mt-1 text-base font-semibold text-graphite-900">
                                {{ $service->name }}
                            </h3>

                            <p id="description-{{ $service->code }}"
                               class="mt-2 flex-1 text-sm leading-relaxed text-graphite-600">
                                {{ $service->description }}
                            </p>

                            <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-nere-700">
                                Ouvrir
                                <x-app-icon nom="chevron-droite" class="size-4 transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-layout>