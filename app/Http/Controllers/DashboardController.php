<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Departement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // Portail de services - Adapté selon le rôle
    public function index()
    {
        $user = auth()->user();
        
        try {
            if ($user->hasRole('admin')) {
                return $this->adminDashboard();
            }
            if ($user->est_technicien || $user->hasRole('technicien')) {
                return $this->technicianDashboard();
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage(), ['exception' => $e]);
        }
        
        return $this->userDashboard();
    }
    
    protected function adminDashboard()
    {
        $stats = [
            'total_tickets'       => Ticket::count(),
            'tickets_ouverts'     => Ticket::whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))->count(),
            'tickets_critiques'   => Ticket::whereHas('priorite', fn($q) => $q->where('niveau', '>=', 3))->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))->count(),
            'utilisateurs_actifs' => User::where('actif', true)->count(),
        ];

        $recentTickets = Ticket::with(['statut', 'priorite', 'user'])
            ->latest()
            ->limit(8)
            ->get();

        $ticketsParDepartement = collect();

        return view('dashboard.admin', compact('stats', 'recentTickets', 'ticketsParDepartement'));
    }
    
    protected function technicianDashboard()
    {
        $user = auth()->user();

        $myTickets = Ticket::with(['statut', 'priorite', 'user'])
            ->where('assigned_to', $user->id)
            ->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))
            ->latest()
            ->limit(10)
            ->get();

        $teamTickets = collect();

        $stats = [
            'mes_tickets'            => $myTickets->count(),
            'tickets_critiques'      => $myTickets->filter(fn($t) => $t->priorite?->niveau >= 3)->count(),
            'en_attente_assignment'  => Ticket::whereNull('assigned_to')->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))->count(),
            'resolus_aujourdhui'     => Ticket::where('assigned_to', $user->id)->whereDate('updated_at', today())->whereHas('statut', fn($q) => $q->where('slug', 'resolu'))->count(),
        ];

        return view('dashboard.technician', compact('myTickets', 'teamTickets', 'stats'));
    }
    
    protected function userDashboard()
    {
        $user = auth()->user();
        $myTickets = Ticket::with(['statut', 'priorite'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.user', compact('myTickets'));
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
