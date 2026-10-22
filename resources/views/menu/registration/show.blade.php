@extends('menu.layout')

@section('title', $campaign->title)

@section('content')
    <section class="mb-6 border-b border-graphite-200 pb-6">
        <div class="max-w-3xl">
            <a href="{{ route('menu.portal') }}" class="inline-flex items-center gap-2 text-sm font-medium text-graphite-600 transition hover:text-nere-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m7 7l-7-7 7-7"/></svg>
                Toutes les semaines
            </a>
            <span class="tag-service mt-5 inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                Menu du personnel · Semaine
            </span>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-graphite-950 sm:text-4xl">{{ $campaign->title }}</h1>
            <p class="mt-2 inline-flex items-center gap-2 text-sm text-graphite-600">
                <svg class="h-4 w-4 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                {{ $campaign->starts_on->translatedFormat('l d F Y') }} <span aria-hidden="true">—</span> {{ $campaign->ends_on->translatedFormat('l d F Y') }}
            </p>
        </div>
    </section>

    <section class="mb-6 flex items-start gap-3 rounded-lg border border-graphite-200 bg-white p-4 sm:p-5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-graphite-50 text-nere-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11c0 3-2 5-5 5s-5-2-5-5 2-5 5-5c3 0 5 2 5 5zm0 0c0-3 2-5 5-5s5 2 5 5-2 5-5 5c-3 0-5-2-5-5zM7 16v3m10-3v3"/></svg>
        </span>
        <div>
            <p class="text-sm font-bold text-graphite-900">Bonjour {{ auth()->user()->first_name }}</p>
            <p class="mt-1 text-sm leading-6 text-graphite-600">Indiquez votre département puis choisissez un plat pour chaque jour où vous souhaitez déjeuner. Vos choix ne sont visibles que par vous et le gestionnaire.</p>
        </div>
    </section>

    @if(! $canRegister)
        <div role="status" class="mb-6 flex items-start gap-3 rounded-md border border-or-200 bg-or-50 px-4 py-4 text-sm text-graphite-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-or-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.86L1.82 18a2 2 0 001.7 3h16.96a2 2 0 001.7-3L13.7 3.86a2 2 0 00-3.4 0z"/></svg>
            <span><strong>Inscriptions clôturées.</strong> Vous pouvez consulter les menus et vos choix enregistrés, mais ils ne peuvent plus être modifiés.</span>
        </div>
    @endif

    @if($canRegister)
        <form method="POST" action="{{ route('menu.registration.store', $campaign->link_token) }}" class="space-y-5">
            @csrf
            <section class="rounded-lg border border-graphite-200 bg-white p-5 sm:p-6">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-graphite-50 text-sm font-semibold text-graphite-600">01</span>
                    <div>
                        <h2 class="font-bold text-graphite-900">Votre département</h2>
                        <p class="text-xs text-graphite-500">Requis pour organiser la liste de restauration.</p>
                    </div>
                </div>
                <label class="block max-w-xl">
                    <span class="etiquette">Département <span class="text-nere-600">*</span></span>
                    <input name="department" list="menu-departments" value="{{ old('department', $registration?->department) }}" required maxlength="150" placeholder="Ex. Finances, Sûreté, Zone du Bois" class="champ">
                    <datalist id="menu-departments">
                        @foreach($departments as $department)
                            <option value="{{ $department->name }}">
                        @endforeach
                    </datalist>
                </label>
                <p class="mt-2 flex items-start gap-1.5 text-xs leading-5 text-graphite-500">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11c0 3-2 5-5 5s-5-2-5-5 2-5 5-5c3 0 5 2 5 5zm0 0c0-3 2-5 5-5s5 2 5 5-2 5-5 5c-3 0-5-2-5-5zM7 16v3m10-3v3"/></svg>
                    Votre nom provient de votre compte Active Directory. Vous pouvez choisir une suggestion ou saisir votre département.
                </p>
            </section>

            <div class="flex items-end justify-between gap-3 px-1">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-graphite-900">Vos choix de la semaine</h2>
                    <p class="mt-1 text-sm text-graphite-600">Sélectionnez un plat ou indiquez « Pas de repas ».</p>
                </div>
                <span class="hidden shrink-0 rounded-md bg-graphite-50 px-3 py-1.5 text-xs font-semibold text-graphite-600 sm:inline-flex">{{ $campaign->days->count() }} jours</span>
            </div>

            <div class="space-y-4">
                @foreach($campaign->days as $day)
                    @php($previousChoice = $registration?->choices->firstWhere('menu_day_id', $day->id)?->selected_menu)
                    @php($weekdayNumber = $day->date->dayOfWeekIso)
                    @php($weekdayLabels = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'])
                    <section class="overflow-hidden rounded-lg border border-graphite-200 bg-white">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-graphite-100 px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-graphite-100 text-sm font-semibold text-graphite-700">{{ $day->date->format('d') }}</span>
                                <div>
                                    <h3 class="font-bold text-graphite-900">{{ $weekdayLabels[$weekdayNumber] ?? $day->date->translatedFormat('l') }}</h3>
                                    <p class="text-xs text-graphite-500">{{ $day->date->translatedFormat('d F Y') }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-graphite-50 px-2.5 py-1 text-xs font-medium text-graphite-600">
                                <svg class="h-3.5 w-3.5 text-graphite-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Choix du jour
                            </span>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-5">
                            <label class="group relative flex cursor-pointer rounded-md border border-graphite-200 bg-white p-4 transition-colors hover:border-yellow-400 group-has-[:checked]:border-yellow-500 group-has-[:checked]:bg-yellow-50/60">
                                <input class="peer sr-only" type="radio" name="choices[{{ $day->id }}]" value="1" @checked((string) old("choices.{$day->id}", $previousChoice) === '1')>
                                <span class="flex w-full items-start gap-3">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-graphite-300 text-transparent transition group-has-[:checked]:border-yellow-600 group-has-[:checked]:bg-yellow-500 group-has-[:checked]:text-white">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-graphite-500"><span class="h-2 w-2 rounded-full bg-yellow-400"></span>Menu 1</span>
                                        <span class="mt-1.5 block text-sm font-semibold leading-5 text-graphite-900">{{ $day->menu_one }}</span>
                                    </span>
                                </span>
                            </label>
                            <label class="group relative flex cursor-pointer rounded-md border border-graphite-200 bg-white p-4 transition-colors hover:border-green-400 group-has-[:checked]:border-green-600 group-has-[:checked]:bg-green-50/60">
                                <input class="peer sr-only" type="radio" name="choices[{{ $day->id }}]" value="2" @checked((string) old("choices.{$day->id}", $previousChoice) === '2')>
                                <span class="flex w-full items-start gap-3">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-graphite-300 text-transparent transition group-has-[:checked]:border-green-700 group-has-[:checked]:bg-green-600 group-has-[:checked]:text-white">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-graphite-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>Menu 2</span>
                                        <span class="mt-1.5 block text-sm font-semibold leading-5 text-graphite-900">{{ $day->menu_two }}</span>
                                    </span>
                                </span>
                            </label>
                        </div>
                        <div class="border-t border-graphite-100 px-4 py-3 sm:px-5">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-graphite-600 transition hover:bg-graphite-50 has-[:checked]:font-semibold has-[:checked]:text-graphite-900">
                                <input class="h-4 w-4 border-graphite-300 text-graphite-700 focus:ring-graphite-500" type="radio" name="choices[{{ $day->id }}]" value="" @checked(old("choices.{$day->id}", $previousChoice) === null)>
                                Pas de repas ce jour
                            </label>
                            <span class="ml-2 text-xs text-graphite-500">· Dessert fixe : {{ $day->dessert }}</span>
                        </div>
                    </section>
                @endforeach
            </div>
            <div class="sticky bottom-0 z-20 -mx-4 mt-6 border-t border-graphite-200 bg-white px-4 py-4 sm:static sm:mx-0 sm:flex sm:items-center sm:justify-between sm:gap-4 sm:rounded-lg sm:border sm:px-5">
                <p class="hidden text-sm text-graphite-500 sm:block">Vous pourrez modifier vos choix tant que les inscriptions sont ouvertes.</p>
                <button class="bouton-primaire w-full sm:w-auto">
                    Enregistrer mes choix
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
        </form>
    @else
        <div class="mb-4">
            <h2 class="text-xl font-bold text-graphite-900">Menus de la semaine</h2>
            <p class="mt-1 text-sm text-graphite-600">Les inscriptions pour cette période sont terminées.</p>
        </div>
        <div class="space-y-4">
            @foreach($campaign->days as $day)
                <section class="overflow-hidden rounded-lg border border-graphite-200 bg-white">
                    <div class="flex items-center gap-3 border-b border-graphite-100 px-5 py-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-graphite-100 text-sm font-semibold text-graphite-700">{{ $day->date->format('d') }}</span>
                        <h3 class="font-bold text-graphite-900">{{ $day->date->translatedFormat('l d F Y') }}</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-5">
                        <div class="rounded-md border border-yellow-200 bg-yellow-50/40 p-4">
                            <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-graphite-600"><span class="h-2 w-2 rounded-full bg-yellow-400"></span>Menu 1</span>
                            <p class="mt-2 text-sm font-semibold text-graphite-900">{{ $day->menu_one }}</p>
                        </div>
                        <div class="rounded-md border border-green-200 bg-green-50/40 p-4">
                            <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-graphite-600"><span class="h-2 w-2 rounded-full bg-green-500"></span>Menu 2</span>
                            <p class="mt-2 text-sm font-semibold text-graphite-900">{{ $day->menu_two }}</p>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
        @if($registration)
            <section class="mt-6 rounded-lg border border-green-200 bg-green-50 p-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-green-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>
                    <div>
                        <h2 class="font-bold text-graphite-900">Vos choix enregistrés</h2>
                        <p class="text-sm text-graphite-600">{{ $registration->department }}</p>
                    </div>
                </div>
                <div class="mt-4 divide-y divide-green-200">
                    @foreach($registration->choices as $choice)
                        <p class="py-2 text-sm text-graphite-700"><span class="font-semibold">{{ $choice->day->date->translatedFormat('l d F') }}</span><span class="mx-2 text-graphite-400">·</span>Menu {{ $choice->selected_menu }}</p>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
@endsection
