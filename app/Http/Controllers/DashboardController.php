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
        try {
            $user = auth()->user();
            return view('dashboard.minimal-html');
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ], 500);
        }
    }
    
    protected function adminDashboard()
    {
        $stats = ['total_tickets' => 0, 'tickets_ouverts' => 0];
        $recentTickets = [];
        $ticketsParDepartement = [];
        
        return view('dashboard.minimal', compact('stats', 'recentTickets', 'ticketsParDepartement'));
    }
    
    protected function technicianDashboard()
    {
        $myTickets = [];
        $teamTickets = [];
        $stats = ['mes_tickets' => 0, 'tickets_critiques' => 0];
        
        return view('dashboard.minimal', compact('myTickets', 'teamTickets', 'stats'));
    }
    
    protected function userDashboard()
    {
        // Vue portail simple pour les demandeurs
        return view('dashboard.simple', [
            'title' => 'Accueil',
            'message' => 'Bienvenue sur votre portail de services'
        ]);
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
