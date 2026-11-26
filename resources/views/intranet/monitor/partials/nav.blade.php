<nav class="mb-6 flex flex-wrap gap-1 border-b border-graphite-200" aria-label="Supervision réseau">
    @foreach([
        ['intranet.monitor.index', 'Vue d’ensemble', ['intranet.monitor.index', 'intranet.monitor.devices.*', 'intranet.monitor.sensors.*', 'intranet.monitor.groups.*']],
        ['intranet.monitor.maps', 'Cartes', ['intranet.monitor.maps*']],
        ['intranet.monitor.reports', 'Rapports', ['intranet.monitor.reports']],
        ['intranet.monitor.logs', 'Journal', ['intranet.monitor.logs']],
        ['intranet.monitor.discovery', 'Découverte automatique', ['intranet.monitor.discovery*']],
    ] as [$route, $label, $patterns])
        @php $active = request()->routeIs(...$patterns); @endphp
        <a href="{{ route($route) }}" @if($active) aria-current="page" @endif
           class="-mb-px border-b-2 px-4 py-2 text-sm font-medium {{ $active ? 'border-nere-700 text-nere-700' : 'border-transparent text-graphite-600 hover:text-graphite-900' }}">{{ $label }}</a>
    @endforeach
</nav>
