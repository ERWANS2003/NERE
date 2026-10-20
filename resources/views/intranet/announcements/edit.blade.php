@php
    $breadcrumbs = [
        ['label' => 'Annonces', 'url' => route('intranet.announcements.index')],
        ['label' => $announcement->title, 'url' => route('intranet.announcements.show', $announcement)],
        ['label' => 'Modifier'],
    ];
@endphp

<x-intranet-layout title="Modifier l'annonce" :breadcrumbs="$breadcrumbs">

    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-graphite-900 mb-6">Modifier l'annonce</h1>

        <form method="POST" action="{{ route('intranet.announcements.update', $announcement) }}" class="bg-white rounded-xl border border-graphite-200 p-8 space-y-6">
            @csrf
            @method('PUT')

            @include('intranet.announcements.partials.form-fields')

            <div class="flex items-center gap-3 pt-2">
                <x-bouton-primaire>Enregistrer</x-bouton-primaire>
                <a href="{{ route('intranet.announcements.show', $announcement) }}" class="bouton-secondaire">Annuler</a>
            </div>
        </form>
    </div>

</x-intranet-layout>
