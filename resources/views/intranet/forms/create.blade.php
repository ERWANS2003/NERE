@php($breadcrumbs = [['label' => 'Formulaires', 'url' => route('intranet.forms.index')], ['label' => 'Créer']])

<x-intranet-layout title="Créer un formulaire" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Créer un formulaire" description="Définissez les informations nécessaires à une demande." />
    @include('intranet.forms._editor', ['form' => null])
</x-intranet-layout>
