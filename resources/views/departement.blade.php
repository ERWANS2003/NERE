<x-app-layout>
    <x-slot name="titre">{{ $departement->name }}</x-slot>

    @php
        $accent = $departement->accentColor();
    @endphp

    {{-- Retour a l'accueil : le service est une destination, pas un cul-de-sac. --}}
    <nav aria-label="Fil d'Ariane" class="mb-4">
        <a href="{{ route('accueil') }}"
           class="inline-flex items-center gap-1.5 py-1.5 text-sm font-medium text-graphite-500 transition hover:text-graphite-800">
            <x-app-icon nom="fleche-gauche" class="size-4" />
            Tous les services
        </a>
    </nav>

    <header class="carte p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-lg"
                      style="background-color: {{ $accent }}1a; color: {{ $accent }}">
                    <x-app-icon :nom="$departement->icon" class="size-7" />
                </span>

                <div>
                    <p class="tag-service">{{ $departement->tag }}</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-graphite-900">
                        {{ $departement->name }}
                    </h1>
                </div>
            </div>

            {{-- Rappel du code metier : c'est lui qui prefixe les references de
                 demandes, l'agent en aura besoin pour relier un papier a une demande. --}}
            <span class="rounded-md bg-graphite-100 px-2.5 py-1 font-mono text-xs font-medium text-graphite-600">
                {{ $departement->code }}
            </span>
        </div>

        <p class="mt-5 max-w-2xl text-sm leading-relaxed text-graphite-600">
            {{ $departement->description }}
        </p>
    </header>

    <section class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="carte p-6">
            <h2 class="text-sm font-semibold text-graphite-900">Mes droits ici</h2>

            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-graphite-600">Rôle</dt>
                    <dd class="font-medium text-graphite-900">{{ $role?->label() ?? 'Aucun' }}</dd>
                </div>

                @if ($niveau !== null)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-graphite-600">Niveau de traitement</dt>
                        <dd class="font-medium text-graphite-900">N{{ $niveau }}</dd>
                    </div>
                @endif
            </dl>

            @if ($role === null)
                <p class="mt-4 text-xs leading-relaxed text-graphite-500">
                    Vous n'avez aucune affectation dans ce service : les demandes que
                    vous y déposez resteront sans traitement.
                </p>
            @endif
        </div>

        {{-- Le catalogue de formulaires n'existe pas encore. On le dit en clair :
             un bloc vide sans explication serait lu comme une panne. --}}
        <div class="carte flex flex-col justify-between p-6 lg:col-span-2">
            <div>
                <h2 class="text-sm font-semibold text-graphite-900">Demandes</h2>

                <div class="mt-4 flex items-start gap-3 rounded-md bg-graphite-50 p-4">
                    <span class="mt-0.5 shrink-0 text-graphite-400">
                        <x-app-icon nom="info" class="size-5" />
                    </span>
                    <p class="text-sm leading-relaxed text-graphite-600">
                        Aucun formulaire n'est encore publié dans ce service. Dès que le
                        service en aura, ils apparaîtront ici et vous pourrez deposit
                        une demande directement.
                    </p>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>