@php
    $breadcrumbs = [
        ['label' => 'Base de connaissances', 'url' => route('intranet.knowledge-base.index')],
        ['label' => 'Ajouter un guide'],
    ];
@endphp

<x-intranet-layout title="Ajouter un guide" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-graphite-900">Ajouter un guide de dépannage</h1>
            <p class="mt-2 text-graphite-600">Rédigez des étapes simples et sûres, puis publiez-les pour aider les collègues à résoudre les problèmes courants.</p>
        </div>
        @include('intranet.knowledge-base._form', [
            'action' => route('intranet.knowledge-base.store'),
            'method' => 'POST',
            'article' => null,
        ])
    </div>
</x-intranet-layout>
