@php
    // Graphique SVG : temps de réponse moyen par tranche, barres rouges pour les tranches en échec.
    $height = $height ?? 220;
    $width = 1000;
    $points = $points ?? [];
    $count = count($points);
    $max = max(1, collect($points)->max('value') ?? 1);
    $x = fn (int $i) => $count > 1 ? round($i * $width / ($count - 1), 1) : $width / 2;
    $y = fn (float $v) => round($height - 4 - (max(0, $v) / $max) * ($height - 12), 1);
    $segments = [];
    $current = [];
    foreach ($points as $i => $point) {
        if ($point['value'] === null) {
            $current && $segments[] = $current;
            $current = [];
            continue;
        }
        $current[] = $x($i).','.$y($point['value']);
    }
    $current && $segments[] = $current;
    $barWidth = $count > 0 ? max(2, $width / $count) : 0;
@endphp
<figure>
    <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="block w-full" style="height: {{ $height }}px" role="img"
         aria-label="{{ $label ?? 'Graphique' }} : maximum {{ $max }} {{ $unit ?? '' }}">
        @unless($compact ?? false)
            @foreach([0.25, 0.5, 0.75] as $line)
                <line x1="0" x2="{{ $width }}" y1="{{ round($height * $line) }}" y2="{{ round($height * $line) }}" stroke="currentColor" class="text-graphite-200" stroke-width="1" vector-effect="non-scaling-stroke" />
            @endforeach
        @endunless
        @foreach($points as $i => $point)
            @if($point['failed'])
                <rect x="{{ max(0, $x($i) - $barWidth / 2) }}" y="0" width="{{ $barWidth }}" height="{{ $height }}" class="fill-red-500/30" />
            @endif
        @endforeach
        @foreach($segments as $segment)
            @if(count($segment) === 1)
                @php [$cx, $cy] = explode(',', $segment[0]); @endphp
                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3" class="fill-nere-700" />
            @endif
            <polyline points="{{ implode(' ', $segment) }}" fill="none" stroke="currentColor" class="text-nere-700" stroke-width="{{ ($compact ?? false) ? 1.5 : 2 }}" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
        @endforeach
    </svg>
    @unless($compact ?? false)
        <figcaption class="mt-1 flex justify-between text-xs text-graphite-500">
            <span>{{ $count ? $points[0]['at']->format('d/m H:i') : '' }}</span>
            <span>Échelle : 0 à {{ round($max, 1) }} {{ $unit ?? '' }} · zones rouges = échecs</span>
            <span>{{ $count ? $points[$count - 1]['at']->format('d/m H:i') : '' }}</span>
        </figcaption>
    @endunless
</figure>
