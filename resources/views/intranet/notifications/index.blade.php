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
    @php
        $notificationsConfig = [
            'total' => $notifications->total(),
            'csrf' => csrf_token(),
            'destroyUrl' => route('intranet.notifications.destroy', '__ID__'),
            'destroyAllUrl' => route('intranet.notifications.destroy-all'),
            'indexUrl' => route('intranet.notifications.index'),
        ];
    @endphp
<div x-data="notificationsList(@js($notificationsConfig))">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-graphite-900 mb-2">Notifications</h1>
            <p class="text-graphite-600">Toutes vos notifications, lues et non lues</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('intranet.notifications.mark-all-read') }}" x-show="!vide">
                    @csrf
                    <button type="submit" class="bouton-secondaire">Tout marquer comme lu</button>
                </form>
            @endif
            <form method="POST" action="{{ route('intranet.notifications.destroy-all') }}" x-show="!vide && !confirmation" @submit.prevent="confirmation = true" @if($notifications->total() === 0) style="display: none;" @endif>
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Tout supprimer
                </button>
            </form>
        </div>
    </div>

    {{-- Confirmation avant de tout supprimer. --}}
    <div x-show="confirmation" x-cloak x-transition.opacity.duration.200ms
         class="mb-6 flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between" role="alertdialog" aria-labelledby="confirmation-suppression">
        <p id="confirmation-suppression" class="text-sm text-red-900">
            Supprimer définitivement <strong x-text="total + ' notification' + (total > 1 ? 's' : '')"></strong>, lues et non lues ? Cette action est irréversible.
        </p>
        <div class="flex shrink-0 gap-2">
            <button type="button" @click="confirmation = false" class="rounded-lg border border-graphite-300 bg-white px-4 py-2 text-sm font-semibold text-graphite-700 hover:bg-graphite-50">Annuler</button>
            <button type="button" @click="toutSupprimer()" :disabled="enCours" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-60" x-text="enCours ? 'Suppression…' : 'Oui, tout supprimer'"></button>
        </div>
    </div>

    <p x-show="erreur" x-cloak x-text="erreur" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"></p>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(config('webpush.enabled'))
        <section
            class="mb-6 rounded-lg border border-graphite-200 bg-white p-5"
            aria-labelledby="push-notifications-heading"
            data-tour="notifications-push"
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

    <div class="bg-white rounded-lg border border-graphite-200 overflow-hidden" data-tour="notifications-liste" x-ref="liste" x-show="!vide">
        @forelse($notifications as $notification)
            @php $d = $notification->data; $nonLue = is_null($notification->read_at); @endphp
            <div class="group relative border-b border-graphite-100 last:border-0" data-notification="{{ $notification->id }}">
            <form method="POST" action="{{ route('intranet.notifications.mark-read', $notification->id) }}">
                @csrf
                <button type="submit" class="w-full text-left py-4 pl-6 pr-16 hover:bg-graphite-50 transition flex items-start gap-4 {{ $nonLue ? 'bg-nere-50/40' : '' }}">
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
            <form method="POST" action="{{ route('intranet.notifications.destroy', $notification->id) }}" class="absolute right-3 top-1/2 -translate-y-1/2" @submit.prevent="supprimer(@js($notification->id))">
                @csrf
                @method('DELETE')
                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full text-graphite-400 transition hover:bg-red-50 hover:text-red-700 focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
                        aria-label="Supprimer la notification « {{ $d['title'] ?? 'Notification' }} »" title="Supprimer">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
            </div>
        @empty
        @endforelse
    </div>

    <div x-show="vide" x-cloak class="rounded-lg border border-graphite-200 bg-white p-12 text-center" @if($notifications->total() === 0) style="display: block;" @endif>
        <span x-ref="cloche" class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-50 text-green-600">
            <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icones['bell'] }}"/></svg>
        </span>
        <p class="font-semibold text-graphite-900">Tout est propre</p>
        <p class="mt-1 text-sm text-graphite-600">Aucune notification. Les nouvelles apparaîtront ici et dans la cloche.</p>
    </div>

    @if($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif

</div>
</x-intranet-layout>
