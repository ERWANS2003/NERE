@extends('admin.layout')

@section('title', $announcement->exists ? 'Modifier une annonce globale' : 'Nouvelle annonce globale')

@section('content')
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-2 text-2xl font-bold text-gray-900">{{ $announcement->exists ? 'Modifier une annonce globale' : 'Nouvelle annonce globale' }}</h1>
        <p class="mb-6 text-sm text-gray-600">Cette annonce sera visible par tous les utilisateurs actifs.</p>
        <form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            @csrf
            @if($announcement->exists) @method('PUT') @endif
            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-gray-700">Titre</label>
                <input id="title" name="title" required maxlength="255" value="{{ old('title', $announcement->title) }}" class="w-full rounded border-gray-300">
                @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="mb-1 block text-sm font-medium text-gray-700">Contenu</label>
                <textarea id="body" name="body" rows="8" required maxlength="10000" class="w-full rounded border-gray-300">{{ old('body', $announcement->body) }}</textarea>
                @error('body')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="priority" class="mb-1 block text-sm font-medium text-gray-700">Priorité</label>
                <select id="priority" name="priority" required class="w-full rounded border-gray-300">
                    @foreach(['info' => 'Info', 'important' => 'Important', 'urgent' => 'Urgent (e-mail aux destinataires)'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $announcement->priority) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $announcement->is_active)) class="rounded border-gray-300">
                Publier maintenant (les destinataires seront notifiés)
            </label>
            <div class="flex justify-end gap-3 border-t pt-4">
                <a href="{{ route('admin.announcements.index') }}" class="rounded border px-4 py-2 text-sm">Annuler</a>
                <button class="rounded bg-nere-700 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            </div>
        </form>
    </div>
@endsection
