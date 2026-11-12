{{-- Département avec un ou deux services : le service est mis en avant et la
     page est complétée par le mode d'emploi et les contacts du département. --}}
@php
    $responsables = $team->where('isHead', true)->take(2);
    $apercuEquipe = $team->take(6);
    $suiviUrl = $department->code === 'IT' ? route('intranet.tickets.index') : route('intranet.submissions.index');
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        @foreach($services as $service)
            @php
                $destinationUrl = $service->destinationUrl();
                $actif = $destinationUrl !== null;
            @endphp
            <{{ $actif ? 'a' : 'div' }}
                @if($actif) href="{{ $destinationUrl }}" data-tour="service-carte" @endif
                @if($actif && $service->link_type === 'external') target="_blank" rel="noopener noreferrer" @endif
                class="group relative block overflow-hidden rounded-2xl border border-graphite-200 bg-white p-6 transition sm:p-8 {{ $actif ? 'hover:-translate-y-0.5 hover:border-nere-300 hover:shadow-lg' : 'opacity-75' }}"
            >
                <span class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-nere-50 transition group-hover:scale-110" aria-hidden="true"></span>
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-start">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-nere-600 text-white shadow-md shadow-nere-600/20">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-nere-700">{{ $services->count() === 1 ? 'Le service du département' : 'Service' }}</p>
                        <h3 class="mt-1 text-2xl font-bold text-graphite-900">{{ $service->name }}</h3>
                        @if($service->description)
                            <p class="mt-2 text-base leading-7 text-graphite-600">{{ $service->description }}</p>
                        @endif
                        @if($actif)
                            <span class="mt-5 inline-flex items-center gap-2 rounded-xl bg-nere-700 px-5 py-3 text-sm font-semibold text-white transition group-hover:bg-nere-800">
                                Ouvrir le service
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        @else
                            <span class="mt-5 inline-block rounded bg-graphite-100 px-2 py-0.5 text-xs font-medium text-graphite-600">Accès à configurer</span>
                        @endif
                    </div>
                </div>
            </{{ $actif ? 'a' : 'div' }}>
        @endforeach

        <section class="rounded-2xl border border-graphite-200 bg-white p-6">
            <h3 class="font-semibold text-graphite-900">Comment ça marche</h3>
            <ol class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach([
                    ['Décrivez votre besoin', 'Ouvrez le service et remplissez la demande : soyez précis, ajoutez une capture ou une photo si besoin.'],
                    ['L’équipe la prend en charge', 'Un membre du département s’en occupe et peut vous poser des questions directement dans la demande.'],
                    ['Suivez la réponse', 'Vous êtes prévenu dans la cloche, par notification Windows et par e-mail à chaque étape.'],
                ] as $numero => [$titre, $texte])
                    <li class="flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-nere-50 text-sm font-bold text-nere-700">{{ $numero + 1 }}</span>
                        <span>
                            <span class="block text-sm font-semibold text-graphite-900">{{ $titre }}</span>
                            <span class="mt-0.5 block text-sm leading-6 text-graphite-600">{{ $texte }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
            <a href="{{ $suiviUrl }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-nere-700 hover:text-nere-900">
                {{ $department->code === 'IT' ? 'Suivre mes tickets' : 'Suivre mes demandes' }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-2xl border border-graphite-200 bg-white p-6">
            <h3 class="font-semibold text-graphite-900">Contacter le département</h3>
            @if($responsables->isNotEmpty())
                <ul class="mt-4 space-y-3">
                    @foreach($responsables as $responsable)
                        <li class="flex items-center gap-3">
                            <x-avatar :user="$responsable['user']" size="md" />
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-graphite-900">{{ $responsable['user']->name }}</span>
                                <span class="block truncate text-xs text-graphite-500">{{ $responsable['user']->job_title ?? $responsable['roleLabel'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <dl class="mt-4 space-y-2 text-sm">
                @if($department->phone)
                    <div class="flex items-center gap-2"><dt class="sr-only">Téléphone</dt><svg class="h-4 w-4 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg><dd><a href="tel:{{ $department->phone }}" class="text-graphite-800 hover:text-nere-700">{{ $department->phone }}</a></dd></div>
                @endif
                @if($department->email)
                    <div class="flex items-center gap-2"><dt class="sr-only">E-mail</dt><svg class="h-4 w-4 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><dd class="min-w-0 truncate"><a href="mailto:{{ $department->email }}" class="text-graphite-800 hover:text-nere-700">{{ $department->email }}</a></dd></div>
                @endif
                @if($department->location)
                    <div class="flex items-center gap-2"><dt class="sr-only">Lieu</dt><svg class="h-4 w-4 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><dd class="text-graphite-800">{{ $department->location }}</dd></div>
                @endif
            </dl>
            @if($responsables->isEmpty() && ! $department->phone && ! $department->email && ! $department->location)
                <p class="mt-2 text-sm text-graphite-500">Passez par le service ci-contre : votre demande arrive directement à l’équipe.</p>
            @endif
        </section>

        @if($apercuEquipe->isNotEmpty())
            <section class="rounded-2xl border border-graphite-200 bg-white p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-graphite-900">L’équipe</h3>
                    <span class="rounded-full bg-graphite-100 px-2 py-0.5 text-xs font-semibold text-graphite-600">{{ $memberCount }} membre{{ $memberCount > 1 ? 's' : '' }}</span>
                </div>
                <div class="mt-4 flex -space-x-2">
                    @foreach($apercuEquipe as $membre)
                        <span class="rounded-full ring-2 ring-white" title="{{ $membre['user']->name }}"><x-avatar :user="$membre['user']" size="md" /></span>
                    @endforeach
                    @if($memberCount > $apercuEquipe->count())
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-graphite-100 text-xs font-semibold text-graphite-600 ring-2 ring-white">+{{ $memberCount - $apercuEquipe->count() }}</span>
                    @endif
                </div>
                <button type="button" @click="onglet = 'equipe'; $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
                        class="mt-4 text-sm font-semibold text-nere-700 hover:text-nere-900">Voir toute l’équipe →</button>
            </section>
        @endif
    </aside>
</div>
