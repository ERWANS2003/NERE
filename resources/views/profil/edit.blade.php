@php
    $breadcrumbs = [
        ['label' => 'Mon compte'],
    ];
@endphp

<x-intranet-layout title="Mon compte" :breadcrumbs="$breadcrumbs">

    <h1 class="text-3xl font-bold text-graphite-900">Mon compte</h1>

    <p class="mt-1 text-graphite-600">
        Mettez à jour vos coordonnées ou votre mot de passe.
    </p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @include('profil.partials.coordonnees')
        @include('profil.partials.mot-de-passe')
    </div>
</x-intranet-layout>
