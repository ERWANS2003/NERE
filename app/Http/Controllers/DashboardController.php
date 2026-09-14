<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
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
            \Log::error('Dashboard error: ' . $e->getMessage());
        }

        return $this->userDashboard();
    }

    protected function adminDashboard()
    {
        try {
            $stats = [
                'total_tickets'       => Ticket::count(),
                'tickets_ouverts'     => Ticket::whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))->count(),
                'tickets_critiques'   => Ticket::whereHas('priorite', fn($q) => $q->where('niveau', '>=', 3))
                                               ->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))->count(),
                'utilisateurs_actifs' => User::where('actif', true)->count(),
            ];

            $recentTickets = Ticket::with(['statut', 'priorite', 'demandeur'])
                ->latest()
                ->limit(8)
                ->get();

        } catch (\Exception $e) {
            \Log::error('Admin dashboard query error: ' . $e->getMessage());
            $stats = ['total_tickets' => 0, 'tickets_ouverts' => 0, 'tickets_critiques' => 0, 'utilisateurs_actifs' => 0];
            $recentTickets = collect();
        }

        $ticketsParDepartement = collect();

        return view('dashboard.admin', compact('stats', 'recentTickets', 'ticketsParDepartement'));
    }

    protected function technicianDashboard()
    {
        $user = auth()->user();

        try {
            $myTickets = Ticket::with(['statut', 'priorite', 'demandeur'])
                ->where('assigned_to', $user->id)
                ->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))
                ->latest()
                ->limit(10)
                ->get();

            $stats = [
                'mes_tickets'           => $myTickets->count(),
                'tickets_critiques'     => $myTickets->filter(fn($t) => ($t->priorite?->niveau ?? 0) >= 3)->count(),
                'en_attente_assignment' => Ticket::whereNull('assigned_to')
                                                 ->whereHas('statut', fn($q) => $q->whereNotIn('slug', ['resolu', 'ferme']))
                                                 ->count(),
                'resolus_aujourdhui'    => Ticket::where('assigned_to', $user->id)
                                                 ->whereDate('updated_at', today())
                                                 ->whereHas('statut', fn($q) => $q->where('slug', 'resolu'))
                                                 ->count(),
            ];

        } catch (\Exception $e) {
            \Log::error('Technician dashboard query error: ' . $e->getMessage());
            $myTickets = collect();
            $stats = ['mes_tickets' => 0, 'tickets_critiques' => 0, 'en_attente_assignment' => 0, 'resolus_aujourdhui' => 0];
        }

        $teamTickets = collect();

        return view('dashboard.technician', compact('myTickets', 'teamTickets', 'stats'));
    }

    protected function userDashboard()
    {
        $user = auth()->user();

        try {
            $myTickets = Ticket::with(['statut', 'priorite'])
                ->where('user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $myTickets = collect();
        }

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

            if ($user->hasRole('admin')) {
                $query->orWhereNotNull('id');
            } elseif ($user->departement_id) {
                $query->orWhere('departement_id', $user->departement_id);
            }
        });
    }
}
