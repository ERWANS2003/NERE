<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 0 0 12px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; text-align: left; padding: 5px; font-size: 9px; text-transform: uppercase; }
        td { padding: 5px; border-bottom: 1px solid #e5e7eb; }
        .r { text-align: right; }
        .bad { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>Supervision réseau NÉRÉ Mining · rapport généré le {{ now()->format('d/m/Y à H:i') }}</p>
    <table>
        <thead>
            <tr><th>Groupe</th><th>Équipement</th><th>Capteur</th><th class="r">Disponibilité</th><th class="r">Pannes</th><th class="r">Durée de panne</th><th class="r">Moyenne</th><th class="r">Maximum</th></tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php $s = $row['sensor']; @endphp
                <tr>
                    <td>{{ $s->device->group->name }}</td>
                    <td>{{ $s->device->name }} ({{ $s->device->host }})</td>
                    <td>{{ $s->name }}</td>
                    <td class="r {{ $row['uptime'] !== null && $row['uptime'] < 99 ? 'bad' : '' }}">{{ $row['uptime'] === null ? '-' : number_format($row['uptime'], 2, ',', ' ').' %' }}</td>
                    <td class="r">{{ $row['outages'] }}</td>
                    <td class="r">{{ $row['downtime_minutes'] ? \App\Services\Monitor\SensorChecks::duration($row['downtime_minutes'] * 60) : '-' }}</td>
                    <td class="r">{{ $row['avg'] === null ? '-' : $s->formatValue($row['avg']) }}</td>
                    <td class="r">{{ $row['max'] === null ? '-' : $s->formatValue($row['max']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
