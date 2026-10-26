{{-- Grille de tuiles d'indicateurs. Attend $kpis (liste) et facultativement $colonnes. --}}
<div class="grid grid-cols-2 gap-3 {{ $colonnes ?? 'md:grid-cols-3 xl:grid-cols-4' }}">
    @foreach($kpis as $kpi)
        @php
            $variation = $kpi['variation'] ?? null;
            $ton = match ($variation['favorable'] ?? null) {
                true => 'text-green-700 bg-green-50',
                false => 'text-nere-700 bg-nere-50',
                default => 'text-graphite-600 bg-graphite-100',
            };
        @endphp
        <div class="carte flex flex-col gap-1 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-graphite-500">{{ $kpi['label'] }}</p>
            <p class="text-2xl font-bold tabular-nums text-graphite-900">{{ $kpi['valeur'] }}</p>
            @if($variation)
                <p class="mt-auto">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $ton }}">
                        <span aria-hidden="true">{{ $variation['sens'] === 'hausse' ? '▲' : '▼' }}</span>
                        {{ $variation['sens'] === 'hausse' ? '+' : '−' }}{{ $variation['pourcentage'] }} %
                        <span class="sr-only">par rapport à la période précédente</span>
                    </span>
                    <span class="text-xs text-graphite-500" aria-hidden="true">vs période préc.</span>
                </p>
            @endif
        </div>
    @endforeach
</div>
