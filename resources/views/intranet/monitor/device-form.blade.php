@php
    $title = $device->exists ? 'Modifier '.$device->name : 'Ajouter un équipement';
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => $title]];
    $group = \App\Models\MonitorGroup::find(old('group_id', $device->group_id)) ?? \App\Models\MonitorGroup::query()->first();
    $inherited = fn (string $field) => $field === 'snmp_community' ? '' : $group?->setting($field);
    $templates = collect(\App\Enums\MonitorDeviceType::cases())->mapWithKeys(fn ($type) => [$type->value => [
        'base' => collect($type->template(false))->pluck('name')->join(', '),
        'snmp' => collect(array_slice($type->template(true), count($type->template(false))))->pluck('name')->join(', '),
    ]]);
    $advanced = collect(\App\Models\MonitorGroup::SETTINGS)->contains(fn ($field) => $errors->has($field) || filled($device->{$field}));
@endphp

<x-intranet-layout :title="$title" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header :title="$title" />

    <form method="POST" action="{{ $device->exists ? route('intranet.monitor.devices.update', $device) : route('intranet.monitor.devices.store') }}" class="carte grid max-w-4xl gap-4 p-6 sm:grid-cols-2"
          x-data="{ type: @js(old('type', $device->type?->value ?? 'camera')), snmp: @js((bool) old('snmp')), templates: @js($templates) }">
        @csrf
        @if($device->exists) @method('PUT') @endif
        <label class="block">
            <span class="etiquette">Nom</span>
            <input name="name" value="{{ old('name', $device->name) }}" required maxlength="120" placeholder="Caméra portail nord" class="champ">
            @error('name')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="block">
            <span class="etiquette">Adresse IP ou nom</span>
            <input name="host" value="{{ old('host', $device->host) }}" required maxlength="253" placeholder="192.168.10.50" class="champ">
            @error('host')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="block">
            <span class="etiquette">Type</span>
            <select name="type" required class="champ" x-model="type">
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $device->type?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="etiquette">Groupe</span>
            <select name="group_id" required class="champ">
                @foreach($groups as $id => $path)
                    <option value="{{ $id }}" @selected((string) old('group_id', $device->group_id) === (string) $id)>{{ $path }}</option>
                @endforeach
            </select>
        </label>
        <label class="block sm:col-span-2">
            <span class="etiquette">Dépend de (équipement parent)</span>
            <select name="parent_device_id" class="champ">
                <option value="">Aucun</option>
                @foreach($devices as $other)
                    <option value="{{ $other->id }}" @selected((string) old('parent_device_id', $device->parent_device_id) === (string) $other->id)>{{ $other->name }} ({{ $other->host }})</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-graphite-500">Ex. la caméra dépend de l’antenne qui la relie au réseau : si l’antenne ne répond plus, la caméra passe en pause au lieu de déclencher une seconde alarme.</span>
            @error('parent_device_id')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        @unless($device->exists)
            <div class="rounded-lg bg-graphite-50 p-4 text-sm text-graphite-700 sm:col-span-2">
                <p><span class="font-semibold text-graphite-900">Capteurs créés automatiquement :</span> <span x-text="templates[type].base + (snmp && templates[type].snmp ? ', ' + templates[type].snmp : '')"></span>.</p>
                <label class="mt-3 flex items-start gap-2">
                    <input type="checkbox" name="snmp" value="1" x-model="snmp" class="mt-0.5 rounded border-graphite-300">
                    <span>Ajouter aussi les capteurs SNMP (<span x-text="templates[type].snmp"></span>). L’équipement doit avoir SNMP activé avec la communauté du groupe.</span>
                </label>
                <p class="mt-2 text-xs text-graphite-500">Vous pourrez ajouter d’autres capteurs ensuite (bureau à distance, base de données, site web…).</p>
            </div>
        @endunless

        <fieldset id="fiche" class="scroll-mt-24 border-t border-graphite-100 pt-4 sm:col-span-2">
            <legend class="pt-4 font-semibold text-graphite-900">Fiche de l’équipement</legend>
            <p class="mb-4 text-sm text-graphite-600">Facultatif. Les champs changent selon le type. Le modèle, le n° de série et l’emplacement sont lus automatiquement si l’équipement répond en SNMP.</p>
            @foreach(\App\Enums\MonitorDeviceType::cases() as $sheetType)
                <template x-if="type === @js($sheetType->value)">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach($sheetType->sheetFields() as $key => $field)
                            @php $value = old('details.'.$key, $device->details[$key] ?? null); $input = $field['input'] ?? 'text'; @endphp
                            <label class="block {{ $input === 'textarea' ? 'sm:col-span-2' : '' }}">
                                <span class="etiquette">{{ $field['label'] }}</span>
                                @if($input === 'select')
                                    <select name="details[{{ $key }}]" class="champ">
                                        <option value="">Non renseigné</option>
                                        @foreach($field['options'] as $option)
                                            <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif($input === 'textarea')
                                    <textarea name="details[{{ $key }}]" rows="3" maxlength="2000" class="champ" placeholder="Historique, particularités, accès…">{{ $value }}</textarea>
                                @else
                                    <input type="{{ $input }}" name="details[{{ $key }}]" value="{{ $value }}" maxlength="255" placeholder="{{ $field['placeholder'] ?? '' }}" class="champ">
                                @endif
                                @error('details.'.$key)<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                </template>
            @endforeach
        </fieldset>

        <details class="sm:col-span-2" @if($advanced) open @endif>
            <summary class="cursor-pointer font-medium text-nere-700">Réglages avancés (facultatif) : intervalle, alertes, SNMP</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @include('intranet.monitor.partials.settings-fields', ['model' => $device, 'inherited' => $inherited])
            </div>
        </details>

        <div class="flex flex-wrap gap-3 sm:col-span-2">
            <button class="bouton-primaire">{{ $device->exists ? 'Enregistrer' : 'Ajouter l’équipement' }}</button>
            <a href="{{ $device->exists ? route('intranet.monitor.devices.show', $device) : route('intranet.monitor.index') }}" class="bouton-secondaire">Annuler</a>
        </div>
    </form>

    @if($device->exists)
        <form method="POST" action="{{ route('intranet.monitor.devices.destroy', $device) }}" class="mt-6 max-w-4xl" onsubmit="return confirm('Retirer cet équipement et tout son historique de la supervision ?')">
            @csrf
            @method('DELETE')
            <button class="bouton-danger">Retirer l’équipement de la supervision</button>
            <span class="ml-2 text-sm text-graphite-600">Supprime aussi ses capteurs et son historique.</span>
        </form>
    @endif
</x-intranet-layout>
