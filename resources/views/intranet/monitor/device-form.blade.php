@php
    $title = $device->exists ? 'Modifier '.$device->name : 'Ajouter un équipement';
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => $title]];
    $group = \App\Models\MonitorGroup::find(old('group_id', $device->group_id)) ?? \App\Models\MonitorGroup::query()->first();
    $inherited = fn (string $field) => $field === 'snmp_community' ? '' : $group?->setting($field);
@endphp

<x-intranet-layout :title="$title" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header :title="$title" />

    <form method="POST" action="{{ $device->exists ? route('intranet.monitor.devices.update', $device) : route('intranet.monitor.devices.store') }}" class="carte grid max-w-4xl gap-4 p-6 sm:grid-cols-2">
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
            <select name="type" required class="champ">
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
            <label class="flex items-start gap-2 text-sm text-graphite-700 sm:col-span-2">
                <input type="checkbox" name="snmp" value="1" @checked(old('snmp')) class="mt-0.5 rounded border-graphite-300">
                <span>Ajouter aussi les capteurs SNMP du modèle (durée de fonctionnement, processeur, mémoire, disque, consommables ou trafic selon le type). L’équipement doit avoir SNMP activé avec la communauté du groupe.</span>
            </label>
        @endunless

        @include('intranet.monitor.partials.settings-fields', ['model' => $device, 'inherited' => $inherited])

        <div class="flex flex-wrap gap-3 sm:col-span-2">
            <button class="bouton-primaire">{{ $device->exists ? 'Enregistrer' : 'Ajouter l’équipement' }}</button>
            <a href="{{ $device->exists ? route('intranet.monitor.devices.show', $device) : route('intranet.monitor.index') }}" class="bouton-secondaire">Annuler</a>
        </div>
    </form>
</x-intranet-layout>
