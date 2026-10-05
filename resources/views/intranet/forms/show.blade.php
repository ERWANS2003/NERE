@extends('intranet.layouts.app')

@section('titre', $form->name)

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <a href="{{ route('intranet.departments.show', $dept) }}" class="hover:underline" style="color: var(--brand);">
        {{ $dept->name }}
    </a>
    <span class="mx-1 opacity-40">/</span>
    <span>{{ $form->name }}</span>
@endsection

@section('content')

<div class="mx-auto max-w-2xl">

    {{-- En-tête --}}
    <div class="mb-6">
        <p class="text-[10px] font-extrabold uppercase tracking-widest mb-1"
           style="color: {{ $dept->color }};">{{ $dept->tag }}</p>
        <h1 class="text-xl font-bold" style="color: var(--text-strong);">{{ $form->name }}</h1>
        @if ($form->description)
            <p class="mt-1 text-sm" style="color: var(--text-muted);">{{ $form->description }}</p>
        @endif
    </div>

    {{-- Erreurs de validation --}}
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="mb-2 text-sm font-semibold text-red-700">Veuillez corriger les erreurs suivantes :</p>
            <ul class="list-disc pl-5 text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulaire --}}
    <form method="POST"
          action="{{ route('intranet.submissions.store', $form) }}"
          enctype="multipart/form-data"
          class="rounded-2xl border p-6 space-y-5"
          style="background-color: var(--surface-card); border-color: var(--line-subtle);">
        @csrf

        @foreach ($fields as $field)
            @php
                $key   = 'field_' . $field->id;
                $old   = old($key);
                $label = $field->label . ($field->required ? ' *' : '');
            @endphp

            {{-- Condition d'affichage (JS géré côté client si besoin) --}}
            <div class="form-field-group" data-field-id="{{ $field->id }}">
                <label for="{{ $key }}"
                       class="mb-1.5 block text-sm font-semibold"
                       style="color: var(--text-body);">
                    {{ $label }}
                </label>

                @if ($field->help_text)
                    <p class="mb-1.5 text-xs" style="color: var(--text-muted);">{{ $field->help_text }}</p>
                @endif

                @switch($field->type)

                    @case('text')
                    @case('number')
                        <input type="{{ $field->type }}"
                               id="{{ $key }}"
                               name="{{ $key }}"
                               value="{{ $old }}"
                               {{ $field->required ? 'required' : '' }}
                               class="nm-input w-full @error($key) border-red-400 @enderror">
                        @break

                    @case('textarea')
                        <textarea id="{{ $key }}"
                                  name="{{ $key }}"
                                  rows="4"
                                  {{ $field->required ? 'required' : '' }}
                                  class="nm-textarea w-full @error($key) border-red-400 @enderror">{{ $old }}</textarea>
                        @break

                    @case('date')
                        <input type="date"
                               id="{{ $key }}"
                               name="{{ $key }}"
                               value="{{ $old }}"
                               {{ $field->required ? 'required' : '' }}
                               class="nm-input w-full @error($key) border-red-400 @enderror">
                        @break

                    @case('select')
                        <select id="{{ $key }}"
                                name="{{ $key }}"
                                {{ $field->required ? 'required' : '' }}
                                class="nm-input w-full @error($key) border-red-400 @enderror">
                            <option value="">— Sélectionner —</option>
                            @foreach ($field->options ?? [] as $opt)
                                <option value="{{ $opt['value'] }}"
                                        {{ $old === $opt['value'] ? 'selected' : '' }}>
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @break

                    @case('multiselect')
                        <select id="{{ $key }}"
                                name="{{ $key }}[]"
                                multiple
                                {{ $field->required ? 'required' : '' }}
                                class="nm-input w-full min-h-[100px] @error($key) border-red-400 @enderror">
                            @foreach ($field->options ?? [] as $opt)
                                <option value="{{ $opt['value'] }}"
                                        {{ in_array($opt['value'], (array) $old) ? 'selected' : '' }}>
                                    {{ $opt['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs" style="color: var(--text-subtle);">
                            Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs valeurs.
                        </p>
                        @break

                    @case('checkbox')
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   id="{{ $key }}"
                                   name="{{ $key }}"
                                   value="1"
                                   {{ $old ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 accent-amber-500">
                            <span class="text-sm" style="color: var(--text-body);">{{ $field->label }}</span>
                        </label>
                        @break

                    @case('file')
                        @php
                            $accept = implode(',', array_map(
                                fn($m) => '.' . trim($m),
                                explode(',', $field->rules['mimes'] ?? 'pdf,jpg,jpeg,png')
                            ));
                            $maxKo = $field->rules['max'] ?? 10240;
                        @endphp
                        <input type="file"
                               id="{{ $key }}"
                               name="{{ $key }}"
                               accept="{{ $accept }}"
                               {{ $field->required ? 'required' : '' }}
                               class="nm-input w-full file:mr-3 file:rounded-lg file:border-0 file:bg-amber-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-amber-700 hover:file:bg-amber-100 @error($key) border-red-400 @enderror">
                        <p class="mt-1 text-xs" style="color: var(--text-subtle);">
                            Formats acceptés : {{ $field->rules['mimes'] ?? 'pdf, jpg, jpeg, png' }} ·
                            Taille max : {{ round($maxKo / 1024, 0) }} Mo
                        </p>
                        @break

                    @case('user')
                        {{-- Sélecteur d'utilisateur simplifié (champ texte libre pour le MVP) --}}
                        <input type="text"
                               id="{{ $key }}"
                               name="{{ $key }}"
                               value="{{ $old }}"
                               placeholder="Nom ou matricule de l'utilisateur"
                               {{ $field->required ? 'required' : '' }}
                               class="nm-input w-full @error($key) border-red-400 @enderror">
                        @break

                @endswitch

                @error($key)
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        {{-- Boutons --}}
        <div class="flex items-center justify-between gap-3 pt-2 border-t" style="border-color: var(--line-subtle);">
            <a href="{{ route('intranet.departments.show', $dept) }}"
               class="nm-btn nm-btn-secondary nm-btn-sm">
                ← Retour
            </a>
            <button type="submit" class="nm-btn nm-btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                </svg>
                Soumettre la demande
            </button>
        </div>
    </form>
</div>

@endsection
