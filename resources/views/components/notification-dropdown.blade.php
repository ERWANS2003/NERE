@php
    // Lecture unique : le compteur et la liste partagent la même requête.
    $utilisateur = auth()->user();
    $nonLues = $utilisateur->unreadNotifications()->latest()->take(8)->get();
    $nbNonLues = $utilisateur->unreadNotifications()->count();

    // Icônes disponibles (clé « icon » du format normalisé des notifications).
    $icones = [
        'megaphone' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        'check' => 'M5 13l4 4L19 7',
        'x' => 'M6 18L18 6M6 6l12 12',
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'bell' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    ];
@endphp

<div class="relative" x-data="{ open: false }">
    <button
        @click="open = !open"
        @click.away="open = false"
        class="relative p-2 rounded-lg hover:bg-graphite-100 text-graphite-600 transition"
        aria-label="Notifications"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icones['bell'] }}"/>
        </svg>
        @if($nbNonLues > 0)
            <span class="absolute top-1 right-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-nere-600 px-1 text-[10px] font-bold text-white">
                {{ $nbNonLues > 9 ? '9+' : $nbNonLues }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="absolute right-0 mt-2 w-80 bg-white border border-graphite-200 rounded-lg shadow-lg z-50"
        style="display: none;"
    >
        <div class="flex items-center justify-between px-4 py-2 border-b border-graphite-200">
            <span class="text-sm font-semibold text-graphite-900">Notifications</span>
            @if($nbNonLues > 0)
                <form method="POST" action="{{ route('intranet.notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="text-xs text-nere-600 hover:text-nere-700 font-medium">
                        Tout marquer lu
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse($nonLues as $notification)
                @php $d = $notification->data; @endphp
                <form method="POST" action="{{ route('intranet.notifications.mark-read', $notification->id) }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-3 hover:bg-graphite-50 transition border-b border-graphite-100 last:border-0 flex items-start gap-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-nere-50 text-nere-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icones[$d['icon'] ?? 'bell'] ?? $icones['bell'] }}"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium text-graphite-900 truncate">{{ $d['title'] ?? 'Notification' }}</span>
                            @if(!empty($d['message']))
                                <span class="block text-xs text-graphite-500 truncate">{{ $d['message'] }}</span>
                            @endif
                            <span class="block text-[11px] text-graphite-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <p class="px-4 py-6 text-sm text-graphite-500 text-center">Aucune nouvelle notification</p>
            @endforelse
        </div>

        <a href="{{ route('intranet.notifications.index') }}" class="block px-4 py-2.5 text-center text-sm font-medium text-nere-600 hover:bg-graphite-50 border-t border-graphite-200">
            Voir toutes les notifications
        </a>
    </div>
</div>
