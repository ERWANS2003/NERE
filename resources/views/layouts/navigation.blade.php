@php
    $utilisateur = Auth::user();
@endphp

<nav x-data="{ ouvert: false }"
     x-on:keydown.escape.window="ouvert = false"
     class="border-b border-graphite-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('accueil') }}"
                   class="flex shrink-0 items-center gap-2.5">
                    <x-logo class="h-8 w-auto text-graphite-900" />
                    <span class="hidden text-sm font-semibold tracking-tight text-graphite-900 sm:block">
                        {{ config('app.name') }}
                    </span>
                </a>

                <div class="hidden space-x-6 sm:flex">
                    <x-lien-nav :href="route('accueil')" :active="request()->routeIs('accueil')">
                        Accueil
                    </x-lien-nav>

                    <x-lien-nav :href="route('profil.edit')" :active="request()->routeIs('profil.*')">
                        Mon compte
                    </x-lien-nav>
                </div>
            </div>

            <div class="hidden items-center gap-4 sm:flex">
                {{-- Le matricule est l'identifiant metier : l'afficher en permanence
                     evite au personnel de le retrouver dans le profile quand un
                     technicien doit verifier une identite. --}}
                <div class="text-right leading-tight">
                    <div class="text-sm font-medium text-graphite-900">
                        {{ $utilisateur->name }}
                    </div>
                    <div class="font-mono text-xs text-graphite-500">
                        {{ $utilisateur->matricule }}
                    </div>
                </div>

                <x-menu-deroulant>
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm leading-4 font-medium text-graphite-500 transition hover:bg-graphite-100 hover:text-graphite-800 focus:outline-none">
                            <span class="sr-only">Ouvrir le menu du compte</span>
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2">
                            <div class="text-sm font-medium text-graphite-900">{{ $utilisateur->name }}</div>
                            <div class="text-xs text-graphite-500">{{ $utilisateur->email }}</div>
                            @if ($utilisateur->is_super_admin)
                                <div class="tag-ambre mt-2">Administrateur general</div>
                            @endif
                        </div>

                        <x-lien-menu :href="route('profil.edit')">
                            Mon compte
                        </x-lien-menu>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-lien-menu :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Se deconnecter
                            </x-lien-menu>
                        </form>
                    </x-slot>
                </x-menu-deroulant>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="ouvert = ! ouvert"
                        :aria-expanded="ouvert"
                        aria-controls="menu-mobile"
                        class="inline-flex items-center justify-center rounded-md p-2 text-graphite-400 transition hover:bg-graphite-100 hover:text-graphite-600 focus:outline-none">
                    <span class="sr-only">Ouvrir le menu principal</span>
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': ouvert, 'inline-flex': ! ouvert }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! ouvert, 'inline-flex': ouvert }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="menu-mobile" x-cloak :class="{'block': ouvert}" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-lien-nav-mobile :href="route('accueil')" :active="request()->routeIs('accueil')">
                Accueil
            </x-lien-nav-mobile>

            <x-lien-nav-mobile :href="route('profil.edit')" :active="request()->routeIs('profil.*')">
                Mon compte
            </x-lien-nav-mobile>
        </div>

        <div class="border-t border-graphite-200 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-graphite-900">{{ $utilisateur->name }}</div>
                <div class="text-sm text-graphite-500">
                    <span class="font-mono">{{ $utilisateur->matricule }}</span>
                    — {{ $utilisateur->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-lien-nav-mobile :href="route('profil.edit')">
                    Mon compte
                </x-lien-nav-mobile>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-lien-nav-mobile :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Se deconnecter
                    </x-lien-nav-mobile>
                </form>
            </div>
        </div>
    </div>
</nav>