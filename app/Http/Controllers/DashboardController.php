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
        
        // Return raw HTML to test if Blade is the problem
        return response(<<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-dark-900 text-white">
    <div class="p-8">
        <h1 class="text-2xl font-bold mb-4">Dashboard Test</h1>
        <p class="mb-2">If you see this, the dashboard is working!</p>
        <p class="text-gray-400">User: SYSTEM_OK</p>
    </div>
</body>
</html>
HTML
        )->header('Content-Type', 'text/html');
    }
    
    protected function adminDashboard()
    {
        try {
            $stats = [
                'total_tickets' => Ticket::count(),
                'tickets_ouverts' => 0,
                'tickets_critiques' => 0,
                'utilisateurs_actifs' => User::where('actif', true)->count(),
                'techniciens_disponibles' => 0,
                'sla_depasse' => 0,
            ];
            
            $recentTickets = collect(); // Empty collection for now
            $ticketsParDepartement = collect();
            
            return view('dashboard.admin', compact('stats', 'recentTickets', 'ticketsParDepartement'));
        } catch (\Exception $e) {
            \Log::error('Admin dashboard error: ' . $e->getMessage(), ['exception' => $e]);
            return view('dashboard.simple', [
                'title' => 'Tableau de Bord Admin',
                'message' => 'Erreur lors du chargement du tableau de bord: ' . $e->getMessage()
            ]);
        }
    }
    
    protected function technicianDashboard()
    {
        try {
            $user = auth()->user();
            
            $myTickets = collect();
            $teamTickets = collect();
            
            $stats = [
                'mes_tickets' => 0,
                'tickets_critiques' => 0,
                'en_attente_assignment' => 0,
                'resolus_aujourdhui' => 0,
            ];
            
            return view('dashboard.technician', compact('myTickets', 'teamTickets', 'stats'));
        } catch (\Exception $e) {
            \Log::error('Technician dashboard error: ' . $e->getMessage(), ['exception' => $e]);
            return view('dashboard.simple', [
                'title' => 'Mes Tickets',
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ]);
        }
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
