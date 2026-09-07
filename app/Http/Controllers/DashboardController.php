<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Departement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // Phase 7 : Tableau de bord (données consommées par Chart.js côté vue)
    public function index()
    {
        $tickets = $this->ticketsVisibles();
        $serviceKpis = $this->serviceKpis($tickets);
        $departements = Departement::withCount(['teams', 'tickets'])
            ->with(['teams.categories' => fn($query) => $query->where('actif', true)])
            ->where('actif', true)
            ->when(Auth::user()->departement_id && ! Auth::user()->hasRole('admin') && ! Auth::user()->hasRole('demandeur'), fn($q) => $q->whereKey(Auth::user()->departement_id))
            ->orderBy('nom')
            ->get();
        $stats = [
            'total_tickets' => (clone $tickets)->count(),
            'tickets_ouverts' => (clone $tickets)->ouverts()->count(),
            'tickets_critiques' => (clone $tickets)->critiques()->ouverts()->count(),
            'tickets_en_attente' => (clone $tickets)->whereHas('statut', fn($q) => $q->where('slug', 'en_attente'))->count(),
            'tickets_resolus' => (clone $tickets)->whereHas('statut', fn($q) => $q->where('slug', 'resolu'))->count(),
            'tickets_sla_depasse' => (clone $tickets)->where('sla_depasse', true)->count(),
            'techniciens_disponibles' => User::when(Auth::user()->departement_id && ! Auth::user()->hasRole('admin'), fn($q) => $q->where('departement_id', Auth::user()->departement_id))->where('est_technicien', true)->where('disponible', true)->count(),
        ];

        $ticketsParStatut = (clone $tickets)
            ->join('ticket_statuses', 'tickets.ticket_status_id', '=', 'ticket_statuses.id')
            ->select('ticket_statuses.nom', DB::raw('count(*) as total'))
            ->groupBy('ticket_statuses.nom')
            ->pluck('total', 'nom');

        $ticketsParPriorite = (clone $tickets)
            ->join('ticket_priorities', 'tickets.ticket_priority_id', '=', 'ticket_priorities.id')
            ->select('ticket_priorities.nom', DB::raw('count(*) as total'))
            ->groupBy('ticket_priorities.nom')
            ->pluck('total', 'nom');

        $ticketsParSite = (clone $tickets)
            ->join('sites', 'tickets.site_id', '=', 'sites.id')
            ->select('sites.nom', DB::raw('count(*) as total'))
            ->groupBy('sites.nom')
            ->pluck('total', 'nom');

        $dateFormatExpr = match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $evolutionMensuelle = (clone $tickets)
            ->selectRaw("{$dateFormatExpr} as mois, count(*) as total")
            ->groupBy('mois')
            ->orderByDesc('mois')
            ->limit(12)
            ->get()
            ->sortBy('mois')
            ->pluck('total', 'mois');

        $derniersTickets = (clone $tickets)->with(['statut', 'priorite', 'site'])
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'ticketsParStatut',
            'ticketsParPriorite',
            'ticketsParSite',
            'evolutionMensuelle',
            'derniersTickets',
            'departements',
            'serviceKpis'
        ));
    }

    protected function ticketsVisibles()
    {
        $user = Auth::user();

        return Ticket::query()->where(function ($query) use ($user) {
            $query->where('user_id', $user->id);

            if ($user->hasRole('demandeur')) {
                return;
            }

            if (! $user->hasRole('admin') && $user->departement_id) {
                $query->orWhere('departement_id', $user->departement_id);
            } elseif ($user->hasRole('admin')) {
                $query->orWhereNotNull('tickets.id');
            }
        });
    }
    protected function serviceKpis($tickets): array
    {
        $compterCategories = function (array $categories) use ($tickets): int {
            return (clone $tickets)->whereHas('categorie', fn($query) => $query->whereIn('nom', $categories))->count();
        };

        return [
            'IT' => [
                'Pannes et demandes' => $compterCategories(['Poste de travail', 'Réseau', 'Email', 'VPN', 'Imprimante', 'SAP', 'Office', 'Téléphone']),
                'Tickets hors SLA' => (clone $tickets)->where('sla_depasse', true)->count(),
            ],
            'HSE' => [
                'Accidents' => $compterCategories(['Accident', 'Accident du travail']),
                'Presqu’accidents' => $compterCategories(["Presqu'accident", 'Presqu’accident']),
                'Actions à surveiller' => (clone $tickets)->where('validation_statut', 'en_attente')->whereHas('departement', fn($query) => $query->where('nom', 'HSE'))->count(),
            ],
            'Maintenance' => [
                'Interventions' => $compterCategories(['Engin', 'Pompe', 'Convoyeur', 'Générateur', 'Climatisation', 'Électricité', 'SCADA / OT']),
                'Équipements critiques' => (clone $tickets)->where('impact', 'Critique')->whereHas('departement', fn($query) => $query->where('nom', 'Maintenance'))->count(),
            ],
            'RH' => [
                'Congés' => $compterCategories(['Congé']),
                'Recrutements' => $compterCategories(['Recrutement']),
                'Demandes à valider' => (clone $tickets)->where('validation_statut', 'en_attente')->whereHas('departement', fn($query) => $query->where('nom', 'RH'))->count(),
            ],
        ];
    }
}
