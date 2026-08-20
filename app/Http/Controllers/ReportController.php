<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Phase 11 : Rapports
    public function index(Request $request)
    {
        $periode = $request->query('periode', 'mois'); // mois, site, categorie, technicien

        $ticketsParMois = Ticket::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as periode, count(*) as total")
            ->groupBy('periode')->orderBy('periode')->get();

        $ticketsParSite = Ticket::join('sites', 'tickets.site_id', '=', 'sites.id')
            ->selectRaw('sites.nom as site, count(*) as total')
            ->groupBy('sites.nom')->get();

        $ticketsParCategorie = Ticket::join('ticket_categories', 'tickets.ticket_category_id', '=', 'ticket_categories.id')
            ->selectRaw('ticket_categories.nom as categorie, count(*) as total')
            ->groupBy('ticket_categories.nom')->get();

        $tempsMoyenResolution = Ticket::whereNotNull('date_resolution')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, date_resolution)) as moyenne_heures')
            ->value('moyenne_heures');

        $tauxSatisfaction = Ticket::whereNotNull('satisfaction_note')
            ->selectRaw('AVG(satisfaction_note) as moyenne, count(*) as total')
            ->first();

        $respectSla = [
            'dans_les_delais' => Ticket::whereNotNull('date_resolution')->where('sla_depasse', false)->count(),
            'sla_depasse' => Ticket::where('sla_depasse', true)->count(),
        ];

        $performanceTechniciens = Ticket::join('users', 'tickets.assigned_to', '=', 'users.id')
            ->selectRaw('users.name as technicien, count(*) as total_traites,
                AVG(TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.date_resolution)) as temps_moyen_heures')
            ->whereNotNull('tickets.date_resolution')
            ->groupBy('users.name')
            ->get();

        return view('reports.index', compact(
            'ticketsParMois', 'ticketsParSite', 'ticketsParCategorie',
            'tempsMoyenResolution', 'tauxSatisfaction', 'respectSla', 'performanceTechniciens'
        ));
    }

    // Export PDF / Excel / CSV (nécessite barryvdh/laravel-dompdf et maatwebsite/excel)
    public function exportPdf()
    {
        // $pdf = Pdf::loadView('reports.pdf', [...]);
        // return $pdf->download('rapport-tickets.pdf');
        abort(501, 'Installer barryvdh/laravel-dompdf pour activer cet export.');
    }

    public function exportExcel()
    {
        // return Excel::download(new TicketsExport, 'rapport-tickets.xlsx');
        abort(501, 'Installer maatwebsite/excel pour activer cet export.');
    }

    public function exportCsv()
    {
        $tickets = Ticket::with(['categorie', 'priorite', 'statut', 'site'])->get();

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Référence', 'Titre', 'Catégorie', 'Priorité', 'Statut', 'Site', 'Créé le']);
            foreach ($tickets as $t) {
                fputcsv($out, [
                    $t->reference, $t->titre, $t->categorie?->nom, $t->priorite?->nom,
                    $t->statut?->nom, $t->site?->nom, $t->created_at,
                ]);
            }
            fclose($out);
        }, 'rapport-tickets.csv');
    }
}
