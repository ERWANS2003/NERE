<x-intranet-layout title="Menu de la semaine">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-graphite-900 mb-2">Menu du personnel</h1>
        <p class="text-graphite-600">Choisissez votre repas pour chaque jour de la semaine à venir.</p>
    </div>

    @if(! $menu)
        <div class="bg-white rounded-xl border border-graphite-200 p-10 text-center">
            <p class="text-graphite-600">Aucun menu n'est publié pour le moment.</p>
            <p class="text-sm text-graphite-500 mt-1">Vous serez notifié dès qu'un nouveau menu est disponible.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-graphite-200 overflow-hidden">
            <div class="p-5 border-b border-graphite-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-graphite-900">
                        Semaine du {{ $menu->week_start->format('d/m/Y') }} au {{ $menu->week_end->format('d/m/Y') }}
                    </h2>
                    <p class="text-sm text-graphite-500">{{ $menu->location->name }}</p>
                </div>
                @if($open)
                    <span class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1 text-sm text-green-700">
                        À remplir avant le {{ $menu->response_deadline->format('d/m/Y H:i') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 rounded-full bg-graphite-100 px-3 py-1 text-sm text-graphite-600">
                        Période de réponse close
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('intranet.menu.store', $menu) }}" class="p-5">
                @csrf

                <div class="space-y-4">
                    @foreach($menu->days as $day)
                        @php $current = $choices[$day->id] ?? null; @endphp
                        <div class="rounded-lg border border-graphite-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-graphite-900">{{ $day->day_name }}</h3>
                                <span class="text-xs text-graphite-500">{{ $day->menu_date->format('d/m/Y') }} · Dessert : {{ $day->dessert }}</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                @foreach([
                                    ['value' => 'menu_1', 'label' => 'Menu 1', 'desc' => $day->menu_1],
                                    ['value' => 'menu_2', 'label' => 'Menu 2', 'desc' => $day->menu_2],
                                    ['value' => 'no_meal', 'label' => 'Aucun repas', 'desc' => 'Je ne prends pas de repas ce jour'],
                                ] as $option)
                                    <label class="flex items-start gap-2 rounded-lg border p-3 cursor-pointer transition
                                        {{ $current === $option['value'] ? 'border-nere-500 bg-nere-50' : 'border-graphite-200 hover:border-graphite-300' }}
                                        {{ $open ? '' : 'opacity-70 cursor-not-allowed' }}">
                                        <input type="radio" name="choices[{{ $day->id }}]" value="{{ $option['value'] }}"
                                               @checked($current === $option['value']) @disabled(! $open)
                                               class="mt-1 text-nere-600 focus:ring-nere-500" required>
                                        <span>
                                            <span class="block text-sm font-semibold text-graphite-900">{{ $option['label'] }}</span>
                                            <span class="block text-xs text-graphite-500">{{ $option['desc'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($open)
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="bouton-primaire">Enregistrer mes choix</button>
                    </div>
                @endif
            </form>
        </div>
    @endif

</x-intranet-layout>
