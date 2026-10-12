@php
    $breadcrumbs = [
        ['label' => 'Mes demandes', 'url' => route('intranet.submissions.index')],
        ['label' => '#' . str_pad($submission->id, 5, '0', STR_PAD_LEFT)],
    ];
    $statutMap = [
        'pending' => ['label' => 'En attente', 'type' => 'warning'],
        'approved' => ['label' => 'Approuvée', 'type' => 'success'],
        'rejected' => ['label' => 'Rejetée', 'type' => 'danger'],
    ];
    $statut = $statutMap[$submission->status] ?? ['label' => ucfirst((string) $submission->status), 'type' => 'info'];
@endphp

<x-intranet-layout title="Demande #{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}" :breadcrumbs="$breadcrumbs">

    <div class="max-w-3xl mx-auto">

        @if(session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-graphite-200 overflow-hidden">

            {{-- En-tête --}}
            <div class="p-6 border-b border-graphite-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-sm text-graphite-500">#{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}</span>
                            <x-badge :type="$statut['type']">{{ $statut['label'] }}</x-badge>
                        </div>
                        <h1 class="text-2xl font-bold text-graphite-900">
                            {{ $submission->form?->name ?? 'Demande' }}
                        </h1>
                        @if($submission->form?->department)
                            <p class="text-sm text-graphite-600 mt-1">
                                {{ $submission->form->department->name }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Détails --}}
            <div class="p-6 space-y-4">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-graphite-500">Demandeur</dt>
                        <dd class="text-graphite-900 font-medium">{{ $submission->user?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-graphite-500">Date de soumission</dt>
                        <dd class="text-graphite-900 font-medium">{{ $submission->created_at?->format('d/m/Y à H:i') }}</dd>
                    </div>
                    @if($submission->reviewed_at)
                        <div>
                            <dt class="text-graphite-500">Traitée par</dt>
                            <dd class="text-graphite-900 font-medium">{{ $submission->reviewer?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-graphite-500">Date de traitement</dt>
                            <dd class="text-graphite-900 font-medium">{{ $submission->reviewed_at?->format('d/m/Y à H:i') }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Contenu du formulaire --}}
                @if(is_array($submission->data) && count($submission->data) > 0)
                    <div class="pt-4 border-t border-graphite-100">
                        <h2 class="font-semibold text-graphite-900 mb-3">Détails de la demande</h2>
                        <dl class="space-y-2">
                            @foreach($submission->data as $champ => $valeur)
                                <div class="flex flex-col sm:flex-row sm:gap-4">
                                    <dt class="text-graphite-500 sm:w-1/3">{{ ucfirst(str_replace('_', ' ', $champ)) }}</dt>
                                    <dd class="text-graphite-900 sm:w-2/3">{{ is_array($valeur) ? implode(', ', $valeur) : $valeur }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                {{-- Commentaire de revue --}}
                @if($submission->review_comment)
                    <div class="pt-4 border-t border-graphite-100">
                        <h2 class="font-semibold text-graphite-900 mb-2">Commentaire</h2>
                        <p class="text-graphite-700 text-sm bg-graphite-50 rounded-lg p-3">{{ $submission->review_comment }}</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- Retour --}}
        <div class="mt-6">
            <a href="{{ route('intranet.submissions.index') }}"
               class="inline-flex items-center gap-2 text-graphite-600 hover:text-graphite-900 font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour à mes demandes
            </a>
        </div>

    </div>

</x-intranet-layout>
