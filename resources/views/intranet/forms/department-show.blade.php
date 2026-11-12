@php
    $breadcrumbs = [
        ['label' => $form->department->name, 'url' => route('intranet.departments.show', $form->department->code)],
        ['label' => $form->name],
    ];
@endphp

<x-intranet-layout :title="$form->name" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto max-w-3xl">
        <header class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-nere-700">{{ $form->department->name }}</p>
            <h1 class="mt-1 text-3xl font-bold text-graphite-900">{{ $form->name }}</h1>
            @if($form->description)
                <p class="mt-2 text-graphite-600">{{ $form->description }}</p>
            @endif
            <p class="mt-3 text-sm text-graphite-500">Votre demande sera transmise à l’équipe du département pour traitement.</p>
        </header>

        <form method="POST" action="{{ route('intranet.submissions.store', $form) }}" data-tour="formulaire-champs" class="space-y-5 rounded-xl border border-graphite-200 bg-white p-6">
            @csrf
            @foreach($fields as $field)
                @php
                    $key = $field['key'];
                    $name = 'data['.$key.']';
                    $errorKey = 'data.'.$key;
                @endphp
                @if(($field['type'] ?? 'text') === 'items')
                    @php
                        $lignes = collect(old($errorKey, []))->map(fn ($l) => ['article' => $l['article'] ?? '', 'quantite' => $l['quantite'] ?? 1])->values()->all() ?: [['article' => '', 'quantite' => 1]];
                        $suggestions = 'suggestions-'.$key;
                    @endphp
                    <fieldset x-data="{ lignes: @js($lignes) }" aria-describedby="{{ $key }}-aide">
                        <legend class="mb-1 block text-sm font-medium text-graphite-800">
                            {{ $field['label'] }}@if($field['required'] ?? false)<span class="text-red-600"> *</span>@endif
                        </legend>
                        <p id="{{ $key }}-aide" class="mb-3 text-sm text-graphite-500">
                            {{ filled($field['help_text'] ?? null) ? $field['help_text'] : 'Une ligne par article : choisissez dans la liste ou saisissez-le, puis indiquez la quantité.' }}
                        </p>
                        @if(! empty($field['options']))
                            <datalist id="{{ $suggestions }}">
                                @foreach($field['options'] as $option)
                                    <option value="{{ $option['label'] }}">
                                @endforeach
                            </datalist>
                        @endif
                        <div class="space-y-2">
                            <div class="hidden grid-cols-[1fr_7rem_2.5rem] gap-2 px-1 text-xs font-semibold uppercase tracking-wide text-graphite-500 sm:grid">
                                <span>Article</span><span>Quantité</span><span></span>
                            </div>
                            <template x-for="(ligne, i) in lignes" :key="i">
                                <div class="grid grid-cols-[1fr_5.5rem_2.5rem] gap-2 sm:grid-cols-[1fr_7rem_2.5rem]">
                                    <input x-model="ligne.article" :name="'{{ $name }}[' + i + '][article]'" :aria-label="'Article ' + (i + 1)" data-article
                                           @if(! empty($field['options'])) list="{{ $suggestions }}" @endif
                                           maxlength="255" placeholder="Ex. Ramette de papier A4" class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                                    <input x-model="ligne.quantite" :name="'{{ $name }}[' + i + '][quantite]'" :aria-label="'Quantité de l’article ' + (i + 1)"
                                           type="number" min="1" max="100000" step="1" class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                                    <button type="button" @click="lignes.length > 1 ? lignes.splice(i, 1) : (lignes[0] = { article: '', quantite: 1 })"
                                            class="flex h-10 w-10 items-center justify-center rounded-lg text-graphite-400 hover:bg-red-50 hover:text-red-700" :aria-label="'Retirer l’article ' + (i + 1)">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="lignes.push({ article: '', quantite: 1 }); $nextTick(() => [...$root.querySelectorAll('[data-article]')].pop()?.focus())"
                                x-show="lignes.length < {{ \App\Support\DynamicFormSchema::MAX_ITEMS }}"
                                class="mt-3 inline-flex items-center gap-2 rounded-lg border border-dashed border-nere-300 px-3 py-2 text-sm font-semibold text-nere-700 hover:bg-nere-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Ajouter un article
                        </button>
                        <p class="mt-2 text-xs text-graphite-500" x-text="lignes.filter(l => l.article.trim()).length + ' article(s) · ' + lignes.reduce((t, l) => t + (l.article.trim() ? Number(l.quantite) || 0 : 0), 0) + ' unité(s) au total'"></p>
                        @error($errorKey)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        @if($errors->has($errorKey.'.*'))<p class="mt-1 text-sm text-red-700">Vérifiez chaque ligne : un article et une quantité d’au moins 1.</p>@endif
                    </fieldset>
                    @continue
                @endif
                <div>
                    <label for="{{ $key }}" class="mb-1 block text-sm font-medium text-graphite-800">
                        {{ $field['label'] }}@if($field['required'] ?? false)<span class="text-red-600"> *</span>@endif
                    </label>
                    @if(($field['type'] ?? 'text') === 'textarea')
                        <textarea id="{{ $key }}" name="{{ $name }}" rows="4" @required($field['required'] ?? false) class="w-full rounded-lg border border-graphite-300 px-3 py-2">{{ old($errorKey) }}</textarea>
                    @elseif(($field['type'] ?? 'text') === 'select')
                        <select id="{{ $key }}" name="{{ $name }}" @required($field['required'] ?? false) class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                            <option value="">Choisir une option</option>
                            @foreach($field['options'] ?? [] as $option)
                                <option value="{{ $option['value'] }}" @selected(old($errorKey) === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            id="{{ $key }}"
                            name="{{ $name }}"
                            type="{{ $field['type'] }}"
                            value="{{ old($errorKey) }}"
                            @if(($field['type'] ?? '') === 'number') step="any" @endif
                            @required($field['required'] ?? false)
                            class="w-full rounded-lg border border-graphite-300 px-3 py-2"
                        >
                    @endif
                    @if(filled($field['help_text'] ?? null))
                        <p class="mt-1 text-sm text-graphite-500">{{ $field['help_text'] }}</p>
                    @endif
                    @error($errorKey)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <div class="flex justify-end gap-3 border-t border-graphite-100 pt-5" data-tour="formulaire-envoyer">
                <a href="{{ route('intranet.departments.show', $form->department->code) }}" class="rounded-lg border border-graphite-300 px-4 py-2 text-sm text-graphite-700 hover:bg-graphite-50">Annuler</a>
                <button type="submit" class="rounded-lg bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Envoyer la demande</button>
            </div>
        </form>
    </div>
</x-intranet-layout>
