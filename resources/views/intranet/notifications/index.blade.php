@php
    $breadcrumbs = [
        ['label' => 'Notifications'],
    ];
    $icones = [
        'megaphone' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        'check' => 'M5 13l4 4L19 7',
        'x' => 'M6 18L18 6M6 6l12 12',
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'bell' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    ];
@endphp

<x-intranet-layout title="Notifications" :breadcrumbs="$breadcrumbs">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Notifications</h1>
            <p class="text-graphite-600">Toutes vos notifications, lues et non lues</p>
        </div>

        @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('intranet.notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="bouton-secondaire">Tout marquer comme lu</button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(config('webpush.enabled'))
        <section
            class="mb-6 rounded-lg border border-graphite-200 bg-white p-5"
            aria-labelledby="push-notifications-heading"
            data-web-push
            data-public-key-url="{{ route('intranet.notifications.push.public-key') }}"
            data-subscribe-url="{{ route('intranet.notifications.push.subscriptions.store') }}"
            data-unsubscribe-url="{{ route('intranet.notifications.push.subscriptions.destroy') }}"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="push-notifications-heading" class="font-semibold text-graphite-900">Notifications navigateur</h2>
                    <p class="mt-1 text-sm text-graphite-600">Recevez les alertes de l’intranet en notification Windows sur cet appareil, même lorsque l’onglet est fermé. Les alertes urgentes restent affichées jusqu’à ce que vous cliquiez dessus.</p>
                </div>
                <button type="button" class="bouton-secondaire" data-web-push-toggle disabled aria-pressed="false">
                    Activer les notifications push
                </button>
            </div>
            <p class="mt-3 text-sm text-graphite-500" role="status" aria-live="polite" data-web-push-status>
                Préparation des notifications push…
            </p>
        </section>
    @else
        <p class="mb-6 rounded-lg border border-graphite-200 bg-graphite-50 px-4 py-3 text-sm text-graphite-600">
            Les notifications navigateur ne sont pas encore configurées par l’administrateur.
        </p>
    @endif

    <div class="bg-white rounded-lg border border-graphite-200 overflow-hidden">
        @forelse($notifications as $notification)
            @php $d = $notification->data; $nonLue = is_null($notification->read_at); @endphp
            <form method="POST" action="{{ route('intranet.notifications.mark-read', $notification->id) }}">
                @csrf
                <button type="submit" class="w-full text-left px-6 py-4 hover:bg-graphite-50 transition border-b border-graphite-100 last:border-0 flex items-start gap-4 {{ $nonLue ? 'bg-nere-50/40' : '' }}">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-nere-50 text-nere-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icones[$d['icon'] ?? 'bell'] ?? $icones['bell'] }}"/>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="text-sm font-medium text-graphite-900">{{ $d['title'] ?? 'Notification' }}</span>
                            @if($nonLue)
                                <span class="inline-block h-2 w-2 rounded-full bg-nere-600" title="Non lue"></span>
                            @endif
                        </span>
                        @if(!empty($d['message']))
                            <span class="block text-sm text-graphite-500 mt-0.5">{{ $d['message'] }}</span>
                        @endif
                        <span class="block text-xs text-graphite-400 mt-1">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            </form>
        @empty
            <div class="p-12 text-center">
                <svg class="w-16 h-16 text-graphite-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icones['bell'] }}"/>
                </svg>
                <p class="text-graphite-600">Aucune notification</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif

</x-intranet-layout>
