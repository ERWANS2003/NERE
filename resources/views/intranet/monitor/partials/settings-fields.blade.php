{{-- Réglages hérités : vide = valeur du groupe parent (ou valeur par défaut). $inherited donne la valeur héritée. --}}
<fieldset class="sm:col-span-2">
    <legend class="mb-1 font-semibold text-graphite-900">Réglages hérités</legend>
    <p class="mb-3 text-sm text-graphite-600">Laissez vide pour reprendre la valeur du niveau au-dessus, indiquée entre parenthèses.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach([
            'interval_seconds' => ['Intervalle de contrôle (secondes)', 30],
            'notify_delay_minutes' => ['Prévenir après une panne de (minutes)', 0],
            'notify_repeat_minutes' => ['Répéter l’alerte toutes les (minutes, 0 = jamais)', 0],
            'escalate_after_minutes' => ['Escalader aux responsables après (minutes, 0 = jamais)', 0],
        ] as $field => [$label, $min])
            <label class="block">
                <span class="etiquette">{{ $label }}</span>
                <input type="number" name="{{ $field }}" min="{{ $min }}" value="{{ old($field, $model->{$field}) }}" placeholder="({{ $inherited($field) }})" class="champ">
                @error($field)<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            </label>
        @endforeach
        <label class="block">
            <span class="etiquette">Communauté SNMP (lecture seule)</span>
            <input type="password" name="snmp_community" maxlength="64" autocomplete="off"
                   placeholder="{{ $model->snmp_community ? 'Définie, laissez vide pour la garder' : 'Héritée' }}" class="champ">
            @error('snmp_community')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        @if($model->snmp_community)
            <label class="flex items-center gap-2 self-end pb-2 text-sm text-graphite-700">
                <input type="checkbox" name="clear_snmp_community" value="1" class="rounded border-graphite-300">
                Effacer et reprendre la communauté héritée
            </label>
        @endif
    </div>
</fieldset>
