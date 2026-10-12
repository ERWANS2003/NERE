@php
    $breadcrumbs = [
        ['label' => 'Ressources', 'url' => route('intranet.resources')],
        ['label' => $resource->title],
    ];
@endphp

<x-intranet-layout :title="$resource->title" :breadcrumbs="$breadcrumbs">

    <div class="max-w-3xl mx-auto">
        <article class="bg-white rounded-xl border border-graphite-200 overflow-hidden">

            <div class="p-6 border-b border-graphite-100">
                <div class="flex items-center gap-3 mb-3 text-sm">
                    <span class="px-2 py-0.5 rounded bg-graphite-100 text-graphite-700 font-semibold uppercase text-xs">
                        {{ $resource->file_type }}
                    </span>
                    @if($resource->category)
                        <span class="text-graphite-500">{{ $resource->category->name }}</span>
                    @endif
                </div>
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-2xl font-bold text-graphite-900">{{ $resource->title }}</h1>

                    {{-- Favori (étoile) --}}
                    <form method="POST" action="{{ route('intranet.resources.favorite', $resource) }}" class="flex-shrink-0">
                        @csrf
                        @php $estFavori = $resource->favoritedBy()->whereKey(auth()->id())->exists(); @endphp
                        <button type="submit" class="transition {{ $estFavori ? 'text-or-500' : 'text-graphite-300 hover:text-or-500' }}"
                                title="{{ $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
                                aria-label="{{ $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                            <svg class="w-6 h-6" fill="{{ $estFavori ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </button>
                    </form>
                </div>
                @if($resource->description)
                    <p class="mt-2 text-graphite-600">{{ $resource->description }}</p>
                @endif
            </div>

            <div class="p-6 space-y-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @if($resource->department)
                        <div>
                            <dt class="text-graphite-500">Département</dt>
                            <dd class="text-graphite-900 font-medium">{{ $resource->department->name }}</dd>
                        </div>
                    @endif
                    @if($resource->file_size)
                        <div>
                            <dt class="text-graphite-500">Taille</dt>
                            <dd class="text-graphite-900 font-medium">{{ $resource->file_size }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-graphite-500">Téléchargements</dt>
                        <dd class="text-graphite-900 font-medium">{{ $resource->download_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-graphite-500">Ajouté par</dt>
                        <dd class="text-graphite-900 font-medium">{{ $resource->uploader?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-graphite-500">Date d'ajout</dt>
                        <dd class="text-graphite-900 font-medium">{{ $resource->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                @if(is_array($resource->tags) && count($resource->tags) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($resource->tags as $tag)
                            <span class="px-2 py-1 rounded bg-graphite-100 text-graphite-600 text-xs">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    @if($available)
                        <a href="{{ route('intranet.resources.download', $resource) }}" class="bouton-primaire">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Télécharger
                        </a>
                    @else
                        <p class="rounded-lg bg-or-50 border border-or-200 px-4 py-3 text-sm text-or-800" role="status">
                            Le fichier n'est pas encore disponible en téléchargement.
                        </p>
                    @endif

                    @can('delete', $resource)
                        <form
                            method="POST"
                            action="{{ route('intranet.resources.destroy', $resource) }}"
                            onsubmit="return confirm('Supprimer définitivement cette ressource ?');"
                        >
                            @csrf
                            @method('DELETE')
                            <x-bouton-danger>Supprimer</x-bouton-danger>
                        </form>
                    @endcan
                </div>
            </div>
        </article>

        <div class="mt-6">
            <a href="{{ route('intranet.resources') }}"
               class="inline-flex items-center gap-2 text-graphite-600 hover:text-graphite-900 font-medium transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour aux ressources
            </a>
        </div>
    </div>

</x-intranet-layout>
