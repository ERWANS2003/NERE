<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport analytique - {{ $periodes[$jours] }}</title>
    <style>
        body { color: #263238; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { color: #9b2926; font-size: 20px; margin-bottom: 4px; }
        h2 { border-bottom: 1px solid #d5dadd; font-size: 14px; margin: 22px 0 8px; padding-bottom: 5px; }
        p { margin: 4px 0; }
        table { border-collapse: collapse; margin-top: 8px; width: 100%; }
        th, td { border: 1px solid #d5dadd; padding: 6px; text-align: left; }
        th { background: #f1f3f4; }
        .muted { color: #637078; }
        .kpis td { width: 25%; }
    </style>
</head>
<body>
    <h1>Rapport analytique</h1>
    <p>{{ $scopeLabel }}</p>
    <p class="muted">Période : {{ $periodes[$jours] }} · Généré le {{ now()->format('d/m/Y à H:i') }}</p>

    @if($isAdmin && $overview)
        <h2>Vue d’ensemble de l’entreprise</h2>
        <table>
            <thead>
                <tr><th>Départements actifs</th><th>Employés actifs</th><th>Demandes enregistrées</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($overview['departements'], 0, ',', ' ') }}</td>
                    <td>{{ number_format($overview['effectif'], 0, ',', ' ') }}</td>
                    <td>{{ number_format($overview['demandes'], 0, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <h2>Indicateurs sur {{ $periodes[$jours] }}</h2>
    <table class="kpis">
        <thead>
            <tr>
                @foreach($kpis as $kpi)
                    <th>{{ $kpi['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach($kpis as $kpi)
                    <td>{{ $kpi['valeur'] }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    @foreach([
        ['title' => 'Demandes par semaine', 'data' => $charts['parSemaine']],
        ['title' => 'Répartition par statut', 'data' => $charts['parStatut']],
        ['title' => $isAdmin ? 'Demandes par département' : 'Demandes dans vos départements', 'data' => $charts['parDepartement']],
        ['title' => $isAdmin ? 'Effectifs par département' : 'Effectifs de vos départements', 'data' => $charts['effectifs']],
    ] as $chart)
        <h2>{{ $chart['title'] }}</h2>
        <table>
            <thead>
                <tr><th>Élément</th><th>Nombre</th></tr>
            </thead>
            <tbody>
                @forelse($chart['data']['labels'] as $index => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $chart['data']['data'][$index] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Aucune donnée pour cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    @if(! empty($sections['synthese']['alertes']))
        <h2>Points d’attention</h2>
        <table>
            <tbody>
                @foreach($sections['synthese']['alertes'] as $alerte)
                    <tr><td>{{ $alerte['texte'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @foreach(['demandes' => 'Demandes', 'support' => 'Support IT', 'conges' => 'Congés', 'contenus' => 'Contenus et adoption', 'effectifs' => 'Effectifs'] as $cle => $titre)
        @isset($sections[$cle])
            <h2>Indicateurs détaillés · {{ $titre }}</h2>
            <table>
                <thead>
                    <tr><th>Indicateur</th><th>Valeur</th><th>Évolution</th></tr>
                </thead>
                <tbody>
                    @foreach($sections[$cle]['kpis'] as $kpi)
                        <tr>
                            <td>{{ $kpi['label'] }}</td>
                            <td>{{ $kpi['valeur'] }}</td>
                            <td>{{ $kpi['variation'] ? ($kpi['variation']['sens'] === 'hausse' ? '+' : '−').$kpi['variation']['pourcentage'].' %' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endisset
    @endforeach
</body>
</html>
