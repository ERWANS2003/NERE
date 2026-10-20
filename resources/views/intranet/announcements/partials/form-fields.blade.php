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
