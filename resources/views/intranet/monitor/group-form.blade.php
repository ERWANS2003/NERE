@php
    $title = $group->exists ? 'Groupe '.$group->name : 'Nouveau groupe';
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => $title]];
    $parent = $group->parent_id ? \App\Models\MonitorGroup::find($group->parent_id) : null;
    $inherited = fn (string $field) => $parent ? ($field === 'snmp_community' ? '' : $parent->setting($field)) : config('monitor.'.$field);
@endphp

<x-intranet-layout :title="$title" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header :title="$title" description="Un groupe rassemble des équipements d'un même site ou d'une même zone. Ses réglages s'appliquent à tout ce qu'il contient, sauf réglage plus précis." />

    <form method="POST" action="{{ $group->exists ? route('intranet.monitor.groups.update', $group) : route('intranet.monitor.groups.store') }}" class="carte grid max-w-4xl gap-4 p-6 sm:grid-cols-2">
        @csrf
        @if($group->exists) @method('PUT') @endif
        <label class="block">
            <span class="etiquette">Nom</span>
            <input name="name" value="{{ old('name', $group->name) }}" required maxlength="120" placeholder="Siège Ouagadougou" class="champ">
            @error('name')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        <label class="block">
            <span class="etiquette">Dans le groupe</span>
            <select name="parent_id" class="champ">
                <option value="">(premier niveau)</option>
                @foreach($parents as $id => $path)
                    <option value="{{ $id }}" @selected((string) old('parent_id', $group->parent_id) === (string) $id)>{{ $path }}</option>
                @endforeach
            </select>
            @error('parent_id')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>

        @include('intranet.monitor.partials.settings-fields', ['model' => $group, 'inherited' => $inherited])

        <div class="flex flex-wrap gap-3 sm:col-span-2">
            <button class="bouton-primaire">Enregistrer</button>
            <a href="{{ route('intranet.monitor.index') }}" class="bouton-secondaire">Annuler</a>
        </div>
    </form>

    @if($group->exists)
        <form method="POST" action="{{ route('intranet.monitor.groups.destroy', $group) }}" class="mt-6" onsubmit="return confirm('Supprimer ce groupe ?')">
            @csrf
            @method('DELETE')
            <button class="bouton-danger">Supprimer le groupe</button>
            <span class="ml-2 text-sm text-graphite-600">Possible seulement s’il est vide.</span>
        </form>
    @endif
</x-intranet-layout>
