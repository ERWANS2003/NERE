@php
    $formFieldsByKey = collect($submission->form?->form_schema['fields'] ?? [])->keyBy('key');
    $breadcrumbs = [
        ['label' => auth()->user()->canHandleIn($submission->form?->department_id ?? 0) ? 'Demandes à traiter' : 'Mes demandes',
            'url' => auth()->user()->canHandleIn($submission->form?->department_id ?? 0)
                ? route('intranet.submissions.queue')
                : route('intranet.submissions.index')],
        ['label' => '#' . str_pad($submission->id, 5, '0', STR_PAD_LEFT)],
    ];
    $canHandle = $submission->form?->department_id !== null
        && auth()->user()->canHandleIn($submission->form->department_id);
    $canReview = $submission->form?->department_id !== null
        && auth()->user()->canValidateIn($submission->form->department_id);
@endphp

<x-intranet-layout title="Demande #{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}" :breadcrumbs="$breadcrumbs">

    <div class="max-w-3xl mx-auto">

        @if(session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-graphite-200 overflow-hidden">

            {{-- En-tête --}}
            <div class="p-6 border-b border-graphite-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-sm text-graphite-500">#{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}</span>
                            <x-intranet.status-badge :status="$submission->status" />
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
                    @if($submission->assignee)
                        <div>
                            <dt class="text-graphite-500">Pris en charge par</dt>
                            <dd class="text-graphite-900 font-medium">{{ $submission->assignee->name }}</dd>
                        </div>
                    @endif
                    @if($submission->assigned_at)
                        <div>
                            <dt class="text-graphite-500">Date de prise en charge</dt>
                            <dd class="text-graphite-900 font-medium">{{ $submission->assigned_at->format('d/m/Y à H:i') }}</dd>
                        </div>
                    @endif
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
                                    <dt class="text-graphite-500 sm:w-1/3">{{ $formFieldsByKey->get($champ)['label'] ?? ucfirst(str_replace('_', ' ', $champ)) }}</dt>
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

        @if($submission->status === 'pending' && $canHandle)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
                <h2 class="font-semibold text-blue-950">Prise en charge</h2>
                @if($submission->assigned_to === null || $submission->assigned_to === auth()->id())
                    <p class="mt-1 text-sm text-blue-900">Attribuez cette demande à votre compte pour indiquer à l’équipe que vous la traitez.</p>
                    <form method="POST" action="{{ route('intranet.submissions.take', $submission) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            <x-icone nom="hand" />
                            {{ $submission->assigned_to === auth()->id() ? 'Confirmer ma prise en charge' : 'Prendre en charge' }}
                        </button>
                    </form>
                @else
                    <p class="mt-1 text-sm text-blue-900">
                        Cette demande est déjà suivie par <strong>{{ $submission->assignee?->name ?? 'un membre de l’équipe' }}</strong>.
                    </p>
                @endif
            </div>
        @endif

        @if($submission->status === 'pending' && $canReview)
            <div class="mt-6 rounded-xl border border-graphite-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-graphite-900">Décision du chef de département</h2>
                <p class="mt-1 text-sm text-graphite-600">Après examen, approuvez ou rejetez la demande. Votre commentaire sera transmis au demandeur.</p>
                <form method="POST" action="{{ route('intranet.submissions.update', $submission) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="review_comment" class="mb-1 block text-sm font-medium text-graphite-700">Commentaire <span class="font-normal text-graphite-500">(facultatif)</span></label>
                        <textarea id="review_comment" name="review_comment" rows="3" maxlength="2000"
                                  class="w-full rounded-lg border-graphite-300 text-sm focus:border-nere-500 focus:ring-nere-500"
                                  placeholder="Ajoutez une précision utile au demandeur…">{{ old('review_comment') }}</textarea>
                        @error('review_comment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @error('status')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" name="status" value="approved" class="inline-flex items-center gap-2 rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
                            <x-icone nom="check" />Approuver la demande
                        </button>
                        <button type="submit" name="status" value="rejected" class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                            <x-icone nom="x" />Rejeter la demande
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Retour --}}
        <div class="mt-6">
            <a href="{{ $canHandle ? route('intranet.submissions.queue') : route('intranet.submissions.index') }}"
               class="inline-flex items-center gap-2 text-graphite-600 hover:text-graphite-900 font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour à mes demandes
            </a>
        </div>

    </div>

</x-intranet-layout>
