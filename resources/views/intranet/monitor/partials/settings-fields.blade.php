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
        @php
            $window = old('maintenance_days') !== null
                ? ['days' => array_map('intval', old('maintenance_days', [])), 'start' => old('maintenance_start'), 'end' => old('maintenance_end')]
                : ($model->maintenance ?? null);
            $dayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 0 => 'Dim'];
        @endphp
        <fieldset class="sm:col-span-2">
            <legend class="etiquette">Maintenance planifiée chaque semaine (contrôles et alertes suspendus)</legend>
            <div class="flex flex-wrap items-center gap-3">
                @foreach($dayNames as $value => $label)
                    <label class="flex items-center gap-1.5 text-sm text-graphite-700">
                        <input type="checkbox" name="maintenance_days[]" value="{{ $value }}" @checked(in_array($value, $window['days'] ?? [], true)) class="rounded border-graphite-300">
                        {{ $label }}
                    </label>
                @endforeach
                <span class="text-sm text-graphite-600">de</span>
                <input type="time" name="maintenance_start" value="{{ $window['start'] ?? '' }}" class="champ w-32" aria-label="Début de la maintenance">
                <span class="text-sm text-graphite-600">à</span>
                <input type="time" name="maintenance_end" value="{{ $window['end'] ?? '' }}" class="champ w-32" aria-label="Fin de la maintenance">
            </div>
            <p class="mt-1 text-xs text-graphite-500">Ex. le dimanche de 02:00 à 05:00 pour les mises à jour. Une plage peut passer minuit (22:00 à 02:00). Aucun jour coché = reprendre la plage du niveau au-dessus.</p>
            @foreach(['maintenance_start', 'maintenance_end', 'maintenance_days'] as $field)
                @error($field)<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
            @endforeach
        </fieldset>
        @if($model->snmp_community)
            <label class="flex items-center gap-2 self-end pb-2 text-sm text-graphite-700">
                <input type="checkbox" name="clear_snmp_community" value="1" class="rounded border-graphite-300">
                Effacer et reprendre la communauté héritée
            </label>
        @endif
    </div>
</fieldset>
