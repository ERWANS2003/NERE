@php
    $breadcrumbs = [
        ['label' => 'Base de connaissances', 'url' => route('intranet.knowledge-base.index')],
        ['label' => $article->title],
    ];
@endphp

<x-intranet-layout :title="$article->title" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto max-w-3xl">
        <article class="overflow-hidden rounded-xl border border-graphite-200 bg-white shadow-sm">
            <header class="border-b border-graphite-100 p-6 sm:p-8">
                @if($article->category && isset($categories[$article->category]))
                    <span class="inline-flex rounded-full bg-nere-50 px-3 py-1 text-xs font-semibold text-nere-700">
                        {{ $categories[$article->category] }}
                    </span>
                @endif
                @unless($article->is_published)
                    <span class="ml-2 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">Brouillon visible par l’équipe IT</span>
                @endunless
                <h1 class="mt-4 text-2xl font-bold text-graphite-900 sm:text-3xl">{{ $article->title }}</h1>
                <p class="mt-3 text-base leading-relaxed text-graphite-600">{{ $article->summary }}</p>
                <p class="mt-4 text-xs text-graphite-500">
                    Guide proposé par {{ $article->author?->name ?? 'le support informatique' }}
                    · Mis à jour le {{ $article->updated_at?->format('d/m/Y') }}
                </p>
            </header>

            <div class="p-6 sm:p-8">
                <h2 class="mb-4 text-lg font-semibold text-graphite-900">Étapes à essayer</h2>
                <div class="whitespace-pre-line text-sm leading-7 text-graphite-800">{{ $article->content }}</div>
            </div>

            @if($article->is_published)
                <section class="border-t border-graphite-100 bg-graphite-50 p-6 sm:p-8" aria-labelledby="article-feedback">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="article-feedback" class="font-semibold text-graphite-900">Ce guide vous a-t-il aidé ?</h2>
                            <p class="mt-1 text-sm text-graphite-600">
                                {{ $article->helpful_count }} oui · {{ $article->not_helpful_count }} pas encore
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('intranet.knowledge-base.feedback', $article) }}">
                                @csrf
                                <input type="hidden" name="helpful" value="1">
                                <button type="submit" @disabled($userVote === true)
                                        class="bouton-secondaire disabled:cursor-default disabled:bg-green-50 disabled:text-green-800">
                                    Oui, merci
                                </button>
                            </form>
                            <form method="POST" action="{{ route('intranet.knowledge-base.feedback', $article) }}">
                                @csrf
                                <input type="hidden" name="helpful" value="0">
                                <button type="submit" @disabled($userVote === false)
                                        class="bouton-secondaire disabled:cursor-default disabled:bg-amber-50 disabled:text-amber-900">
                                    Pas encore
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            @endif
        </article>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('intranet.knowledge-base.index') }}" class="bouton-secondaire">← Tous les guides</a>
            @if($article->category)
                <a href="{{ route('intranet.tickets.create', ['category' => $article->category]) }}" class="bouton-primaire">
                    Le problème persiste ? Créer un ticket
                </a>
            @else
                <a href="{{ route('intranet.tickets.create') }}" class="bouton-primaire">
                    Le problème persiste ? Créer un ticket
                </a>
            @endif
            @if($isManager)
                <a href="{{ route('intranet.knowledge-base.edit', $article) }}" class="bouton-secondaire">Modifier le guide</a>
                <form method="POST" action="{{ route('intranet.knowledge-base.destroy', $article) }}"
                      onsubmit="return confirm('Supprimer définitivement ce guide ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Supprimer
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-intranet-layout>
