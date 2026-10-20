@php($article = $article ?? null)

<form method="POST" action="{{ $action }}" class="space-y-5 rounded-xl border border-graphite-200 bg-white p-6">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="title" class="mb-1 block text-sm font-semibold text-graphite-800">Titre du guide <span class="text-red-700">*</span></label>
        <input id="title" name="title" required maxlength="180" value="{{ old('title', $article?->title) }}"
               class="champ" placeholder="Ex. Je n’arrive pas à me connecter">
        @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="summary" class="mb-1 block text-sm font-semibold text-graphite-800">Résumé affiché dans les résultats <span class="text-red-700">*</span></label>
        <textarea id="summary" name="summary" required maxlength="500" rows="2" class="champ"
                  placeholder="Décrivez en une ou deux phrases le problème et ce que le guide aide à résoudre.">{{ old('summary', $article?->summary) }}</textarea>
        @error('summary')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="category" class="mb-1 block text-sm font-semibold text-graphite-800">Catégorie</label>
            <select id="category" name="category" class="champ">
                <option value="">Général</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $article?->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="keywords" class="mb-1 block text-sm font-semibold text-graphite-800">Mots-clés (facultatif)</label>
            <input id="keywords" name="keywords" maxlength="255" value="{{ old('keywords', $article?->keywords) }}"
                   class="champ" placeholder="ex. accès, connexion, compte">
            <p class="mt-1 text-xs text-graphite-500">Séparez les mots-clés par des virgules.</p>
            @error('keywords')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="content" class="mb-1 block text-sm font-semibold text-graphite-800">Étapes de résolution <span class="text-red-700">*</span></label>
        <textarea id="content" name="content" required maxlength="20000" rows="10" class="champ"
                  placeholder="Écrivez une étape par paragraphe. Indiquez aussi quand arrêter et contacter le support.">{{ old('content', $article?->content) }}</textarea>
        <p class="mt-1 text-xs text-graphite-500">Le texte est affiché tel quel. N’incluez pas de mots de passe ni d’informations confidentielles.</p>
        @error('content')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <label class="flex items-start gap-3 rounded-lg border border-graphite-200 p-4">
        <input type="checkbox" name="is_published" value="1"
               @checked(old('is_published', $article?->is_published ?? true))
               class="mt-0.5 rounded border-graphite-300 text-nere-600 focus:ring-nere-500">
        <span>
            <span class="block text-sm font-semibold text-graphite-900">Publier ce guide pour les employés</span>
            <span class="mt-1 block text-xs text-graphite-600">Décochez pour enregistrer un brouillon visible uniquement par l’équipe IT.</span>
        </span>
    </label>

    <div class="flex flex-wrap justify-end gap-3 border-t border-graphite-100 pt-5">
        <a href="{{ $article ? route('intranet.knowledge-base.show', $article) : route('intranet.knowledge-base.index') }}"
           class="bouton-secondaire">Annuler</a>
        <button type="submit" class="bouton-primaire">{{ $article ? 'Enregistrer les modifications' : 'Ajouter le guide' }}</button>
    </div>
</form>
