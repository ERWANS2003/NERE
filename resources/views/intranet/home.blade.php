@extends('intranet.layouts.app')

@section('titre', 'Accueil')

@section('content')

{{-- ── En-tête ────────────────────────────────────────────── --}}
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color: var(--spark);">
            Néré Mining · Intranet
        </p>
        <h1 class="text-2xl font-bold" style="color: var(--text-strong);">
            Bonjour, {{ auth()->user()->name }} 👋
        </h1>
        <p class="mt-1 text-sm" style="color: var(--text-muted);">
            Sélectionnez un département pour accéder à ses services et formulaires.
        </p>
    </div>

    <a href="{{ route('intranet.submissions.mine') }}"
       class="nm-btn nm-btn-secondary nm-btn-sm flex items-center gap-1.5">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
        </svg>
        Mes demandes
    </a>
</div>

{{-- ── Grille de cartes ────────────────────────────────────── --}}
@if ($departments->isEmpty())
    <div class="rounded-2xl border p-16 text-center" style="border-color: var(--line-subtle);">
        <svg class="mx-auto mb-4 h-12 w-12 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
        </svg>
        <p class="text-sm font-medium" style="color: var(--text-muted);">
            Aucun département ne vous est encore attribué.
        </p>
        <p class="text-xs mt-1" style="color: var(--text-subtle);">
            Contactez votre administrateur pour obtenir un accès.
        </p>
    </div>
@else
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($departments as $dept)
            @php
                $user        = auth()->user();
                $pivot       = $user->is_super_admin
                    ? null
                    : $dept->users()->where('users.id', $user->id)->first()?->pivot;
                $role        = $user->is_super_admin ? 'director' : ($pivot?->role ?? 'user');
                $services    = $dept->services->where('is_active', true);
                $pending     = \App\Models\Intranet\Submission::whereHas(
                    'form.service', fn($q) => $q->where('department_id', $dept->id)
                )->whereNotIn('status', \App\Models\Intranet\Submission::TERMINAL_STATUSES)
                 ->count();
            @endphp

            <div x-data="{ open: false }"
                 class="group relative flex flex-col rounded-2xl border transition-all duration-200 overflow-hidden"
                 style="background-color: var(--surface-card); border-color: var(--line-subtle);">

                {{-- Bande couleur haute --}}
                <div class="h-1.5 w-full" style="background-color: {{ $dept->color }};"></div>

                <div class="flex flex-1 flex-col p-5">
                    {{-- Tag catégorie --}}
                    <p class="mb-2 text-[10px] font-extrabold uppercase tracking-[0.15em]"
                       style="color: {{ $dept->color }};">
                        {{ $dept->tag }}
                    </p>

                    {{-- Titre + icône --}}
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-base font-bold leading-snug" style="color: var(--text-strong);">
                            {{ $dept->name }}
                        </h2>

                        {{-- Icône département --}}
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                              style="background-color: {{ $dept->color }}1a;">
                            @include('intranet.partials.dept-icon', ['icon' => $dept->icon, 'color' => $dept->color])
                        </span>
                    </div>

                    {{-- Badges rôle + pending --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if ($role === 'director')
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800">
                                Directeur
                            </span>
                        @elseif ($role === 'technician')
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-blue-100 text-blue-800">
                                Technicien N{{ $pivot?->tech_level ?? '' }}
                            </span>
                        @endif

                        @if ($pending > 0)
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                {{ $pending }} en attente
                            </span>
                        @endif
                    </div>

                    {{-- Chevron déroulant les services --}}
                    <button @click="open = !open"
                            class="mt-4 flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                            style="background-color: var(--surface-inset); color: var(--text-body);">
                        <span>{{ $services->count() }} service{{ $services->count() > 1 ? 's' : '' }}</span>
                        <svg class="h-4 w-4 transition-transform duration-200"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"
                             style="color: {{ $dept->color }};">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Liste des services déroulée --}}
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-2 space-y-1">
                        @forelse ($services as $service)
                            @foreach ($service->publishedForms as $form)
                                <a href="{{ route('intranet.forms.show', $form) }}"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-opacity-10"
                                   style="color: var(--text-body);"
                                   onmouseover="this.style.backgroundColor='{{ $dept->color }}15'"
                                   onmouseout="this.style.backgroundColor=''">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="color: {{ $dept->color }};">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                    {{ $form->name }}
                                </a>
                            @endforeach
                        @empty
                            <p class="px-3 py-2 text-xs italic" style="color: var(--text-subtle);">
                                Aucun formulaire disponible
                            </p>
                        @endforelse
                    </div>

                    {{-- Bouton "Accéder" --}}
                    <a href="{{ route('intranet.departments.show', $dept) }}"
                       class="mt-4 flex items-center justify-center gap-1.5 rounded-xl py-2 text-sm font-semibold transition-colors"
                       style="background-color: {{ $dept->color }}1a; color: {{ $dept->color }};"
                       onmouseover="this.style.backgroundColor='{{ $dept->color }}2e'"
                       onmouseout="this.style.backgroundColor='{{ $dept->color }}1a'">
                        Accéder à l'espace
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
