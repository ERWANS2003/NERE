@php
    $breadcrumbs = [
        ['label' => 'Annonces', 'url' => route('intranet.announcements.index')],
        ['label' => 'Nouvelle annonce'],
    ];
    $peutPublierPourTous = auth()->user()->is_super_admin;
@endphp

<x-intranet-layout title="Nouvelle annonce" :breadcrumbs="$breadcrumbs">

    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-graphite-900 mb-6">Nouvelle annonce</h1>

        <form method="POST" action="{{ route('intranet.announcements.store') }}" class="bg-white rounded-xl border border-graphite-200 p-8 space-y-6">
            @csrf

            @include('intranet.announcements.partials.form-fields')

            <div class="flex items-center gap-3 pt-2">
                <x-bouton-primaire>Publier</x-bouton-primaire>
                <a href="{{ route('intranet.announcements.index') }}" class="bouton-secondaire">Annuler</a>
            </div>
        </form>
    </div>

</x-intranet-layout>
