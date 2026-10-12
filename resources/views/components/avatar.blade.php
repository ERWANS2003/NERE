@props([
    'user' => null,
    'src' => null,
    'name' => null,
    'size' => 'md', // xs, sm, md, lg, xl
    'status' => null, // online, offline, busy, away
    'showStatus' => false,
])

@php
    // Les tailles et les couleurs viennent de public/css/nere-design-system.css
    // (.nere-avatar-*). « md » est la taille de base (40 px), « xs » réutilise « sm ».
    $sizeClasses = [
        'xs' => 'nere-avatar-sm !w-6 !h-6 !text-[0.625rem]',
        'sm' => 'nere-avatar-sm',
        'md' => '',
        'lg' => 'nere-avatar-lg',
        'xl' => 'nere-avatar-xl',
    ];
    $sizeClass = $sizeClasses[$size] ?? '';

    if ($user) {
        $imageSrc = $user->avatar_url ?? null;
        $displayName = $user->name ?? '';
    } else {
        $imageSrc = $src;
        $displayName = $name ?? '';
    }

    $words = preg_split('/\s+/', trim((string) $displayName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = $words ? mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : '')) : '';

    // Couleur stable par personne : même nom => même couleur sur toutes les pages.
    $palette = ['red', 'yellow', 'blue', 'green', 'purple', 'pink'];
    $colorClass = 'nere-avatar-' . $palette[crc32(mb_strtolower((string) $displayName)) % count($palette)];
@endphp

<div {{ $attributes->merge(['class' => trim("nere-avatar relative shrink-0 {$colorClass} {$sizeClass}")]) }}
     @if($displayName) title="{{ $displayName }}" @endif>
    @if($imageSrc)
        <img src="{{ $imageSrc }}" alt="{{ $displayName }}" loading="lazy" decoding="async" class="h-full w-full rounded-full object-cover">
    @else
        <span aria-hidden="true">{{ $initials ?: '?' }}</span>
        @if($displayName)<span class="sr-only">{{ $displayName }}</span>@endif
    @endif

    @if($showStatus && $status)
        @php
            $statusColor = ['online' => 'bg-green-500', 'busy' => 'bg-red-500', 'away' => 'bg-yellow-400'][$status] ?? 'bg-graphite-400';
        @endphp
        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full ring-2 ring-white {{ $statusColor }}"></span>
    @endif
</div>
