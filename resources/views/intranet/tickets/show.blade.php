@php
    $breadcrumbs = [
        ['label' => 'Support IT', 'url' => route('intranet.tickets.index')],
        ['label' => $ticket->number],
    ];
    $priorityStyles = [
        'low' => 'text-graphite-600',
        'normal' => 'text-blue-700',
        'high' => 'text-amber-700',
        'urgent' => 'text-red-700 font-semibold',
    ];
    $responseAtRisk = ! $ticket->first_responded_at
        && $ticket->response_warning_at
        && now()->greaterThanOrEqualTo($ticket->response_warning_at);
    $responseExpired = ! $ticket->first_responded_at
        && $ticket->first_response_due_at
        && now()->greaterThanOrEqualTo($ticket->first_response_due_at);
    $resolutionAtRisk = ! $ticket->resolved_at
        && ! $ticket->closed_at
        && $ticket->resolution_warning_at
        && now()->greaterThanOrEqualTo($ticket->resolution_warning_at);
    $resolutionExpired = ! $ticket->resolved_at
        && ! $ticket->closed_at
        && $ticket->resolution_due_at
        && now()->greaterThanOrEqualTo($ticket->resolution_due_at);
@endphp

<x-intranet-layout :title="$ticket->number" :breadcrumbs="$breadcrumbs">
    <div class="mx-auto max-w-4xl space-y-6">
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-xl border border-graphite-200 bg-white">
            <div class="border-b border-graphite-100 p-6">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-mono text-sm text-graphite-500">{{ $ticket->number }}</span>
                    <x-intranet.status-badge :status="$ticket->status" />
                    <span class="text-sm {{ $priorityStyles[$ticket->priority->value] }}">Priorité {{ $ticket->priority->label() }}</span>
                </div>
                <h1 class="mt-3 text-2xl font-bold text-graphite-900">{{ $ticket->subject }}</h1>
                <p class="mt-2 text-sm text-graphite-500">
                    {{ $ticket->category->label() }} · créé le {{ $ticket->created_at->format('d/m/Y à H:i') }}
                    · par {{ $ticket->requester?->name ?? 'Compte supprimé' }}
                </p>
            </div>

            <div class="space-y-5 p-6">
                <div class="whitespace-pre-line break-words text-sm leading-6 text-graphite-800">{{ $ticket->description }}</div>

                @if($ticket->attachments->isNotEmpty())
                    <div class="border-t border-graphite-100 pt-4">
                        <h2 class="mb-2 text-sm font-semibold text-graphite-800">Pièces jointes</h2>
                        <ul class="space-y-1">
                            @foreach($ticket->attachments as $attachment)
                                <li><a class="text-sm text-nere-700 hover:underline" href="{{ route('intranet.tickets.attachments.download', $attachment) }}">{{ $attachment->original_name }} ({{ number_format($attachment->size / 1024, 0) }} Ko)</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <dl class="grid gap-4 border-t border-graphite-100 pt-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-graphite-500">Responsable IT</dt><dd class="mt-1 font-medium text-graphite-800">{{ $ticket->assignee?->name ?? 'Non attribué' }}</dd></div>
                    @if($ticket->resolved_at)<div><dt class="text-graphite-500">Résolu le</dt><dd class="mt-1 font-medium text-graphite-800">{{ $ticket->resolved_at->format('d/m/Y à H:i') }}</dd></div>@endif
                    @if($ticket->closed_at)<div><dt class="text-graphite-500">Fermé le</dt><dd class="mt-1 font-medium text-graphite-800">{{ $ticket->closed_at->format('d/m/Y à H:i') }}</dd></div>@endif
                </dl>

                <dl class="grid gap-4 border-t border-graphite-100 pt-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-graphite-500">Première réponse</dt>
                        <dd class="mt-1 font-medium {{ $responseExpired ? 'text-red-700' : ($responseAtRisk ? 'text-amber-700' : 'text-graphite-800') }}">
                            @if($ticket->first_responded_at)
                                Répondue le {{ $ticket->first_responded_at->format('d/m/Y à H:i') }}
                            @elseif($ticket->first_response_due_at)
                                Échéance le {{ $ticket->first_response_due_at->format('d/m/Y à H:i') }}
                                @if($responseExpired) · Délai dépassé @elseif($responseAtRisk) · À risque @endif
                            @else
                                Échéance en cours de calcul
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-graphite-500">Résolution</dt>
                        <dd class="mt-1 font-medium {{ $resolutionExpired ? 'text-red-700' : ($resolutionAtRisk ? 'text-amber-700' : 'text-graphite-800') }}">
                            @if($ticket->resolved_at || $ticket->closed_at)
                                Résolu
                            @elseif($ticket->resolution_due_at)
                                Échéance le {{ $ticket->resolution_due_at->format('d/m/Y à H:i') }}
                                @if($resolutionExpired) · Délai dépassé @elseif($resolutionAtRisk) · À risque @endif
                            @else
                                Échéance en cours de calcul
                            @endif
                        </dd>
                    </div>
                </dl>
                <p class="text-xs text-graphite-500">
                    Délais en heures ouvrées : lundi–vendredi 08:00–17:00, samedi 08:00–14:00.
                </p>
            </div>
        </section>

        @if($isAgent)
            <section class="rounded-xl border border-graphite-200 bg-white p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-graphite-900">Traitement du ticket</h2>
                    @if($canTake && $ticket->status->value !== 'closed')
                        <form method="POST" action="{{ route('intranet.tickets.take', $ticket) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-nere-700 px-4 py-2 text-sm font-semibold text-nere-700 hover:bg-nere-50">
                                Prendre en charge
                            </button>
                        </form>
                    @endif
                </div>
                <form method="POST" action="{{ route('intranet.tickets.update', $ticket) }}" class="grid gap-4 {{ $canAssign ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium text-graphite-700">Statut</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $ticket->status->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="priority" class="mb-1 block text-sm font-medium text-graphite-700">Priorité</label>
                        <select id="priority" name="priority" class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                            @foreach($priorities as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', $ticket->priority->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    @if($canAssign)
                        <div>
                            <label for="assignee_id" class="mb-1 block text-sm font-medium text-graphite-700">Affecter à un technicien</label>
                            <select id="assignee_id" name="assignee_id" class="w-full rounded-lg border border-graphite-300 px-3 py-2">
                                <option value="">Non attribué</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected((string) old('assignee_id', $ticket->assignee_id) === (string) $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                            @error('assignee_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div class="{{ $canAssign ? 'sm:col-span-3' : 'sm:col-span-2' }}">
                        <button type="submit" class="rounded-lg bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Enregistrer le traitement</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="rounded-xl border border-graphite-200 bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold text-graphite-900">Échanges et suivi</h2>
            <div class="mb-6 space-y-4">
                @foreach($ticket->activities as $activity)
                    <div class="flex gap-3 text-xs text-graphite-500">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-nere-500"></span>
                        <p>
                            {{ match ($activity->event) {
                                'created' => 'Ticket créé',
                                'taken' => 'Ticket pris en charge',
                                default => 'Ticket mis à jour',
                            } }}
                            par {{ $activity->user?->name ?? 'Compte supprimé' }}
                            · {{ $activity->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                @endforeach

                @foreach($ticket->comments->filter(fn ($comment) => $isAgent || ! $comment->is_internal) as $comment)
                    <article class="rounded-lg border {{ $comment->is_internal ? 'border-amber-200 bg-amber-50' : 'border-graphite-200 bg-graphite-50' }} p-4">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-xs text-graphite-500">
                            <span class="font-semibold text-graphite-800">{{ $comment->user?->name ?? 'Compte supprimé' }} @if($comment->is_internal)<span class="ml-1 rounded bg-amber-200 px-2 py-0.5 text-amber-900">Note interne IT</span>@endif</span>
                            <time>{{ $comment->created_at->format('d/m/Y à H:i') }}</time>
                        </div>
                        <p class="whitespace-pre-line break-words text-sm text-graphite-800">{{ $comment->body }}</p>
                        @if($comment->attachments->isNotEmpty())
                            <ul class="mt-3 space-y-1">
                                @foreach($comment->attachments as $attachment)
                                    <li><a class="text-sm text-nere-700 hover:underline" href="{{ route('intranet.tickets.attachments.download', $attachment) }}">{{ $attachment->original_name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>

            @if($ticket->status->value !== 'closed')
                <form method="POST" action="{{ route('intranet.tickets.comments.store', $ticket) }}" enctype="multipart/form-data" class="space-y-4 border-t border-graphite-100 pt-5">
                    @csrf
                    <div>
                        <label for="body" class="mb-1 block text-sm font-medium text-graphite-800">Ajouter un commentaire</label>
                        <textarea id="body" name="body" rows="4" maxlength="10000" required class="w-full rounded-lg border border-graphite-300 px-3 py-2">{{ old('body') }}</textarea>
                        @error('body')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <input type="file" name="attachments[]" multiple accept=".pdf,.png,.jpg,.jpeg,.txt,.doc,.docx,.xls,.xlsx" class="block w-full text-sm text-graphite-700">
                            <p class="mt-1 text-xs text-graphite-500">5 fichiers max, 10 Mo chacun.</p>
                            @error('attachments')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                            @error('attachments.*')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-center gap-4">
                            @if($isAgent)
                                <label class="flex items-center gap-2 text-sm text-graphite-700">
                                    <input type="checkbox" name="is_internal" value="1" @checked(old('is_internal'))>
                                    Note interne (visible par l’IT uniquement)
                                </label>
                            @endif
                            <button type="submit" class="shrink-0 rounded-lg bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Commenter</button>
                        </div>
                    </div>
                </form>
            @else
                <p class="border-t border-graphite-100 pt-4 text-sm text-graphite-500">Ce ticket est fermé ; aucun nouveau commentaire ne peut être ajouté.</p>
            @endif
        </section>

        <a href="{{ route('intranet.tickets.index') }}" class="inline-flex text-sm font-medium text-nere-700 hover:underline">← Retour aux tickets</a>
    </div>
</x-intranet-layout>
