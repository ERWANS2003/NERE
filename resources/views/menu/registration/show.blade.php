@extends('menu.layout')

@section('title', $campaign->title)

@section('content')
    <div class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        <p class="text-sm font-medium uppercase tracking-wide text-red-700">Menu du personnel</p>
        <h1 class="mt-1 text-2xl font-semibold">{{ $campaign->title }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $campaign->starts_on->format('d/m/Y') }} – {{ $campaign->ends_on->format('d/m/Y') }}</p>
        <p class="mt-4 text-sm">Bonjour <strong>{{ auth()->user()->name }}</strong>. Renseignez votre département et choisissez un menu pour les jours où vous souhaitez déjeuner.</p>
        @if(! $canRegister)
            <p class="mt-3 rounded bg-amber-50 px-3 py-2 text-sm text-amber-800">Les inscriptions pour cette semaine sont clôturées.</p>
        @endif
    </div>

    @if($canRegister)
        <form method="POST" action="{{ route('menu.registration.store', $campaign->link_token) }}">
            @csrf
            <section class="mb-5 rounded-lg bg-white p-5 shadow-sm">
                <label class="block max-w-md">
                    <span class="mb-1 block text-sm font-medium">Votre département</span>
                    <input name="department" list="menu-departments" value="{{ old('department', $registration?->department) }}" required maxlength="150" placeholder="Ex. Finances, Sûreté, Zone du Bois" class="w-full rounded border-gray-300">
                    <datalist id="menu-departments">
                        @foreach($departments as $department)
                            <option value="{{ $department->name }}">
                        @endforeach
                    </datalist>
                </label>
                <p class="mt-2 text-xs text-gray-500">Le nom affiché provient de votre compte Active Directory. Le département peut être choisi parmi les suggestions ou saisi librement.</p>
            </section>

            <div class="space-y-4">
                @foreach($campaign->days as $day)
                    @php($previousChoice = $registration?->choices->firstWhere('menu_day_id', $day->id)?->selected_menu)
                    <section class="rounded-lg bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="font-semibold">{{ $day->date->translatedFormat('l d F Y') }}</h2>
                            <span class="text-xs text-gray-500">Dessert : {{ $day->dessert }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label class="flex cursor-pointer gap-3 rounded border p-4 hover:border-red-400">
                                <input type="radio" name="choices[{{ $day->id }}]" value="1" @checked((string) old("choices.{$day->id}", $previousChoice) === '1')>
                                <span><strong>Menu 1</strong><span class="mt-1 block text-sm text-gray-700">{{ $day->menu_one }}</span></span>
                            </label>
                            <label class="flex cursor-pointer gap-3 rounded border p-4 hover:border-red-400">
                                <input type="radio" name="choices[{{ $day->id }}]" value="2" @checked((string) old("choices.{$day->id}", $previousChoice) === '2')>
                                <span><strong>Menu 2</strong><span class="mt-1 block text-sm text-gray-700">{{ $day->menu_two }}</span></span>
                            </label>
                        </div>
                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-600">
                            <input type="radio" name="choices[{{ $day->id }}]" value="" @checked(old("choices.{$day->id}", $previousChoice) === null)>
                            Pas de repas ce jour
                        </label>
                    </section>
                @endforeach
            </div>
            <div class="mt-5 flex justify-end">
                <button class="rounded bg-red-700 px-5 py-2 font-medium text-white hover:bg-red-800">Enregistrer mes choix</button>
            </div>
        </form>
    @else
        <div class="space-y-4">
            @foreach($campaign->days as $day)
                <section class="rounded-lg bg-white p-5 shadow-sm">
                    <h2 class="font-semibold">{{ $day->date->translatedFormat('l d F Y') }} <span class="font-normal text-gray-500">· Dessert : {{ $day->dessert }}</span></h2>
                    <p class="mt-2 text-sm">Menu 1 : {{ $day->menu_one }}</p>
                    <p class="mt-1 text-sm">Menu 2 : {{ $day->menu_two }}</p>
                </section>
            @endforeach
        </div>
        @if($registration)
            <section class="mt-5 rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">Vos choix enregistrés · {{ $registration->department }}</h2>
                @foreach($registration->choices as $choice)
                    <p class="mt-2 text-sm">{{ $choice->day->date->translatedFormat('l d F') }} : Menu {{ $choice->selected_menu }}</p>
                @endforeach
            </section>
        @endif
    @endif
@endsection
