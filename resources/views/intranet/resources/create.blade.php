@php
    $breadcrumbs = [
        ['label' => 'Ressources', 'url' => route('intranet.resources')],
        ['label' => 'Ajouter une ressource'],
    ];
@endphp

<x-intranet-layout title="Ajouter une ressource" :breadcrumbs="$breadcrumbs">

    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-graphite-900 mb-6">Ajouter une ressource</h1>

        <form
            method="POST"
            action="{{ route('intranet.resources.store') }}"
            enctype="multipart/form-data"
            class="bg-white rounded-xl border border-graphite-200 p-8 space-y-6"
        >
            @csrf

            <div>
                <x-etiquette for="title">Titre</x-etiquette>
                <x-champ id="title" name="title" :value="old('title')" required maxlength="255" autofocus />
                <x-erreur :messages="$errors->get('title')" />
            </div>

            <div>
                <x-etiquette for="description">Description</x-etiquette>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="2000"
                    class="champ"
                >{{ old('description') }}</textarea>
                <x-erreur :messages="$errors->get('description')" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <x-etiquette for="category_id">Catégorie</x-etiquette>
                    @php $categorieChoisie = old('category_id'); @endphp
                    <select id="category_id" name="category_id" required class="champ">
                        <option value="" disabled @selected(!$categorieChoisie)>— Choisir —</option>
                        @foreach($categories as $categorie)
                            <option value="{{ $categorie->id }}" @selected((string) $categorieChoisie === (string) $categorie->id)>
                                {{ $categorie->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-erreur :messages="$errors->get('category_id')" />
                </div>

                <div>
                    <x-etiquette for="department_id">Département</x-etiquette>
                    @php $deptChoisi = old('department_id', $departments->count() === 1 ? $departments->first()->id : null); @endphp
                    <select id="department_id" name="department_id" class="champ" @unless($canPublishForAll) required @endunless>
                        @if($canPublishForAll)
                            <option value="" @selected(!$deptChoisi)>Toute l'entreprise</option>
                        @endif
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected((string) $deptChoisi === (string) $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @unless($canPublishForAll)
                        <p class="mt-1.5 text-xs text-graphite-500">Vous ne pouvez publier que pour un département que vous dirigez.</p>
                    @endunless
                    <x-erreur :messages="$errors->get('department_id')" />
                </div>
            </div>

            <div>
                <x-etiquette for="tags">Mots-clés</x-etiquette>
                <x-champ id="tags" name="tags" :value="old('tags')" maxlength="255" placeholder="hse, sécurité, politique" />
                <p class="mt-1.5 text-xs text-graphite-500">Séparés par des virgules (10 maximum).</p>
                <x-erreur :messages="$errors->get('tags')" />
            </div>

            <div>
                <x-etiquette for="file">Fichier</x-etiquette>
                <input
                    type="file"
                    id="file"
                    name="file"
                    required
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.mp4,.mov,.avi,.webm"
                    class="block w-full text-sm text-graphite-700 file:mr-4 file:rounded-lg file:border-0 file:bg-nere-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-nere-700 hover:file:bg-nere-100"
                >
                <p class="mt-1.5 text-xs text-graphite-500">PDF, Word, Excel, PowerPoint ou vidéo. Taille maximale : 50 Mo.</p>
                <x-erreur :messages="$errors->get('file')" />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-bouton-primaire>Ajouter</x-bouton-primaire>
                <a href="{{ route('intranet.resources') }}" class="bouton-secondaire">Annuler</a>
            </div>
        </form>
    </div>

</x-intranet-layout>
