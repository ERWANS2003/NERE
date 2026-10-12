@php
    $breadcrumbs = [
        ['label' => 'Mes congés', 'url' => route('intranet.time-off.index')],
        ['label' => 'Approbations'],
    ];
@endphp

<x-intranet-layout title="Approbations de congés" :breadcrumbs="$breadcrumbs">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-graphite-900 mb-2">Demandes à approuver</h1>
        <p class="text-graphite-600">Validez ou refusez les demandes de congé de votre équipe</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($requests->count() > 0)
        <div class="space-y-4">
            @foreach($requests as $req)
                <div class="bg-white rounded-lg border border-graphite-200 p-5"
                     x-data="{ ouvert: false, decision: null }">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-4 min-w-0">
                            <x-avatar :user="$req->user" size="lg" />
                            <div class="min-w-0">
                                <div class="font-semibold text-graphite-900">{{ $req->user?->name }}</div>
                                <div class="text-sm text-graphite-500">{{ $req->user?->job_title ?? '—' }}</div>
                                <div class="mt-2 text-sm text-graphite-700">
                                    <span class="font-medium">{{ $req->policy?->name }}</span>
                                    · {{ $req->start_date->format('d/m/Y') }} au {{ $req->end_date->format('d/m/Y') }}
                                    · <strong>{{ rtrim(rtrim(number_format($req->total_days, 1), '0'), '.') }}</strong> jour(s){{ $req->is_half_day ? ' (½)' : '' }}
                                </div>
                                @if($req->reason)
                                    <div class="mt-1 text-sm text-graphite-500">Motif : {{ $req->reason }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <button type="button" @click="ouvert = true; decision = 'approve'"
                                    class="bouton-primaire">Approuver</button>
                            <button type="button" @click="ouvert = true; decision = 'reject'"
                                    class="bouton-secondaire">Refuser</button>
                        </div>
                    </div>

                    {{-- Confirmation avec commentaire --}}
                    <form method="POST" action="{{ route('intranet.time-off.approvals.update', $req->id) }}"
                          x-show="ouvert" x-cloak class="mt-4 border-t border-graphite-100 pt-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="decision" :value="decision">
                        <label class="block text-sm font-medium text-graphite-700 mb-1.5">
                            Commentaire <span class="text-graphite-400">(facultatif)</span>
                        </label>
                        <textarea name="approval_comment" rows="2" maxlength="1000" class="champ"
                                  :placeholder="decision === 'reject' ? 'Motif du refus...' : 'Note éventuelle...'"></textarea>
                        <div class="mt-3 flex items-center gap-2">
                            <button type="submit" class="bouton-primaire"
                                    x-text="decision === 'reject' ? 'Confirmer le refus' : 'Confirmer l\'approbation'"></button>
                            <button type="button" @click="ouvert = false" class="bouton-secondaire">Annuler</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

        @if($requests->hasPages())
            <div class="mt-6">{{ $requests->links() }}</div>
        @endif
    @else
        <div class="bg-white rounded-lg border border-graphite-200 p-12 text-center">
            <svg class="w-14 h-14 text-graphite-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-graphite-600">Aucune demande en attente d'approbation.</p>
        </div>
    @endif

</x-intranet-layout>
