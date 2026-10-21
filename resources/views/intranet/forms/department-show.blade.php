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

        <form method="POST" action="{{ route('intranet.submissions.store', $form) }}" class="space-y-5 rounded-xl border border-graphite-200 bg-white p-6">
            @csrf
            @foreach($fields as $field)
                @php
                    $key = $field['key'];
                    $name = 'data['.$key.']';
                    $errorKey = 'data.'.$key;
                @endphp
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

            <div class="flex justify-end gap-3 border-t border-graphite-100 pt-5">
                <a href="{{ route('intranet.departments.show', $form->department->code) }}" class="rounded-lg border border-graphite-300 px-4 py-2 text-sm text-graphite-700 hover:bg-graphite-50">Annuler</a>
                <button type="submit" class="rounded-lg bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Envoyer la demande</button>
            </div>
        </form>
    </div>
</x-intranet-layout>
