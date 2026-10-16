<x-intranet-layout title="Tableau de bord">

    @php
        $headerHour = (int) now()->format('H');
        $headerGreeting = $headerHour < 18 ? 'Bonjour' : 'Bonsoir';
        $headerFirstName = auth()->user()->first_name ?? explode(' ', auth()->user()->name)[0];
    @endphp
    <section
        class="relative isolate -mx-6 -mt-6 mb-6 overflow-hidden text-white"
        style="background: linear-gradient(135deg, #3d0f0e 0%, #6b1d1a 55%, #c83530 100%);"
    >
        <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-32 h-80 w-80 rounded-full border border-white/10"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-2 top-8 h-52 w-52 rounded-full border border-or-400/30"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 right-1/3 h-64 w-64 rounded-full bg-or-400/10 blur-3xl"></div>
        <div class="relative mx-auto max-w-screen-2xl px-5 py-8 sm:px-8 sm:py-10 lg:px-10">
            <div class="max-w-4xl">
                <h1 class="text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-5xl">
                    Bienvenue dans l’intranet
                    <span class="mt-1 block text-or-300">NÉRÉ Mining</span>
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-white/80 sm:text-base">
                    {{ $headerGreeting }}, {{ $headerFirstName }}. Retrouvez vos services, vos équipes et les informations essentielles de l’entreprise au même endroit.
                </p>
                <div class="mt-6 h-1 w-20 rounded-full bg-or-400"></div>
            </div>
        </div>
        <div aria-hidden="true" class="absolute bottom-0 left-0 h-px w-full bg-linear-to-r from-transparent via-or-400/80 to-transparent"></div>
    </section>

    {{-- ============================================================
         Statistiques rapides
    ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg border border-graphite-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-nere-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-graphite-900">{{ $stats['departments'] }}</div>
                    <div class="text-xs text-graphite-500">Départements</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-graphite-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-or-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-or-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-graphite-900">{{ $stats['users'] }}</div>
                    <div class="text-xs text-graphite-500">Employés</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-graphite-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-graphite-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-graphite-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-graphite-900">{{ $stats['events'] }}</div>
                    <div class="text-xs text-graphite-500">Événements à venir</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-graphite-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-nere-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-graphite-900">{{ $stats['submissions'] }}</div>
                    <div class="text-xs text-graphite-500">Mes demandes</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Layout principal : grid départements + colonne widgets
    ============================================================ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Colonne principale (2/3) --}}
        <div class="xl:col-span-2 space-y-8">

            {{-- JOUR 2-3 — Grid des départements --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-graphite-900">Départements</h2>
                    <a href="{{ route('intranet.departments.index') }}" class="text-sm text-nere-600 hover:text-nere-700 font-medium">
                        Tout voir →
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($departments as $department)
                        <a
                            href="{{ route('intranet.departments.show', strtolower($department->code)) }}"
                            class="group relative overflow-hidden bg-white rounded-xl border-2 border-graphite-200 hover:border-nere-500 hover:shadow-lg transition-all duration-300"
                        >
                            {{-- Illustration --}}
                            <div class="relative h-28 bg-gradient-to-br from-graphite-50 to-graphite-100 overflow-hidden">
                                @if($department->image_url)
                                    <img src="{{ $department->image_url }}" alt="{{ $department->name }}"
                                         loading="lazy" decoding="async"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-graphite-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                @endif
                                {{-- Tag jaune/or --}}
                                <span class="absolute top-2 left-2 px-2 py-0.5 bg-or-400 text-graphite-900 rounded text-[10px] font-bold uppercase tracking-wider">
                                    {{ $department->tag ?? $department->code }}
                                </span>
                            </div>

                            {{-- Infos --}}
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-2 sm:min-h-[2.75rem]">
                                    <h3 class="min-w-0 font-bold leading-snug text-graphite-900 group-hover:text-nere-600 transition">
                                        {{ $department->name }}
                                    </h3>
                                    <svg class="w-5 h-5 text-graphite-400 group-hover:text-nere-600 group-hover:translate-x-1 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                                <p class="text-xs text-graphite-500 mt-1">
                                    {{ $department->services_count }} service(s)
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- JOUR 5 — Section Mes demandes récentes (remplace la section Tickets) --}}
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-graphite-900">Mes demandes récentes</h2>
                    <a href="{{ route('intranet.submissions.index') }}" class="text-sm text-nere-600 hover:text-nere-700 font-medium">
                        Tout voir →
                    </a>
                </div>

                <div class="bg-white rounded-lg border border-graphite-200 overflow-hidden">
                    @if($recentSubmissions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-graphite-50 text-graphite-600">
                                    <tr>
                                        <th class="text-left font-semibold px-4 py-3">Réf</th>
                                        <th class="text-left font-semibold px-4 py-3">Formulaire</th>
                                        <th class="text-left font-semibold px-4 py-3 hidden md:table-cell">Département</th>
                                        <th class="text-left font-semibold px-4 py-3">Statut</th>
                                        <th class="text-left font-semibold px-4 py-3 hidden sm:table-cell">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-graphite-100">
                                    @foreach($recentSubmissions as $submission)
                                        <tr class="hover:bg-graphite-50 transition">
                                            <td class="px-4 py-3">
                                                <a href="{{ route('intranet.submissions.show', $submission->id) }}" class="font-medium text-nere-600 hover:text-nere-700">
                                                    #{{ str_pad($submission->id, 5, '0', STR_PAD_LEFT) }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-graphite-900">
                                                {{ $submission->form?->name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-graphite-600 hidden md:table-cell">
                                                {{ $submission->form?->department?->name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $statutMap = [
                                                        'pending' => ['label' => 'En attente', 'type' => 'warning'],
                                                        'approved' => ['label' => 'Approuvée', 'type' => 'success'],
                                                        'rejected' => ['label' => 'Rejetée', 'type' => 'danger'],
                                                    ];
                                                    $statut = $statutMap[$submission->status] ?? ['label' => ucfirst($submission->status ?? 'N/A'), 'type' => 'info'];
                                                @endphp
                                                <x-badge :type="$statut['type']">{{ $statut['label'] }}</x-badge>
                                            </td>
                                            <td class="px-4 py-3 text-graphite-500 hidden sm:table-cell">
                                                {{ $submission->created_at?->format('d/m/Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-10 text-center">
                            <svg class="w-12 h-12 text-graphite-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-graphite-600 mb-1">Aucune demande pour le moment</p>
                            <a href="{{ route('intranet.forms.index') }}" class="text-sm text-nere-600 hover:text-nere-700 font-medium">
                                Faire une nouvelle demande →
                            </a>
                        </div>
                    @endif
                </div>
            </section>

        </div>

        {{-- Colonne widgets (1/3) --}}
        <div class="space-y-6">

            {{-- JOUR 4 — Widget Annonces --}}
            <div class="bg-white rounded-lg border border-graphite-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-graphite-900">Annonces</h2>
                    <a href="{{ route('intranet.announcements.index') }}" class="text-xs text-nere-600 hover:text-nere-700 font-medium">
                        Tout voir →
                    </a>
                </div>
                <div class="space-y-4">
                    @forelse($recentAnnouncements as $announcement)
                        <a href="{{ route('intranet.announcements.show', $announcement) }}"
                           class="block border-l-4 pl-3 py-1 hover:bg-graphite-50 rounded-r transition
                           {{ $announcement->priority === 'urgent' ? 'border-nere-500' : ($announcement->priority === 'important' ? 'border-or-400' : 'border-graphite-300') }}">
                            <h4 class="font-medium text-sm text-graphite-900 line-clamp-1">{{ $announcement->title }}</h4>
                            <p class="text-xs text-graphite-500 line-clamp-2 mt-0.5">{{ \Illuminate\Support\Str::limit($announcement->body, 80) }}</p>
                            <span class="text-[11px] text-graphite-400">{{ $announcement->published_at?->diffForHumans() }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-graphite-500 text-center py-4">Aucune annonce</p>
                    @endforelse
                </div>
            </div>

            {{-- JOUR 4 — Widget Agenda --}}
            <div class="bg-white rounded-lg border border-graphite-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-graphite-900">Agenda</h2>
                    <a href="{{ route('intranet.calendar') }}" class="text-xs text-nere-600 hover:text-nere-700 font-medium">
                        Calendrier →
                    </a>
                </div>
                <div class="space-y-3">
                    @forelse($upcomingEvents as $event)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-12 text-center bg-graphite-50 rounded-lg py-1">
                                <div class="text-[10px] text-graphite-500 uppercase">{{ $event->event_date->locale('fr')->isoFormat('MMM') }}</div>
                                <div class="text-xl font-bold text-graphite-900 leading-none">{{ $event->event_date->format('d') }}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-sm text-graphite-900 truncate">{{ $event->title }}</h4>
                                <p class="text-xs text-graphite-500 mt-0.5">
                                    @if($event->all_day)
                                        Toute la journée
                                    @elseif($event->event_time)
                                        {{ \Carbon\Carbon::parse($event->event_time)->format('H:i') }}
                                    @endif
                                    @if($event->department)
                                        · {{ $event->department->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-graphite-500 text-center py-4">Aucun événement à venir</p>
                    @endforelse
                </div>
            </div>

            {{-- Accès rapides --}}
            <div class="bg-white rounded-lg border border-graphite-200 p-6">
                <h2 class="text-lg font-bold text-graphite-900 mb-4">Accès rapides</h2>
                <div class="space-y-2">
                    <a href="{{ route('intranet.forms.index') }}" class="flex items-center gap-3 p-2.5 hover:bg-graphite-50 rounded-lg transition">
                        <div class="w-8 h-8 bg-nere-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-graphite-700">Nouvelle demande</span>
                    </a>
                    <a href="{{ route('intranet.directory') }}" class="flex items-center gap-3 p-2.5 hover:bg-graphite-50 rounded-lg transition">
                        <div class="w-8 h-8 bg-or-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-or-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-graphite-700">Annuaire</span>
                    </a>
                    <a href="{{ route('intranet.resources') }}" class="flex items-center gap-3 p-2.5 hover:bg-graphite-50 rounded-lg transition">
                        <div class="w-8 h-8 bg-graphite-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-graphite-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-graphite-700">Ressources</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</x-intranet-layout>
