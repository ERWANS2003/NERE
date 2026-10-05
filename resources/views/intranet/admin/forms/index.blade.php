@extends('intranet.layouts.app')

@section('titre', 'Formulaires — ' . $department->name)

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <a href="{{ route('intranet.admin.departments.index') }}" class="hover:underline" style="color: var(--brand);">Départements</a>
    <span class="mx-1 opacity-40">/</span>
    <span>{{ $department->name }}</span>
@endsection

@section('content')

<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <p class="text-[10px] font-extrabold uppercase tracking-widest mb-1" style="color: {{ $department->color }};">{{ $department->tag }}</p>
        <h1 class="text-xl font-bold" style="color: var(--text-strong);">Formulaires — {{ $department->name }}</h1>
    </div>
</div>

{{-- Par service --}}
@foreach ($services as $service)
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                📂 {{ $service->name }}
            </h2>
            {{-- Créer un formulaire dans ce service --}}
            <form method="POST" action="{{ route('intranet.admin.forms.store', $department) }}"
                  class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <input name="name" required placeholder="Nom du formulaire" class="nm-input text-sm" style="width: 200px;">
                <button type="submit" class="nm-btn nm-btn-primary nm-btn-sm">+ Créer</button>
            </form>
        </div>

        @if ($service->forms->isEmpty())
            <p class="text-sm italic" style="color: var(--text-subtle);">Aucun formulaire dans ce service.</p>
        @else
            <div class="overflow-x-auto rounded-xl border" style="border-color: var(--line-subtle);">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background: var(--surface-inset); border-bottom: 1px solid var(--line-subtle);">
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Nom</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Code</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Version</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Statut</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($service->forms as $form)
                            @php
                                $sc = ['draft' => ['bg' => '#f3f4f6', 'text' => '#6b7280', 'label' => 'Brouillon'],
                                       'published' => ['bg' => '#ecfdf5', 'text' => '#16a34a', 'label' => 'Publié'],
                                       'archived' => ['bg' => '#f9fafb', 'text' => '#9ca3af', 'label' => 'Archivé']];
                                $s = $sc[$form->status] ?? $sc['draft'];
                            @endphp
                            <tr class="border-b" style="border-color: var(--line-subtle);">
                                <td class="px-4 py-3 font-medium" style="color: var(--text-body);">{{ $form->name }}</td>
                                <td class="px-4 py-3 font-mono text-xs" style="color: var(--text-muted);">{{ $form->code }}</td>
                                <td class="px-4 py-3 text-center text-xs font-semibold" style="color: var(--text-body);">v{{ $form->version }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                          style="background: {{ $s['bg'] }}; color: {{ $s['text'] }};">
                                        {{ $s['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right flex items-center justify-end gap-3">
                                    <a href="{{ route('intranet.admin.forms.edit', [$department, $form]) }}"
                                       class="text-xs font-semibold" style="color: var(--brand);">Éditer</a>

                                    @if ($form->status === 'draft')
                                        <form method="POST" action="{{ route('intranet.admin.forms.publish', [$department, $form]) }}"
                                              style="display:inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold text-green-600">Publier</button>
                                        </form>
                                    @elseif ($form->status === 'published')
                                        <form method="POST" action="{{ route('intranet.admin.forms.archive', [$department, $form]) }}"
                                              style="display:inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold" style="color: var(--text-muted);">Archiver</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endforeach

@endsection
