@php
    $breadcrumbs = [
        ['label' => 'Base de connaissances', 'url' => route('intranet.knowledge-base.index')],
        ['label' => $article->title, 'url' => route('intranet.knowledge-base.show', $article)],
        ['label' => 'Modifier'],
    ];
@endphp

<x-intranet-layout title="Modifier un guide" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-graphite-900">Modifier le guide</h1>
            <p class="mt-2 text-graphite-600">Mettez à jour les étapes ou le statut de publication de « {{ $article->title }} ».</p>
        </div>
        @include('intranet.knowledge-base._form', [
            'action' => route('intranet.knowledge-base.update', $article),
            'method' => 'PUT',
            'article' => $article,
        ])
    </div>
</x-intranet-layout>
