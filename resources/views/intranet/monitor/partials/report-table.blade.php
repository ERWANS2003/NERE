<section class="carte overflow-hidden">
    <h2 class="border-b border-graphite-100 px-5 py-3 font-semibold text-graphite-900">{{ $title }}</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-graphite-50 text-left text-xs uppercase tracking-wide text-graphite-500">
                <tr>
                    <th class="px-4 py-2">Équipement</th>
                    <th class="px-4 py-2">Capteur</th>
                    <th class="px-4 py-2 text-right">Disponibilité</th>
                    <th class="px-4 py-2 text-right">Pannes</th>
                    <th class="px-4 py-2 text-right">Durée de panne</th>
                    <th class="px-4 py-2 text-right">Moyenne</th>
                    <th class="px-4 py-2 text-right">Maximum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-graphite-100">
                @forelse($rows as $row)
                    @php $s = $row['sensor']; @endphp
                    <tr>
                        <td class="px-4 py-2">{{ $s->device->name }}<span class="block text-xs text-graphite-500">{{ $s->device->group->name }} · {{ $s->device->host }}</span></td>
                        <td class="px-4 py-2">{{ $s->name }}</td>
                        <td class="px-4 py-2 text-right font-semibold {{ $row['uptime'] !== null && $row['uptime'] < 99 ? 'text-red-700' : 'text-graphite-900' }}">{{ $row['uptime'] === null ? '-' : number_format($row['uptime'], 2, ',', ' ').' %' }}</td>
                        <td class="px-4 py-2 text-right">{{ $row['outages'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $row['downtime_minutes'] ? \App\Services\Monitor\SensorChecks::duration($row['downtime_minutes'] * 60) : '-' }}</td>
                        <td class="px-4 py-2 text-right">{{ $row['avg'] === null ? '-' : $s->formatValue($row['avg']) }}</td>
                        <td class="px-4 py-2 text-right">{{ $row['max'] === null ? '-' : $s->formatValue($row['max']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-4 text-graphite-600">Aucun capteur dans ce périmètre.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
