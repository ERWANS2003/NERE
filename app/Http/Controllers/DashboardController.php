<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Phase 7 : Tableau de bord (données consommées par Chart.js côté vue)
    public function index()
    {
        $stats = [
            'total_tickets' => Ticket::count(),
            'tickets_ouverts' => Ticket::ouverts()->count(),
            'tickets_critiques' => Ticket::critiques()->ouverts()->count(),
            'tickets_sla_depasse' => Ticket::where('sla_depasse', true)->count(),
            'techniciens_disponibles' => User::where('est_technicien', true)->where('disponible', true)->count(),
        ];

        $ticketsParStatut = Ticket::query()
            ->join('ticket_statuses', 'tickets.ticket_status_id', '=', 'ticket_statuses.id')
            ->select('ticket_statuses.nom', DB::raw('count(*) as total'))
            ->groupBy('ticket_statuses.nom')
            ->pluck('total', 'nom');

        $ticketsParPriorite = Ticket::query()
            ->join('ticket_priorities', 'tickets.ticket_priority_id', '=', 'ticket_priorities.id')
            ->select('ticket_priorities.nom', DB::raw('count(*) as total'))
            ->groupBy('ticket_priorities.nom')
            ->pluck('total', 'nom');

        $ticketsParSite = Ticket::query()
            ->join('sites', 'tickets.site_id', '=', 'sites.id')
            ->select('sites.nom', DB::raw('count(*) as total'))
            ->groupBy('sites.nom')
            ->pluck('total', 'nom');

        $evolutionMensuelle = Ticket::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, count(*) as total")
            ->groupBy('mois')
            ->orderBy('mois')
            ->limit(12)
            ->pluck('total', 'mois');

        return view('dashboard.index', compact(
            'stats', 'ticketsParStatut', 'ticketsParPriorite', 'ticketsParSite', 'evolutionMensuelle'
        ));
    }
}
