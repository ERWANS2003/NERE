{{-- Écran squelette affiché pendant le chargement de la page suivante (voir resources/js/page-skeleton.js). --}}
<div class="page-skeleton" aria-hidden="true">
    <div class="page-skeleton-block h-4 w-40"></div>
    <div class="page-skeleton-block mt-4 h-8 w-80 max-w-full"></div>
    <div class="page-skeleton-block mt-3 h-4 w-96 max-w-full"></div>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(range(1, 4) as $card)
            <div class="page-skeleton-card flex items-center gap-4">
                <div class="page-skeleton-block h-10 w-10 rounded-xl"></div>
                <div class="flex-1 space-y-2">
                    <div class="page-skeleton-block h-4 w-3/4"></div>
                    <div class="page-skeleton-block h-3 w-1/2"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="page-skeleton-card mt-6 space-y-4">
        @foreach(range(1, 6) as $row)
            <div class="flex items-center gap-4">
                <div class="page-skeleton-block h-9 w-9 rounded-full"></div>
                <div class="page-skeleton-block h-4 min-w-0 flex-1"></div>
                <div class="page-skeleton-block hidden h-4 w-24 sm:block"></div>
            </div>
        @endforeach
    </div>
    <span class="sr-only">Chargement de la page…</span>
</div>
