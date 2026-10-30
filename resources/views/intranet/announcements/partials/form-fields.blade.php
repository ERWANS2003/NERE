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

<div>
    <x-etiquette for="department_id">Public</x-etiquette>
    @php
        // Une nouvelle annonce de chef vise son département : toute l'entreprise est un choix explicite.
        $public = old('department_id', isset($announcement)
            ? $announcement->department_id
            : (auth()->user()->is_super_admin ? null : $audienceDepartments->first()?->id));
    @endphp
    <select id="department_id" name="department_id" class="champ">
        <option value="">Toute l'entreprise</option>
        @foreach($audienceDepartments as $audienceDepartment)
            <option value="{{ $audienceDepartment->id }}" @selected((string) $public === (string) $audienceDepartment->id)>{{ $audienceDepartment->name }}</option>
        @endforeach
    </select>
    <p class="mt-1.5 text-xs text-graphite-500">Seul ce public voit l'annonce et reçoit la notification.</p>
    <x-erreur :messages="$errors->get('department_id')" />
</div>

<div>
    <x-etiquette for="attachment">Pièce jointe (facultatif)</x-etiquette>
    @if(! empty($announcement?->attachment_path))
        <div class="mb-2 flex items-center gap-4 text-sm">
            <a href="{{ route('intranet.announcements.attachment', $announcement) }}" class="font-medium text-nere-700 hover:underline">{{ $announcement->attachment_name }}</a>
            <label class="flex items-center gap-2 text-graphite-700">
                <input type="checkbox" name="remove_attachment" value="1">
                Retirer
            </label>
        </div>
    @endif
    <input id="attachment" name="attachment" type="file" accept=".pdf,.png,.jpg,.jpeg,.txt,.doc,.docx,.xls,.xlsx,.ppt,.pptx" class="block w-full text-sm text-graphite-700">
    <p class="mt-1.5 text-xs text-graphite-500">PDF, image, Word, Excel, PowerPoint ou texte, 10 Mo maximum.</p>
    <x-erreur :messages="$errors->get('attachment')" />
</div>
