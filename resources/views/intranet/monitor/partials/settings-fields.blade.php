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
        @php $v3 = $model->snmp_v3 ?? null; @endphp
        <fieldset class="sm:col-span-2 rounded-lg border border-graphite-200 p-4" x-data="{ on: @js(filled(old('snmp_v3_user')) || filled($v3)), priv: @js(old('snmp_v3_priv', $v3['priv'] ?? 'AES')) }">
            <legend class="px-1 text-sm font-semibold text-graphite-900">SNMPv3 (chiffré, recommandé)</legend>
            @if($v3)
                <p class="mb-3 text-sm text-green-800">SNMPv3 actif : utilisateur « {{ $v3['user'] }} », {{ ['SHA' => 'SHA-1', 'SHA256' => 'SHA-256', 'SHA384' => 'SHA-384', 'SHA512' => 'SHA-512'][$v3['auth']] ?? $v3['auth'] }}{{ ($v3['priv'] ?? 'AES') === 'AES' ? ' + AES' : ', sans chiffrement' }}. Il remplace la communauté. Laissez les mots de passe vides pour les garder.</p>
                <label class="mb-3 flex items-center gap-2 text-sm text-graphite-700">
                    <input type="checkbox" name="clear_snmp_v3" value="1" class="rounded border-graphite-300">
                    Désactiver SNMPv3 ici (reprendre le réglage du niveau au-dessus)
                </label>
            @else
                <label class="mb-3 flex items-center gap-2 text-sm text-graphite-700">
                    <input type="checkbox" x-model="on" class="rounded border-graphite-300">
                    Utiliser SNMPv3 au lieu de la communauté (les équipements doivent avoir le même utilisateur)
                </label>
            @endif
            <div class="grid gap-4 sm:grid-cols-2" x-show="on" x-cloak>
                <label class="block">
                    <span class="etiquette">Utilisateur</span>
                    <input name="snmp_v3_user" value="{{ old('snmp_v3_user', $v3['user'] ?? '') }}" maxlength="32" autocomplete="off" placeholder="supervision" class="champ" :disabled="! on">
                    @error('snmp_v3_user')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block">
                    <span class="etiquette">Authentification</span>
                    <select name="snmp_v3_auth" class="champ" :disabled="! on">
                        @foreach(['SHA256' => 'SHA-256 (recommandé)', 'SHA512' => 'SHA-512', 'SHA384' => 'SHA-384', 'SHA' => 'SHA-1 (ancien)'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('snmp_v3_auth', $v3['auth'] ?? 'SHA256') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="text-xs text-graphite-500 sm:col-span-2">Choisissez le même algorithme que sur l’équipement (« Hash » sur les Kyocera). Si l’équipement n’a qu’un seul mot de passe, saisissez-le dans les deux champs.</p>
                <label class="block">
                    <span class="etiquette">Mot de passe d’authentification</span>
                    <input type="password" name="snmp_v3_auth_password" minlength="8" maxlength="64" autocomplete="new-password" placeholder="{{ $v3 ? 'Inchangé' : '8 caractères au moins' }}" class="champ" :disabled="! on">
                    @error('snmp_v3_auth_password')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="block">
                    <span class="etiquette">Chiffrement</span>
                    <select name="snmp_v3_priv" x-model="priv" class="champ" :disabled="! on">
                        <option value="AES">AES-128 (recommandé)</option>
                        <option value="none">Aucun (authentification seule)</option>
                    </select>
                </label>
                <label class="block" x-show="priv === 'AES'">
                    <span class="etiquette">Mot de passe de chiffrement</span>
                    <input type="password" name="snmp_v3_priv_password" minlength="8" maxlength="64" autocomplete="new-password" placeholder="{{ $v3 ? 'Inchangé' : '8 caractères au moins' }}" class="champ" :disabled="! on || priv !== 'AES'">
                    @error('snmp_v3_priv_password')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
            </div>
        </fieldset>
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
