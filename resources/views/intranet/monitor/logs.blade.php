@php
    $breadcrumbs = [['label' => 'Supervision réseau', 'url' => route('intranet.monitor.index')], ['label' => 'Journal']];
@endphp

<x-intranet-layout title="Journal de la supervision" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header title="Supervision réseau" description="Tous les changements d'état, pauses et prises en compte, du plus récent au plus ancien." />
    @include('intranet.monitor.partials.nav')

    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filtrer par état">
        <a href="{{ route('intranet.monitor.logs') }}" class="rounded-md px-3 py-1.5 text-sm font-medium {{ $filter ? 'text-graphite-700 hover:bg-graphite-100' : 'bg-nere-700 text-white' }}">Tout</a>
        @foreach(\App\Enums\MonitorStatus::cases() as $status)
            <a href="{{ route('intranet.monitor.logs', ['etat' => $status->value]) }}" class="rounded-md px-3 py-1.5 text-sm font-medium {{ $filter === $status ? 'bg-nere-700 text-white' : 'text-graphite-700 hover:bg-graphite-100' }}">{{ $status->label() }}</a>
        @endforeach
    </nav>

    <section class="carte overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-graphite-50 text-left text-xs uppercase tracking-wide text-graphite-500">
                <tr>
                    <th class="px-5 py-2">Date</th>
                    <th class="px-5 py-2">État</th>
                    <th class="px-5 py-2">Équipement · capteur</th>
                    <th class="px-5 py-2">Message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-graphite-100">
                @forelse($events as $event)
                    <tr>
                        <td class="whitespace-nowrap px-5 py-2.5 text-graphite-500">{{ $event->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-5 py-2.5">@if($event->to_status) @include('intranet.monitor.partials.badge', ['status' => $event->to_status]) @endif</td>
                        <td class="px-5 py-2.5">
                            <a href="{{ route('intranet.monitor.sensors.show', $event->sensor) }}" class="font-medium text-graphite-900 hover:text-nere-700">{{ $event->sensor->device->name }} · {{ $event->sensor->name }}</a>
                        </td>
                        <td class="px-5 py-2.5 text-graphite-700">{{ $event->message }}@if($event->user) · {{ $event->user->name }}@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-4 text-graphite-600">Aucun événement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <div class="mt-4">{{ $events->links() }}</div>
</x-intranet-layout>
