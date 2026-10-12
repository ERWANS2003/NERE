{{-- Champs communs à create.blade.php et edit.blade.php. --}}

<div>
    <x-etiquette for="title">Titre</x-etiquette>
    <x-champ id="title" name="title" :value="old('title', $announcement->title ?? '')" required maxlength="255" autofocus />
    <x-erreur :messages="$errors->get('title')" />
</div>

<div>
    <x-etiquette for="body">Contenu</x-etiquette>
    <textarea
        id="body"
        name="body"
        rows="8"
        required
        maxlength="10000"
        class="champ"
    >{{ old('body', $announcement->body ?? '') }}</textarea>
    <p class="mt-1.5 text-xs text-graphite-500">Texte brut. Les retours à la ligne sont conservés à l'affichage.</p>
    <x-erreur :messages="$errors->get('body')" />
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-etiquette for="priority">Priorité</x-etiquette>
        @php $priorite = old('priority', $announcement->priority ?? 'info'); @endphp
        <select id="priority" name="priority" required class="champ">
            <option value="info" @selected($priorite === 'info')>Info</option>
            <option value="important" @selected($priorite === 'important')>Important</option>
            <option value="urgent" @selected($priorite === 'urgent')>Urgent (envoie aussi un e-mail)</option>
        </select>
        <x-erreur :messages="$errors->get('priority')" />
    </div>

    <div>
        <x-etiquette for="department_id">Public</x-etiquette>
        @php $deptSelectionne = old('department_id', $announcement->department_id ?? ($departments->count() === 1 ? $departments->first()->id : null)); @endphp
        <select id="department_id" name="department_id" class="champ" @if(!$peutPublierPourTous) required @endif>
            @if($peutPublierPourTous)
                <option value="" @selected(!$deptSelectionne)>Toute l'entreprise</option>
            @endif
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" @selected((string) $deptSelectionne === (string) $dept->id)>{{ $dept->name }}</option>
            @endforeach
        </select>
        @if(!$peutPublierPourTous)
            <p class="mt-1.5 text-xs text-graphite-500">Vous ne pouvez publier que pour un département que vous dirigez.</p>
        @endif
        <x-erreur :messages="$errors->get('department_id')" />
    </div>
</div>
