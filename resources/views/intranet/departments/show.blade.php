@extends('intranet.layouts.app')

@section('titre', $department->name)

@section('breadcrumb')
    <span class="mx-1 opacity-40">/</span>
    <span>{{ $department->name }}</span>
@endsection

@section('content')

{{-- ── En-tête département ─────────────────────────────────── --}}
<div class="mb-8 flex flex-wrap items-center gap-4">
    <div class="flex h-12 w-12 items-center justify-center rounded-2xl shrink-0"
         style="background-color: {{ $department->color }}20;">
        @include('intranet.partials.dept-icon', ['icon' => $department->icon, 'color' => $department->color])
    </div>
    <div>
        <p class="text-[10px] font-extrabold uppercase tracking-[0.15em]"
           style="color: {{ $department->color }};">{{ $department->tag }}</p>
        <h1 class="text-xl font-bold" style="color: var(--text-strong);">{{ $department->name }}</h1>
        @if ($department->description)
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">{{ $department->description }}</p>
        @endif
    </div>

    {{-- Badges rôle --}}
    <div class="ml-auto flex flex-wrap gap-2">
        @if ($pivot->role === 'director')
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 uppercase tracking-wide">
                Directeur
            </span>
        @elseif ($pivot->role === 'technician')
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800 uppercase tracking-wide">
                Technicien N{{ $pivot->tech_level }}
            </span>
        @endif
    </div>
</div>

{{-- ── Onglets ─────────────────────────────────────────────── --}}
<div x-data="{ tab: 'services' }">

    <div class="mb-6 flex gap-1 border-b" style="border-color: var(--line-subtle);">
        @php $tabs = [['id' => 'services', 'label' => 'Services'], ['id' => 'mine', 'label' => 'Mes demandes']]; @endphp
        @if ($queue !== null)
            @php $tabs[] = ['id' => 'queue', 'label' => 'File de traitement']; @endphp
        @endif

        @foreach ($tabs as $t)
            <button @click="tab = '{{ $t['id'] }}'"
                    :class="tab === '{{ $t['id'] }}' ? 'border-b-2 font-semibold' : 'opacity-60'"
                    class="px-4 py-2.5 text-sm transition-all -mb-px"
                    style=":class binding handled above"
                    :style="tab === '{{ $t['id'] }}' ? 'color: {{ $department->color }}; border-color: {{ $department->color }};' : 'color: var(--text-muted);'">
                {{ $t['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ── Onglet : Services & formulaires ─────────────────── --}}
    <div x-show="tab === 'services'">
        @forelse ($services as $service)
            <div class="mb-6">
                <h2 class="mb-3 text-sm font-bold uppercase tracking-wide" style="color: var(--text-muted);">
                    {{ $service->name }}
                </h2>
                @if ($service->publishedForms->isEmpty())
                    <p class="text-sm italic" style="color: var(--text-subtle);">Aucun formulaire disponible.</p>
                @else
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($service->publishedForms as $form)
                            <a href="{{ route('intranet.forms.show', $form) }}"
                               class="group flex items-start gap-3 rounded-xl border p-4 transition-all hover:-translate-y-0.5 hover:shadow-md"
                               style="background-color: var(--surface-card); border-color: var(--line-subtle);">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                     style="background-color: {{ $department->color }}15;">
                                    <svg class="h-5 w-5" fill="none" stroke="{{ $department->color }}" viewBox="0 0 24 24" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-sm" style="color: var(--text-strong);">
                                        {{ $form->name }}
                                    </p>
                                    @if ($form->description)
                                        <p class="mt-0.5 line-clamp-2 text-xs" style="color: var(--text-muted);">
                                            {{ $form->description }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm italic" style="color: var(--text-subtle);">Aucun service dans ce département.</p>
        @endforelse
    </div>

    {{-- ── Onglet : Mes demandes ──────────────────────────── --}}
    <div x-show="tab === 'mine'">
        @include('intranet.partials.submissions-table', ['submissions' => $mySubmissions, 'deptColor' => $department->color])
    </div>

    {{-- ── Onglet : File de traitement ────────────────────── --}}
    @if ($queue !== null)
        <div x-show="tab === 'queue'">
            @include('intranet.partials.submissions-table', ['submissions' => $queue, 'deptColor' => $department->color, 'showRequester' => true])
        </div>
    @endif
</div>
@endsection
