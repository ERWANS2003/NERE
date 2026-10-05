@extends('intranet.layouts.app')

@section('titre', 'Éditer — ' . $form->name)

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <a href="{{ route('intranet.admin.forms.index', $department) }}" class="hover:underline" style="color: var(--brand);">Formulaires</a>
    <span class="mx-1 opacity-40">/</span>
    <span>{{ $form->name }}</span>
@endsection

@section('content')

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- ── Champs existants (2/3) ──────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold" style="color: var(--text-strong);">{{ $form->name }}
                <span class="text-xs font-normal ml-2" style="color: var(--text-muted);">v{{ $form->version }} · {{ $form->status }}</span>
            </h1>
            @if ($form->status === 'draft')
                <form method="POST" action="{{ route('intranet.admin.forms.publish', [$department, $form]) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="nm-btn nm-btn-primary nm-btn-sm">✓ Publier</button>
                </form>
            @endif
        </div>

        {{-- Liste des champs --}}
        <div id="fields-list" class="space-y-2">
            @forelse ($form->fields as $field)
                <div class="flex items-center gap-3 rounded-xl border px-4 py-3"
                     style="background: var(--surface-card); border-color: var(--line-subtle);">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm" style="color: var(--text-body);">
                            {{ $field->label }}
                            @if ($field->required)
                                <span class="text-red-500 ml-1">*</span>
                            @endif
                        </p>
                        <p class="text-xs mt-0.5" style="color: var(--text-muted);">
                            Type : <span class="font-mono">{{ $field->type }}</span>
                            · Position : {{ $field->position }}
                        </p>
                    </div>
                    <form method="POST"
                          action="{{ route('intranet.admin.forms.delete-field', [$department, $form, $field]) }}"
                          onsubmit="return confirm('Supprimer ce champ ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-red-500 hover:text-red-700">
                            Supprimer
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-sm italic py-4" style="color: var(--text-subtle);">Aucun champ. Ajoutez-en un ci-contre.</p>
            @endforelse
        </div>
    </div>

    {{-- ── Panneau ajout champ (1/3) ──────────────────────────── --}}
    <div class="space-y-5">
        <div class="rounded-2xl border p-5" style="background: var(--surface-card); border-color: var(--line-subtle);">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                Ajouter un champ
            </h2>
            <form method="POST" action="{{ route('intranet.admin.forms.add-field', [$department, $form]) }}"
                  class="space-y-3">
                @csrf
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">Label *</label>
                    <input name="label" required class="nm-input w-full text-sm" placeholder="Ex: Date de début">
                </div>
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">Type *</label>
                    <select name="type" required class="nm-input w-full text-sm">
                        @foreach (\App\Models\Intranet\FormField::TYPES as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm cursor-pointer" style="color: var(--text-muted);">
                        <input type="checkbox" name="required" value="1" class="h-3.5 w-3.5 accent-amber-500">
                        Champ obligatoire
                    </label>
                </div>
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">
                        Options JSON (select / multiselect)
                    </label>
                    <textarea name="options" rows="3" class="nm-textarea w-full text-xs font-mono"
                              placeholder='[{"label":"Oui","value":"oui"}]'></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold mb-1 block" style="color: var(--text-muted);">
                        Règles JSON (max, min, mimes…)
                    </label>
                    <textarea name="rules" rows="2" class="nm-textarea w-full text-xs font-mono"
                              placeholder='{"max":255}'></textarea>
                </div>
                <button type="submit" class="nm-btn nm-btn-primary nm-btn-sm w-full">
                    + Ajouter ce champ
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
