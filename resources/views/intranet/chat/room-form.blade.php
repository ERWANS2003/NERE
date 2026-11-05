<x-intranet-layout title="{{ $room->exists ? 'Gérer le salon privé' : 'Nouveau salon privé' }}">
    <div class="mx-auto max-w-2xl">
        <a href="{{ $room->exists ? route('intranet.chat.show', $room) : route('intranet.chat.index') }}" class="text-sm font-medium text-graphite-600 hover:text-nere-700">&larr; Retour au chat</a>
        <h1 class="mb-6 mt-2 text-3xl font-bold text-graphite-900">{{ $room->exists ? 'Gérer le salon privé' : 'Nouveau salon privé' }}</h1>

        <form method="POST" action="{{ $room->exists ? route('intranet.chat.rooms.update', $room) : route('intranet.chat.rooms.store') }}" class="space-y-6 rounded-xl border border-graphite-200 bg-white p-6"
              x-data="{ search: '' }">
            @csrf
            @if($room->exists) @method('PUT') @endif
            <div>
                <x-etiquette for="name">Nom du salon</x-etiquette>
                <input id="name" name="name" required maxlength="100" value="{{ old('name', $room->name) }}" class="champ">
                <x-erreur :messages="$errors->get('name')" />
            </div>
            <fieldset>
                <legend class="mb-2 block text-sm font-medium text-graphite-800">Membres</legend>
                <input type="search" x-model="search" placeholder="Rechercher une personne" class="champ mb-2" aria-label="Rechercher une personne">
                <div class="max-h-80 divide-y divide-graphite-100 overflow-y-auto rounded-md border border-graphite-200">
                    @php($selected = collect(old('members', $memberIds))->map(fn ($id) => (int) $id))
                    @foreach($users as $user)
                        <label class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-graphite-50"
                               x-show="@js(mb_strtolower($user->name.' '.$user->poste)).includes(search.toLowerCase())">
                            <input type="checkbox" name="members[]" value="{{ $user->id }}" @checked($selected->contains($user->id)) class="rounded border-graphite-300">
                            <span class="font-medium text-graphite-900">{{ $user->name }}</span>
                            @if($user->poste)<span class="truncate text-graphite-500">{{ $user->poste }}</span>@endif
                        </label>
                    @endforeach
                </div>
                <x-erreur :messages="$errors->get('members')" />
            </fieldset>
            <div class="flex justify-end gap-3 border-t pt-4">
                <a href="{{ route('intranet.chat.index') }}" class="rounded-md border border-graphite-300 px-4 py-2 text-sm">Annuler</a>
                <button class="bouton-primaire">Enregistrer</button>
            </div>
        </form>

        @if($room->exists)
            <form method="POST" action="{{ route('intranet.chat.rooms.destroy', $room) }}" class="mt-4 text-right" onsubmit="return confirm('Supprimer ce salon et tous ses messages ?')">
                @csrf
                @method('DELETE')
                <button class="text-sm font-medium text-red-700 hover:underline">Supprimer le salon</button>
            </form>
        @endif
    </div>
</x-intranet-layout>
