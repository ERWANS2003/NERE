@php
    $breadcrumbs = [
        ['label' => $dept->name, 'url' => route('intranet.departments.show', $dept)],
        ['label' => $form->name],
    ];
@endphp

<x-intranet-layout :title="$form->name" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto w-full max-w-2xl">
        <header class="mb-6">
            <p class="tag-service mb-1" style="color: {{ $dept->color }};">{{ $dept->tag }}</p>
            <h1 class="text-2xl font-bold tracking-tight text-graphite-900 sm:text-3xl">{{ $form->name }}</h1>
            @if ($form->description)
                <p class="mt-2 max-w-prose text-sm leading-6 text-graphite-600">{{ $form->description }}</p>
            @endif
        </header>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50/90 p-4 shadow-sm" role="alert">
                <p class="mb-2 text-sm font-semibold text-red-800">Veuillez corriger les erreurs suivantes :</p>
                <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('intranet.submissions.store', $form) }}"
            enctype="multipart/form-data"
            class="carte space-y-5 p-4 sm:p-6"
        >
            @csrf

            @foreach ($fields as $field)
                @php
                    $key = 'field_' . $field->id;
                    $old = old($key);
                    $label = $field->label . ($field->required ? ' *' : '');
                @endphp

                <div class="form-field-group" data-field-id="{{ $field->id }}">
                    @unless ($field->type === 'checkbox')
                        <label for="{{ $key }}" class="etiquette">
                            {{ $label }}
                        </label>
                    @endunless

                    @if ($field->help_text)
                        <p id="{{ $key }}_help" class="mb-1.5 text-xs leading-5 text-graphite-500">{{ $field->help_text }}</p>
                    @endif

                    @switch($field->type)
                        @case('text')
                        @case('number')
                            <input
                                type="{{ $field->type }}"
                                id="{{ $key }}"
                                name="{{ $key }}"
                                value="{{ $old }}"
                                {{ $field->required ? 'required' : '' }}
                                @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                @error($key) aria-invalid="true" @enderror
                                class="champ @error($key) border-red-500 focus:border-red-600 focus:ring-red-600/20 @enderror"
                            >
                            @break

                        @case('textarea')
                            <textarea
                                id="{{ $key }}"
                                name="{{ $key }}"
                                rows="4"
                                {{ $field->required ? 'required' : '' }}
                                @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                @error($key) aria-invalid="true" @enderror
                                class="champ min-h-28 resize-y @error($key) border-red-500 focus:border-red-600 focus:ring-red-600/20 @enderror"
                            >{{ $old }}</textarea>
                            @break

                        @case('date')
                            <input
                                type="date"
                                id="{{ $key }}"
                                name="{{ $key }}"
                                value="{{ $old }}"
                                {{ $field->required ? 'required' : '' }}
                                @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                @error($key) aria-invalid="true" @enderror
                                class="champ @error($key) border-red-500 focus:border-red-600 focus:ring-red-600/20 @enderror"
                            >
                            @break

                        @case('select')
                            <select
                                id="{{ $key }}"
                                name="{{ $key }}"
                                {{ $field->required ? 'required' : '' }}
                                @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                @error($key) aria-invalid="true" @enderror
                                class="champ @error($key) border-red-500 focus:border-red-600 focus:ring-red-600/20 @enderror"
                            >
                                <option value="">— Sélectionner —</option>
                                @foreach ($field->options ?? [] as $option)
                                    <option value="{{ $option['value'] }}" {{ $old === $option['value'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @break

                        @case('multiselect')
                            <select
                                id="{{ $key }}"
                                name="{{ $key }}[]"
                                multiple
                                {{ $field->required ? 'required' : '' }}
                                @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                @error($key) aria-invalid="true" @enderror
                                class="champ min-h-28 @error($key) border-red-500 focus:border-red-600 focus:ring-red-600/20 @enderror"
                            >
                                @foreach ($field->options ?? [] as $option)
                                    <option value="{{ $option['value'] }}" {{ in_array($option['value'], (array) $old) ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-graphite-500">Vous pouvez sélectionner plusieurs valeurs.</p>
                            @break

                        @case('checkbox')
                            <label for="{{ $key }}" class="flex min-h-11 cursor-pointer items-start gap-3 rounded-xl border border-graphite-200 bg-white/55 px-3 py-2.5 transition-colors hover:bg-white/80">
                                <input
                                    type="checkbox"
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    value="1"
                                    {{ $old ? 'checked' : '' }}
                                    {{ $field->required ? 'required' : '' }}
                                    @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                    @error($key) aria-invalid="true" @enderror
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-graphite-300 accent-nere-600"
                                >
                                <span class="text-sm font-medium text-graphite-800">{{ $label }}</span>
                            </label>
                            @break

                        @case('file')
                            @php
                                $accept = implode(',', array_map(
                                    fn ($mime) => '.' . trim($mime),
                                    explode(',', $field->rules['mimes'] ?? 'pdf,jpg,jpeg,png')
                                ));
                                $maxKo = $field->rules['max'] ?? 10240;
                            @endphp
                            <input
                                type="file"
                                id="{{ $key }}"
                                name="{{ $key }}"
                                accept="{{ $accept }}"
                                {{ $field->required ? 'required' : '' }}
                                @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                @error($key) aria-invalid="true" @enderror
                                class="champ cursor-pointer file:mr-3 file:rounded-full file:border-0 file:bg-nere-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-nere-700 hover:file:bg-nere-100 @error($key) border-red-500 focus:border-red-600 focus:ring-red-600/20 @enderror"
                            >
                            <p class="mt-1.5 text-xs leading-5 text-graphite-500">
                                Formats acceptés : {{ $field->rules['mimes'] ?? 'pdf, jpg, jpeg, png' }} ·
                                Taille maximale : {{ round($maxKo / 1024, 0) }} Mo
                            </p>
                            @break

                        @case('user')
                            <input
                                type="text"
                                id="{{ $key }}"
                                name="{{ $key }}"
                                value="{{ $old }}"
                                placeholder="Nom ou matricule de l’utilisateur"
                                {{ $field->required ? 'required' : '' }}
                                @if($field->help_text) aria-describedby="{{ $key }}_help" @endif
                                @error($key) aria-invalid="true" @enderror
                                class="champ @error($key) border-red-500 focus:border-red-600 focus:ring-red-600/20 @enderror"
                            >
                            @break
                    @endswitch

                    @error($key)
                        <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex flex-col-reverse gap-3 border-t border-white/70 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('intranet.departments.show', $dept) }}" class="bouton-secondaire">
                    <span aria-hidden="true">←</span>
                    Retour
                </a>
                <button type="submit" class="bouton-primaire">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5"/>
                    </svg>
                    Soumettre la demande
                </button>
            </div>
        </form>
    </div>
</x-intranet-layout>
