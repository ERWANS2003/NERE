<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes choix de menus</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1b2126; }
        h1 { font-size: 18px; color: #c83530; margin: 0 0 4px; }
        .meta { color: #555; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #c83530; color: #fff; text-align: left; padding: 6px 8px; }
        td { border-bottom: 1px solid #ddd; padding: 7px 8px; vertical-align: top; }
        .none { color: #888; font-style: italic; }
        .footer { margin-top: 18px; color: #888; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $campaign->title }}</h1>
    <p class="meta">
        {{ $registration->employee_name }} · {{ $registration->department }}<br>
        Choix enregistrés le {{ $registration->updated_at->format('d/m/Y à H:i') }}
    </p>
    <table>
        <thead>
            <tr><th style="width: 22%">Jour</th><th style="width: 12%">Menu</th><th>Plat</th><th style="width: 22%">Dessert</th></tr>
        </thead>
        <tbody>
            @foreach($campaign->days as $day)
                @php($menu = $registration->choices->firstWhere('menu_day_id', $day->id)?->selected_menu)
                <tr>
                    <td>{{ ucfirst($day->date->translatedFormat('l d F')) }}</td>
                    @if($menu)
                        <td>Menu {{ $menu }}</td>
                        <td>{{ $menu === 1 ? $day->menu_one : $day->menu_two }}</td>
                        <td>{{ $day->dessert }}</td>
                    @else
                        <td colspan="3" class="none">Pas de repas</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="footer">NÉRÉ Mining · Menu du personnel · document généré le {{ now()->format('d/m/Y à H:i') }}</p>
</body>
</html>
