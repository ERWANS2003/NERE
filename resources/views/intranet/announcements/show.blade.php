<x-intranet-layout :title="$announcement->title">

    <!-- Breadcrumbs -->
    @php
        $breadcrumbs = [
            ['label' => 'Annonces', 'url' => route('intranet.announcements.index')],
            ['label' => $announcement->title]
        ];
    @endphp

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm max-w-4xl mx-auto">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-4xl mx-auto">

        <!-- Announcement Header -->
        <article class="bg-white rounded-xl border border-graphite-200 overflow-hidden mb-6">

            <!-- Header -->
            <div class="p-8 border-b border-graphite-100">
                <div class="flex items-center gap-3 mb-4">
                    <!-- Priority Badge -->
                    <x-badge :type="$announcement->priority === 'urgent' ? 'danger' : ($announcement->priority === 'important' ? 'warning' : 'info')">
                        {{ ucfirst($announcement->priority) }}
                    </x-badge>

                    <span class="text-sm text-graphite-600">{{ $announcement->department?->name ?? "Toute l'entreprise" }}</span>

                    <!-- Date -->
                    <span class="text-sm text-graphite-500">
                        {{ $announcement->published_at?->diffForHumans() }}
                    </span>
                </div>

                <h1 class="text-3xl font-bold text-graphite-900 mb-4">
                    {{ $announcement->title }}
                </h1>

                <!-- Creator Info -->
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$announcement->creator" size="md" />
                        <div>
                            <div class="font-medium text-graphite-900">
                                {{ $announcement->creator->name }}
                            </div>
                            <div class="text-sm text-graphite-500">
                                Publié le {{ $announcement->published_at?->format('d F Y à H:i') }}
                            </div>
                        </div>
                    </div>

                    @can('update', $announcement)
                        <div class="flex items-center gap-3">
                            <a href="{{ route('intranet.announcements.edit', $announcement) }}" class="text-sm font-medium text-graphite-600 hover:text-graphite-900">
                                Modifier
                            </a>
                            <form method="POST" action="{{ route('intranet.announcements.destroy', $announcement) }}" onsubmit="return confirm('Retirer cette annonce ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-nere-600 hover:text-nere-700">
                                    Retirer
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>

            <!-- Content -->
            <div class="p-8">
                {{-- Texte brut échappé par Blade (pas de {!! !!}) : empêche toute
                     injection HTML/JS depuis le contenu d'une annonce. --}}
                <div class="text-lg text-graphite-800 whitespace-pre-line">{{ $announcement->body }}</div>

                @if($announcement->attachment_path)
                    <a
                        href="{{ route('intranet.announcements.attachment', $announcement) }}"
                        class="mt-6 inline-flex items-center gap-3 rounded-lg border border-graphite-200 px-4 py-3 text-sm font-medium text-graphite-800 hover:border-nere-200 hover:bg-graphite-50"
                    >
                        <svg class="h-5 w-5 text-nere-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span>{{ $announcement->attachment_name }}</span>
                        <span class="text-graphite-500">{{ number_format($announcement->attachment_size / 1024, 0, ',', ' ') }} Ko</span>
                    </a>
                @endif
            </div>

        </article>

        <!-- Back Button -->
        <div class="flex items-center justify-between">
            <a
                href="{{ route('intranet.announcements.index') }}"
                class="inline-flex items-center gap-2 text-graphite-600 hover:text-graphite-900 font-medium transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Retour aux annonces</span>
            </a>

            <button
                type="button"
                onclick="window.print()"
                class="p-2 rounded-lg border border-graphite-200 hover:bg-graphite-50 text-graphite-600 hover:text-graphite-900 transition"
                title="Imprimer"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
            </button>
        </div>

    </div>

</x-intranet-layout>
