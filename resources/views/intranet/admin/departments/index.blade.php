@extends('intranet.layouts.app')

@section('titre', 'Gestion des départements')

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <a href="{{ route('intranet.admin.dashboard') }}" class="hover:underline" style="color: var(--brand);">Admin</a>
    <span class="mx-1 opacity-40">/</span>
    <span>Départements</span>
@endsection

@section('content')

<div class="mb-6 flex items-center justify-between gap-4">
    <h1 class="text-xl font-bold" style="color: var(--text-strong);">Gestion des départements</h1>
    <button onclick="document.getElementById('modal-add-dept').classList.remove('hidden')"
            class="nm-btn nm-btn-primary nm-btn-sm">
        + Nouveau département
    </button>
</div>

{{-- Table des départements --}}
<div class="rounded-2xl border overflow-hidden" style="background: var(--surface-card); border-color: var(--line-subtle);">
    <table class="w-full text-sm">
        <thead>
            <tr style="background: var(--surface-inset); border-bottom: 1px solid var(--line-subtle);">
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Code</th>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Tag</th>
                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Membres</th>
                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Services</th>
                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Actif</th>
                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wide" style="color: var(--text-muted);">Position</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($departments as $dept)
                <tr class="border-b" style="border-color: var(--line-subtle);">
                    <td class="px-4 py-3 font-mono text-xs font-bold" style="color: {{ $dept->color }};">
                        {{ $dept->code }}
                    </td>
                    <td class="px-4 py-3 font-medium" style="color: var(--text-body);">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $dept->color }};"></span>
                            {{ $dept->name }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-xs uppercase tracking-wide font-semibold" style="color: {{ $dept->color }};">
                        {{ $dept->tag }}
                    </td>
                    <td class="px-4 py-3 text-center text-sm font-semibold" style="color: var(--text-body);">{{ $dept->users_count }}</td>
                    <td class="px-4 py-3 text-center text-sm font-semibold" style="color: var(--text-body);">{{ $dept->services_count }}</td>
                    <td class="px-4 py-3 text-center">
                        @if ($dept->is_active)
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                        @else
                            <span class="inline-block w-2 h-2 rounded-full bg-gray-300"></span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-xs" style="color: var(--text-muted);">{{ $dept->position }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('intranet.admin.forms.index', $dept) }}"
                           class="text-xs font-semibold mr-3" style="color: var(--brand);">Formulaires</a>
                        <button onclick="openEditDept({{ $dept->id }}, {{ json_encode($dept->toArray()) }})"
                                class="text-xs font-semibold" style="color: var(--text-muted);">Modifier</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Modal ajout département --}}
<div id="modal-add-dept" class="hidden fixed inset-0 z-50 flex items-center justify-center"
     style="background: rgba(0,0,0,0.5);">
    <div class="w-full max-w-lg rounded-2xl p-6 shadow-xl" style="background: var(--surface-card);">
        <h2 class="mb-4 text-base font-bold" style="color: var(--text-strong);">Nouveau département</h2>
        <form method="POST" action="{{ route('intranet.admin.departments.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">Code *</label>
                    <input name="code" required placeholder="IT" class="nm-input w-full text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">Tag *</label>
                    <input name="tag" required placeholder="TECHNOLOGIES" class="nm-input w-full text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">Nom *</label>
                <input name="name" required placeholder="Département IT" class="nm-input w-full text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">Couleur</label>
                    <input name="color" type="color" value="#2563eb" class="nm-input w-full h-10">
                </div>
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">Position</label>
                    <input name="position" type="number" value="99" class="nm-input w-full text-sm">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-dept').classList.add('hidden')"
                        class="nm-btn nm-btn-secondary nm-btn-sm">Annuler</button>
                <button type="submit" class="nm-btn nm-btn-primary nm-btn-sm">Créer</button>
            </div>
        </form>
    </div>
</div>

@endsection
