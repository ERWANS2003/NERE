@php
    $breadcrumbs = [
        ['label' => 'Support IT', 'url' => route('intranet.tickets.index')],
        ['label' => 'Nouveau ticket'],
    ];
@endphp

<x-intranet-layout title="Nouveau ticket" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-graphite-900">Créer un ticket IT</h1>
            <p class="mt-1 text-graphite-600">Décrivez le problème ou le besoin ; le support informatique vous répondra dans ce ticket.</p>
        </div>

        @if($suggestedArticles->isNotEmpty())
            <section class="mb-6 rounded-xl border border-or-200 bg-or-50 p-5" aria-labelledby="suggested-guides-title">
                <div class="mb-4">
                    <h2 id="suggested-guides-title" class="text-lg font-semibold text-graphite-900">Avant d’envoyer votre demande</h2>
                    <p class="mt-1 text-sm text-graphite-700">Ces guides peuvent vous aider à résoudre le problème plus rapidement.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($suggestedArticles as $article)
                        <a href="{{ route('intranet.knowledge-base.show', $article) }}"
                           class="rounded-lg border border-graphite-200 bg-white p-4 transition hover:border-nere-400 hover:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nere-600">
                            <span class="block font-semibold text-nere-800">{{ $article->title }}</span>
                            <span class="mt-1 block text-sm leading-relaxed text-graphite-600">{{ $article->summary }}</span>
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('intranet.knowledge-base.index') }}" class="mt-4 inline-flex text-sm font-semibold text-nere-700 hover:underline">
                    Parcourir toute la base de connaissances <span class="ml-1" aria-hidden="true">→</span>
                </a>
            </section>
        @endif

        <form method="POST" action="{{ route('intranet.tickets.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-graphite-200 bg-white p-6">
            @csrf
            <div>
                <label for="subject" class="mb-1 block text-sm font-medium text-graphite-800">Sujet <span class="text-red-600">*</span></label>
                <input id="subject" name="subject" value="{{ old('subject') }}" maxlength="180" required class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                @error('subject')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-graphite-800">Catégorie <span class="text-red-600">*</span></label>
                    <select id="category" name="category" required class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                        <option value="" disabled @selected(old('category', $selectedCategory) === null)>Choisir une catégorie</option>
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $selectedCategory) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="priority" class="mb-1 block text-sm font-medium text-graphite-800">Priorité <span class="text-red-600">*</span></label>
                    <select id="priority" name="priority" required class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                        @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-graphite-800">Description détaillée <span class="text-red-600">*</span></label>
                <textarea id="description" name="description" rows="7" maxlength="10000" required class="w-full rounded-lg border border-graphite-300 px-3 py-2">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="attachments" class="mb-1 block text-sm font-medium text-graphite-800">Pièces jointes (facultatif)</label>
                <input id="attachments" type="file" name="attachments[]" multiple accept=".pdf,.png,.jpg,.jpeg,.txt,.doc,.docx,.xls,.xlsx" class="block w-full text-sm text-graphite-700">
                <p class="mt-1 text-xs text-graphite-500">5 fichiers maximum, 10 Mo par fichier. Formats : PDF, image, texte, Word ou Excel.</p>
                @error('attachments')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-3 border-t border-graphite-100 pt-5">
                <a href="{{ route('intranet.tickets.index') }}" class="rounded-lg border border-graphite-300 px-4 py-2 text-sm text-graphite-700 hover:bg-graphite-50">Annuler</a>
                <button type="submit" class="rounded-lg bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Envoyer au support</button>
            </div>
        </form>
    </div>
</x-intranet-layout>
