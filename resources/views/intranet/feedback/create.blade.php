<x-intranet-layout title="Donner mon avis">

    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-graphite-900 mb-2">Donner mon avis</h1>
        <p class="mb-6 text-graphite-600">Une idée, un problème, quelque chose qui vous plaît ? Votre avis aide à améliorer l’intranet.</p>

        @if(session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('intranet.feedback.store') }}" class="bg-white rounded-xl border border-graphite-200 p-8 space-y-6">
            @csrf

            <div>
                <x-etiquette for="category">Type d’avis</x-etiquette>
                <select id="category" name="category" required class="champ">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', 'suggestion') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-erreur :messages="$errors->get('category')" />
            </div>

            <fieldset>
                <legend class="mb-2 block text-sm font-medium text-graphite-800">Note de l’intranet <span class="font-normal text-graphite-500">(facultatif)</span></legend>
                <div class="flex flex-wrap gap-2">
                    @foreach([1, 2, 3, 4, 5] as $note)
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-graphite-200 px-3 py-2 text-sm has-[:checked]:border-nere-500 has-[:checked]:bg-nere-50">
                            <input type="radio" name="rating" value="{{ $note }}" @checked((string) old('rating') === (string) $note) class="sr-only">
                            <span aria-hidden="true" class="text-or-500">{{ str_repeat('★', $note) }}</span>
                            <span class="sr-only">{{ $note }} sur 5</span>
                        </label>
                    @endforeach
                </div>
                <x-erreur :messages="$errors->get('rating')" />
            </fieldset>

            <div>
                <x-etiquette for="message">Votre message</x-etiquette>
                <textarea id="message" name="message" rows="6" required maxlength="5000" class="champ">{{ old('message') }}</textarea>
                <x-erreur :messages="$errors->get('message')" />
            </div>

            <label class="flex items-center gap-2 text-sm text-graphite-700">
                <input type="checkbox" name="anonymous" value="1" @checked(old('anonymous'))>
                Envoyer anonymement (votre nom ne sera pas transmis)
            </label>

            <div class="pt-2">
                <x-bouton-primaire>Envoyer mon avis</x-bouton-primaire>
            </div>
        </form>

        @if($myFeedback->isNotEmpty())
            <section class="mt-8">
                <h2 class="mb-3 text-lg font-semibold text-graphite-900">Mes derniers avis</h2>
                <ul class="space-y-3">
                    @foreach($myFeedback as $item)
                        <li class="rounded-xl border border-graphite-200 bg-white p-4 text-sm">
                            <div class="mb-1 flex flex-wrap items-center justify-between gap-2 text-xs text-graphite-500">
                                <span>{{ $categories[$item->category] ?? $item->category }} · {{ $item->created_at->format('d/m/Y') }}</span>
                                <span class="font-semibold">{{ \App\Models\Feedback::STATUSES[$item->status] ?? $item->status }}</span>
                            </div>
                            <p class="whitespace-pre-line text-graphite-800">{{ $item->message }}</p>
                            @if($item->admin_note)
                                <p class="mt-2 rounded-lg bg-graphite-50 px-3 py-2 text-graphite-700"><span class="font-semibold">Réponse :</span> {{ $item->admin_note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

</x-intranet-layout>
