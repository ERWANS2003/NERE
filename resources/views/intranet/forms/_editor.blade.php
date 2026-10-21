@php
    $edition = $form !== null;
    $defaultFields = $form?->form_schema['fields'] ?? [[
        'key' => 'description',
        'label' => 'Détails de la demande',
        'type' => 'textarea',
        'required' => true,
        'help_text' => null,
        'options' => [],
    ]];
    $fields = collect(old('fields', $defaultFields))
        ->filter(fn ($field) => is_array($field))
        ->map(fn (array $field) => array_merge([
            'key' => '',
            'label' => '',
            'type' => 'text',
            'required' => false,
            'help_text' => '',
            'options' => [],
        ], $field, [
            'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
        ]))
        ->values()
        ->all();
@endphp

<form method="POST"
      action="{{ $edition ? route('intranet.forms.update', $form) : route('intranet.forms.store') }}"
      class="space-y-6 rounded-xl border border-graphite-200 bg-white p-5 sm:p-6">
    @csrf
    @if($edition)
        @method('PUT')
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
            <p class="font-semibold text-red-800">Vérifiez les informations du formulaire :</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($edition)
        <input type="hidden" name="department_id" value="{{ $form->department_id }}">
        <div>
            <span class="block text-sm font-medium text-graphite-700">Département</span>
            <p class="mt-1 text-sm text-graphite-600">{{ $form->department->name }}</p>
        </div>
    @else
        <div>
            <x-etiquette for="department_id">Département</x-etiquette>
            <select id="department_id" name="department_id" required class="champ">
                <option value="">Choisir un département</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
            <x-erreur :messages="$errors->get('department_id')" />
        </div>
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-etiquette for="name">Nom du formulaire</x-etiquette>
            <x-champ id="name" name="name" :value="old('name', $form?->name)" required maxlength="255" />
            <x-erreur :messages="$errors->get('name')" />
        </div>
        <div>
            <x-etiquette for="order">Ordre d'affichage</x-etiquette>
            <x-champ id="order" name="order" type="number" min="0" :value="old('order', $form?->order ?? 0)" />
            <x-erreur :messages="$errors->get('order')" />
        </div>
    </div>

    <div>
        <x-etiquette for="description">Description</x-etiquette>
        <textarea id="description" name="description" rows="3" maxlength="2000" class="champ">{{ old('description', $form?->description) }}</textarea>
        <x-erreur :messages="$errors->get('description')" />
    </div>

    <label class="inline-flex items-center gap-2 text-sm font-medium text-graphite-700">
        <input type="checkbox" name="is_active" value="1"
               @checked(old('is_active', $form?->is_active ?? true))
               class="rounded border-graphite-300 text-nere-700 focus:ring-nere-600">
        Publier le formulaire
    </label>

    <section x-data="{
        fields: @js($fields),
        addField() {
            this.fields.push({ key: '', label: '', type: 'text', required: false, help_text: '', options: [] });
        },
        addOption(field) {
            field.options.push({ value: '', label: '' });
        },
        removeOption(field, index) {
            field.options.splice(index, 1);
        },
        fieldKey(label, index) {
            const slug = label.normalize('NFD').replace(/[\\u0300-\\u036f]/g, '')
                .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
            return slug ? `${slug}_${index + 1}` : `champ_${index + 1}`;
        }
    }" class="space-y-4" aria-labelledby="fields-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="fields-title" class="text-lg font-semibold text-graphite-900">Champs du formulaire</h2>
                <p class="mt-1 text-sm text-graphite-500">Les réponses sont contrôlées côté serveur avant l'enregistrement.</p>
            </div>
            <x-button type="button" variant="secondary" x-on:click="addField()">Ajouter un champ</x-button>
        </div>
        <x-erreur :messages="$errors->get('fields')" />

        <template x-for="(field, index) in fields" :key="index">
            <fieldset class="space-y-4 rounded-lg border border-graphite-200 p-4">
                <legend class="px-1 text-sm font-semibold text-graphite-700">
                    Champ <span x-text="index + 1"></span>
                </legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label :for="`field-label-${index}`" class="mb-1 block text-sm font-medium text-graphite-700">Libellé</label>
                        <input :id="`field-label-${index}`" x-model="field.label"
                               x-on:blur="if (!field.key) field.key = fieldKey(field.label, index)"
                               x-bind:name="`fields[${index}][label]`" required maxlength="255" class="champ">
                        @error('fields.*.label')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label :for="`field-key-${index}`" class="mb-1 block text-sm font-medium text-graphite-700">Identifiant technique</label>
                        <input :id="`field-key-${index}`" x-model="field.key"
                               x-bind:name="`fields[${index}][key]`" required pattern="[A-Za-z0-9_-]+" maxlength="64" class="champ">
                        @error('fields.*.key')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label :for="`field-type-${index}`" class="mb-1 block text-sm font-medium text-graphite-700">Type de champ</label>
                        <select :id="`field-type-${index}`" x-model="field.type"
                                x-bind:name="`fields[${index}][type]`" required class="champ">
                            <option value="text">Texte</option>
                            <option value="textarea">Texte long</option>
                            <option value="number">Nombre</option>
                            <option value="date">Date</option>
                            <option value="time">Heure</option>
                            <option value="datetime-local">Date et heure</option>
                            <option value="select">Liste de choix</option>
                        </select>
                    </div>
                    <div>
                        <label :for="`field-help-${index}`" class="mb-1 block text-sm font-medium text-graphite-700">Aide (facultatif)</label>
                        <input :id="`field-help-${index}`" x-model="field.help_text"
                               x-bind:name="`fields[${index}][help_text]`" maxlength="500" class="champ">
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-graphite-700">
                    <input type="hidden" x-bind:name="`fields[${index}][required]`" value="0">
                    <input type="checkbox" x-model="field.required" x-bind:name="`fields[${index}][required]`"
                           value="1" class="rounded border-graphite-300 text-nere-700 focus:ring-nere-600">
                    Champ obligatoire
                </label>

                <div x-show="field.type === 'select'" x-cloak class="space-y-3 rounded-md bg-graphite-50 p-3">
                    <h3 class="text-sm font-medium text-graphite-800">Options</h3>
                    <template x-for="(option, optionIndex) in field.options" :key="optionIndex">
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            <label class="sr-only" :for="`option-value-${index}-${optionIndex}`">Valeur de l'option</label>
                            <input :id="`option-value-${index}-${optionIndex}`" x-model="option.value"
                                   x-bind:name="`fields[${index}][options][${optionIndex}][value]`"
                                   required maxlength="255" placeholder="Valeur" class="champ">
                            <label class="sr-only" :for="`option-label-${index}-${optionIndex}`">Libellé de l'option</label>
                            <input :id="`option-label-${index}-${optionIndex}`" x-model="option.label"
                                   x-bind:name="`fields[${index}][options][${optionIndex}][label]`"
                                   required maxlength="255" placeholder="Libellé affiché" class="champ">
                            <x-button type="button" variant="danger" x-on:click="removeOption(field, optionIndex)">Retirer</x-button>
                        </div>
                    </template>
                    <x-button type="button" variant="secondary" x-on:click="addOption(field)">Ajouter une option</x-button>
                    @error('fields.*.options')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </fieldset>
        </template>
    </section>

    <div class="flex flex-wrap justify-end gap-3 border-t border-graphite-100 pt-5">
        <x-button :href="route('intranet.forms.index')" variant="secondary">Annuler</x-button>
        <x-button type="submit">{{ $edition ? 'Enregistrer les modifications' : 'Créer le formulaire' }}</x-button>
    </div>
</form>
