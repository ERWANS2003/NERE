@php
    $deptColor     = $deptColor ?? '#e0a52f';
    $showRequester = $showRequester ?? false;
@endphp

@if ($submissions->isEmpty())
    <div class="rounded-xl border py-12 text-center" style="border-color: var(--line-subtle);">
        <svg class="mx-auto mb-3 h-10 w-10 opacity-25" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
        </svg>
        <p class="text-sm" style="color: var(--text-muted);">Aucune demande.</p>
    </div>
@else
    <div class="overflow-x-auto rounded-xl border" style="border-color: var(--line-subtle);">
        <table class="w-full text-sm">
            <thead>
                <tr style="background-color: var(--surface-inset); border-bottom: 1px solid var(--line-subtle);">
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Référence</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Formulaire</th>
                    @if ($showRequester)
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Demandeur</th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Priorité</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Créée le</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($submissions as $sub)
                    @php
                        $statusColors = [
                            'brouillon'    => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
                            'soumise'      => ['bg' => '#eff6ff', 'text' => '#2563eb'],
                            'en_validation'=> ['bg' => '#fef3c7', 'text' => '#d97706'],
                            'assignee'     => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
                            'en_cours'     => ['bg' => '#ecfdf5', 'text' => '#059669'],
                            'en_attente'   => ['bg' => '#fff7ed', 'text' => '#ea580c'],
                            'escaladee'    => ['bg' => '#fdf4ff', 'text' => '#9333ea'],
                            'resolue'      => ['bg' => '#ecfdf5', 'text' => '#16a34a'],
                            'cloturee'     => ['bg' => '#f9fafb', 'text' => '#6b7280'],
                            'rejetee'      => ['bg' => '#fef2f2', 'text' => '#dc2626'],
                            'annulee'      => ['bg' => '#fef2f2', 'text' => '#dc2626'],
                        ];
                        $sc = $statusColors[$sub->status] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
                        $priColors = ['basse' => '#6b7280', 'normale' => '#2563eb', 'haute' => '#d97706', 'critique' => '#dc2626'];
                        $pc = $priColors[$sub->priority] ?? '#6b7280';
                        $statusLabels = [
                            'brouillon' => 'Brouillon', 'soumise' => 'Soumise',
                            'en_validation' => 'En validation', 'assignee' => 'Assignée',
                            'en_cours' => 'En cours', 'en_attente' => 'En attente',
                            'escaladee' => 'Escaladée', 'resolue' => 'Résolue',
                            'cloturee' => 'Clôturée', 'rejetee' => 'Rejetée', 'annulee' => 'Annulée',
                        ];
                    @endphp
                    <tr class="border-b transition-colors hover:bg-opacity-30"
                        style="border-color: var(--line-subtle);"
                        onmouseover="this.style.backgroundColor='{{ $deptColor }}08'"
                        onmouseout="this.style.backgroundColor=''">
                        <td class="px-4 py-3 font-mono text-xs font-semibold" style="color: {{ $deptColor }};">
                            {{ $sub->reference }}
                        </td>
                        <td class="px-4 py-3 max-w-[200px] truncate font-medium" style="color: var(--text-body);">
                            {{ $sub->form->name }}
                        </td>
                        @if ($showRequester)
                            <td class="px-4 py-3" style="color: var(--text-muted);">
                                {{ $sub->requester->name }}
                            </td>
                        @endif
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                  style="background-color: {{ $sc['bg'] }}; color: {{ $sc['text'] }};">
                                {{ $statusLabels[$sub->status] ?? $sub->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold capitalize" style="color: {{ $pc }};">
                                {{ $sub->priority }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs" style="color: var(--text-muted);">
                            {{ $sub->created_at->format('d/m/Y') }}
                            @if ($sub->isOverdue())
                                <span class="ml-1 text-red-500 font-bold" title="Délai dépassé">⚠</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('intranet.submissions.show', $sub) }}"
                               class="rounded-lg px-3 py-1 text-xs font-semibold transition-colors"
                               style="color: {{ $deptColor }}; background-color: {{ $deptColor }}15;"
                               onmouseover="this.style.backgroundColor='{{ $deptColor }}25'"
                               onmouseout="this.style.backgroundColor='{{ $deptColor }}15'">
                                Voir →
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
