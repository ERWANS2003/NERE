<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport Tickets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
        }
        .header .date {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        .summary {
            margin-bottom: 30px;
            background: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
        }
        .summary p {
            margin: 5px 0;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        th {
            background-color: #1f2937;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #333;
        }
        td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .status-open { color: #0066cc; }
        .status-closed { color: #22c55e; }
        .status-pending { color: #f59e0b; }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Rapport de Gestion des Tickets</h1>
        <div class="date">Généré le {{ $generatedAt }}</div>
    </div>

    <div class="summary">
        <p><strong>Total de tickets :</strong> {{ $totalTickets }}</p>
        <p><strong>Tickets ouverts :</strong> {{ $tickets->where('statut.slug', '!=', 'ferme')->count() }}</p>
        <p><strong>Tickets résolus :</strong> {{ $tickets->where('statut.slug', 'resolu')->count() }}</p>
        <p><strong>Tickets fermés :</strong> {{ $tickets->where('statut.slug', 'ferme')->count() }}</p>
    </div>

    <h2 style="font-size: 16px; color: #1f2937; margin-top: 30px; margin-bottom: 15px;">Détail des Tickets</h2>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Site</th>
                <th>Assigné à</th>
                <th>Créé le</th>
                <th>Résolu le</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td style="font-weight: bold;">{{ $ticket->reference }}</td>
                <td>{{ Str::limit($ticket->titre, 40) }}</td>
                <td>{{ $ticket->categorie?->nom ?? '—' }}</td>
                <td>{{ $ticket->priorite?->nom ?? '—' }}</td>
                <td class="status-{{ $ticket->statut?->slug }}">{{ $ticket->statut?->nom ?? '—' }}</td>
                <td>{{ $ticket->site?->nom ?? '—' }}</td>
                <td>{{ $ticket->assignedTo?->name ?? '—' }}</td>
                <td>{{ $ticket->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $ticket->date_resolution?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Document généré automatiquement par le système ITSM Nere Mining</p>
        <p>Cet export contient des données confidentielles - À ne pas partager</p>
    </div>
</body>
</html>
