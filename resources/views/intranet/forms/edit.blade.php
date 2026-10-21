@php($breadcrumbs = [
    ['label' => 'Formulaires', 'url' => route('intranet.forms.index')],
    ['label' => $form->name],
])

<x-intranet-layout :title="'Modifier '.$form->name" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Modifier le formulaire" :description="$form->department->name" />
    @include('intranet.forms._editor', ['form' => $form])
</x-intranet-layout>
