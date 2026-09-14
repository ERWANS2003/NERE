<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Exports\TicketsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // Phase 11 : Rapports
    public function index(Request $request)
    {
        $driver = DB::connection()->getDriverName();
        $dateFormatExpr = match ($driver) {
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
        $diffHourExpr = match ($driver) {
            'pgsql' => "EXTRACT(EPOCH FROM (date_resolution - created_at)) / 3600",
            'sqlite' => "((julianday(date_resolution) - julianday(created_at)) * 24)",
            default => "TIMESTAMPDIFF(HOUR, created_at, date_resolution)",
        };
        $diffHourTicketExpr = match ($driver) {
            'pgsql' => "EXTRACT(EPOCH FROM (tickets.date_resolution - tickets.created_at)) / 3600",
            'sqlite' => "((julianday(tickets.date_resolution) - julianday(tickets.created_at)) * 24)",
            default => "TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.date_resolution)",
        };

        $ticketsParMois = Ticket::selectRaw("{$dateFormatExpr} as periode, count(*) as total")
            ->groupBy('periode')->orderBy('periode')->get();

        $ticketsParSite = Ticket::join('sites', 'tickets.site_id', '=', 'sites.id')
            ->selectRaw('sites.nom as site, count(*) as total')
            ->groupBy('sites.nom')->get();

        $ticketsParCategorie = Ticket::join('ticket_categories', 'tickets.ticket_category_id', '=', 'ticket_categories.id')
            ->selectRaw('ticket_categories.nom as categorie, count(*) as total')
            ->groupBy('ticket_categories.nom')->get();

        $tempsMoyenResolution = Ticket::whereNotNull('date_resolution')
            ->selectRaw("AVG({$diffHourExpr}) as moyenne_heures")
            ->value('moyenne_heures');

        $tauxSatisfaction = Ticket::whereNotNull('satisfaction_note')
            ->selectRaw('AVG(satisfaction_note) as moyenne, count(*) as total')
            ->first();

        $respectSla = [
            'dans_les_delais' => Ticket::whereNotNull('date_resolution')->where('sla_depasse', false)->count(),
            'sla_depasse' => Ticket::where('sla_depasse', true)->count(),
        ];

        $performanceTechniciens = Ticket::join('users', 'tickets.assigned_to', '=', 'users.id')
            ->selectRaw("users.name as technicien, count(*) as total_traites, AVG({$diffHourTicketExpr}) as temps_moyen_heures")
            ->whereNotNull('tickets.date_resolution')
            ->groupBy('users.name')
            ->get();

        $historiques = TicketHistory::with(['ticket', 'utilisateur'])
            ->latest()
            ->limit(50)
            ->get();

        return view('reports.dashboard', compact(
            'ticketsParMois',
            'ticketsParSite',
            'ticketsParCategorie',
            'tempsMoyenResolution',
            'tauxSatisfaction',
            'respectSla',
            'performanceTechniciens',
            'historiques'
        ));
    }

    public function exportPdf()
    {
        $tickets = Ticket::with(['categorie', 'priorite', 'statut', 'site', 'assigned_to'])->get();

        $data = [
            'tickets' => $tickets,
            'generatedAt' => now()->format('d M Y H:i'),
            'totalTickets' => $tickets->count(),
        ];

        $pdf = \PDF::loadView('reports.pdf-export', $data);
        return $pdf->download('rapport-tickets-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TicketsExport(),
            'rapport-tickets-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportCsv()
    {
        $tickets = Ticket::with(['categorie', 'priorite', 'statut', 'site'])->get();

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Référence', 'Titre', 'Catégorie', 'Priorité', 'Statut', 'Site', 'Créé le']);
            foreach ($tickets as $t) {
                fputcsv($out, [
                    $t->reference,
                    $t->titre,
                    $t->categorie?->nom,
                    $t->priorite?->nom,
                    $t->statut?->nom,
                    $t->site?->nom,
                    $t->created_at,
                ]);
            }
            fclose($out);
        }, 'rapport-tickets.csv');
    }

    /**
     * Get advanced analytics data for dashboard
     */
    public function analytics(Request $request)
    {
        $period = $request->query('period', '30'); // days
        $startDate = now()->subDays($period);

        // Trend data (tickets created over time)
        $driver = DB::connection()->getDriverName();
        $dateFormatExpr = match ($driver) {
            'pgsql' => "DATE_TRUNC('day', created_at)::date",
            'sqlite' => "date(created_at)",
            default => "DATE(created_at)",
        };

        $trendData = Ticket::selectRaw("{$dateFormatExpr} as date, count(*) as count")
            ->where('created_at', '>=', $startDate)
            ->groupByRaw($dateFormatExpr)
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });

        // Priority distribution
        $priorityDistribution = Ticket::where('created_at', '>=', $startDate)
            ->join('ticket_priorities', 'tickets.priorite_id', '=', 'ticket_priorities.id')
            ->groupBy('ticket_priorities.nom')
            ->selectRaw('ticket_priorities.nom as label, count(*) as value')
            ->get();

        // Status distribution
        $statusDistribution = Ticket::where('created_at', '>=', $startDate)
            ->join('ticket_statuses', 'tickets.status_id', '=', 'ticket_statuses.id')
            ->groupBy('ticket_statuses.nom')
            ->selectRaw('ticket_statuses.nom as label, count(*) as value')
            ->get();

        // Category distribution
        $categoryDistribution = Ticket::where('created_at', '>=', $startDate)
            ->join('ticket_categories', 'tickets.ticket_category_id', '=', 'ticket_categories.id')
            ->groupBy('ticket_categories.nom')
            ->selectRaw('ticket_categories.nom as label, count(*) as value')
            ->get();

        // Resolution time trend
        $diffHourExpr = match ($driver) {
            'pgsql' => "EXTRACT(EPOCH FROM (date_resolution - created_at)) / 3600",
            'sqlite' => "((julianday(date_resolution) - julianday(created_at)) * 24)",
            default => "TIMESTAMPDIFF(HOUR, created_at, date_resolution)",
        };

        $resolutionTimeTrend = Ticket::selectRaw("{$dateFormatExpr} as date, AVG({$diffHourExpr}) as avg_hours, COUNT(*) as count")
            ->whereNotNull('date_resolution')
            ->where('created_at', '>=', $startDate)
            ->groupByRaw($dateFormatExpr)
            ->orderBy('date')
            ->get();

        // SLA compliance rate
        $totalTickets = Ticket::where('created_at', '>=', $startDate)->count();
        $slaCompliant = Ticket::where('created_at', '>=', $startDate)->where('sla_depasse', false)->count();
        $slaComplianceRate = $totalTickets > 0 ? round(($slaCompliant / $totalTickets) * 100, 1) : 0;

        return response()->json([
            'trend' => $trendData,
            'priority' => $priorityDistribution,
            'status' => $statusDistribution,
            'category' => $categoryDistribution,
            'resolutionTime' => $resolutionTimeTrend,
            'slaCompliance' => $slaComplianceRate,
            'period' => $period,
        ]);
    }

    /**
     * Get team performance analytics
     */
    public function teamPerformance(Request $request)
    {
        $period = $request->query('period', '30');
        $startDate = now()->subDays($period);

        $driver = DB::connection()->getDriverName();
        $diffHourExpr = match ($driver) {
            'pgsql' => "EXTRACT(EPOCH FROM (tickets.date_resolution - tickets.created_at)) / 3600",
            'sqlite' => "((julianday(tickets.date_resolution) - julianday(tickets.created_at)) * 24)",
            default => "TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.date_resolution)",
        };

        $performanceData = Ticket::join('users', 'tickets.assigned_to', '=', 'users.id')
            ->selectRaw("
                users.name as technician,
                COUNT(*) as total_assigned,
                SUM(CASE WHEN tickets.date_resolution IS NOT NULL THEN 1 ELSE 0 END) as resolved,
                AVG({$diffHourExpr}) as avg_resolution_hours,
                SUM(CASE WHEN tickets.sla_depasse = false THEN 1 ELSE 0 END) as sla_compliant
            ")
            ->where('tickets.created_at', '>=', $startDate)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('resolved')
            ->get()
            ->map(function ($item) {
                return [
                    'technician' => $item->technician,
                    'assigned' => $item->total_assigned,
                    'resolved' => $item->resolved,
                    'resolution_rate' => $item->total_assigned > 0 ? round(($item->resolved / $item->total_assigned) * 100, 1) : 0,
                    'avg_resolution_hours' => round($item->avg_resolution_hours ?? 0, 1),
                    'sla_compliance' => $item->total_assigned > 0 ? round(($item->sla_compliant / $item->total_assigned) * 100, 1) : 0,
                ];
            });

        return response()->json([
            'team_performance' => $performanceData,
            'period' => $period,
        ]);
    }
