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
        
        // Admin: Vue avec statistiques complètes + gestion
        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }
        
        // Technicien: Vue avec tickets assignés + files d'attente
        if ($user->est_technicien || $user->hasRole('technicien')) {
            return $this->technicianDashboard();
        }
        
        // Demandeur: Vue portail de services simple
        return $this->userDashboard();
    }
    
    protected function adminDashboard()
    {
        $stats = [
            'total_tickets' => Ticket::count(),
            'tickets_ouverts' => Ticket::whereHas('statut', fn($q) => $q->whereIn('slug', ['nouveau', 'assigne', 'en_cours']))->count(),
            'tickets_critiques' => Ticket::whereHas('priorite', fn($q) => $q->where('niveau', '>=', 4))
                ->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'clos']))
                ->count(),
            'utilisateurs_actifs' => User::where('actif', true)->count(),
            'techniciens_disponibles' => User::where('est_technicien', true)->where('disponible', true)->count(),
            'sla_depasse' => Ticket::where('sla_depasse', true)->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'clos']))->count(),
        ];
        
        $recentTickets = Ticket::with(['demandeur', 'statut', 'priorite', 'technicien'])
            ->latest()
            ->limit(10)
            ->get();
            
        $ticketsParDepartement = Ticket::join('departements', 'tickets.departement_id', '=', 'departements.id')
            ->select('departements.nom', DB::raw('count(*) as total'))
            ->groupBy('departements.nom')
            ->pluck('total', 'nom');
        
        return view('dashboard.admin', compact('stats', 'recentTickets', 'ticketsParDepartement'));
    }
    
    protected function technicianDashboard()
    {
        $user = auth()->user();
        
        $myTickets = Ticket::where('assigned_to', $user->id)
            ->with(['demandeur', 'statut', 'priorite', 'categorie'])
            ->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'clos']))
            ->orderByRaw("CASE 
                WHEN sla_depasse = true THEN 1
                WHEN ticket_priority_id IN (SELECT id FROM ticket_priorities WHERE niveau >= 4) THEN 2
                ELSE 3
            END")
            ->get();
            
        $teamTickets = collect();
        if ($user->teams->count() > 0) {
            $teamIds = $user->teams->pluck('id');
            $teamTickets = Ticket::whereIn('team_id', $teamIds)
                ->whereNull('assigned_to')
                ->with(['demandeur', 'statut', 'priorite', 'categorie'])
                ->whereHas('statut', fn($q) => $q->where('slug', 'nouveau'))
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();
        }
        
        $stats = [
            'mes_tickets' => $myTickets->count(),
            'tickets_critiques' => $myTickets->where('sla_depasse', true)->count(),
            'en_attente_assignment' => $teamTickets->count(),
            'resolus_aujourdhui' => Ticket::where('assigned_to', $user->id)
                ->whereHas('statut', fn($q) => $q->where('slug', 'resolu'))
                ->whereDate('updated_at', today())
                ->count(),
        ];
        
        return view('dashboard.technician', compact('myTickets', 'teamTickets', 'stats'));
    }
    
    protected function userDashboard()
    {
        // Vue portail simple pour les demandeurs
        // Pas besoin de passer de données complexes, la vue est simple
        return view('dashboard.customizable');
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
