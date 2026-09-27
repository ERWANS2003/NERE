<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\SafetyIncident;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    /**
     * Statuts qui ne sont plus « en cours de traitement ».
     *
     * L'ancien code filtrait sur `ferme`, un slug qui n'a jamais existé dans
     * ticket_statuses : le « closed » des tickets résolus ne comptait donc pas
     * comme clôturé et les compteurs d'ouverts gonflaient.
     */
    private const STATUTS_TRAITES = ['resolu', 'clos', 'annule'];

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        if ($user->est_technicien || $user->hasRole('technicien')) {
            return $this->technicianDashboard();
        }

        return $this->userDashboard();
    }

    protected function adminDashboard()
    {
        // No try/catch here on purpose: swallowing the exception and rendering
        // zeros is how the wrong-column bugs in this controller stayed hidden
        // for so long. A real query failure should be visible in the log.
        $stats = [
            'total_tickets' => Ticket::count(),
            'tickets_ouverts' => $this->ouverts()->count(),
            'tickets_critiques' => $this->ouverts()
                ->whereHas('priorite', fn ($q) => $q->where('niveau', '>=', 3))
                ->count(),
            'utilisateurs_actifs' => User::where('actif', true)->count(),
            'techniciens_disponibles' => User::where('est_technicien', true)
                ->where('actif', true)
                ->where('disponible', true)
                ->count(),
            'sla_depasse' => $this->ouverts()->where('sla_depasse', true)->count(),
        ];

        $recentTickets = Ticket::with(['statut', 'priorite', 'demandeur'])
            ->latest()
            ->limit(8)
            ->get();

        $ticketsParDepartement = Departement::withCount('tickets')
            ->where('actif', true)
            ->orderByDesc('tickets_count')
            ->get()
            ->mapWithKeys(fn (Departement $departement) => [$departement->nom => $departement->tickets_count]);

        $securite = [
            'incidents_ouverts' => SafetyIncident::unresolved()->count(),
            'incidents_critiques' => SafetyIncident::critical()->unresolved()->count(),
            'incidents_recents' => SafetyIncident::recent(30)->count(),
        ];

        return view('dashboard.admin', compact('stats', 'recentTickets', 'ticketsParDepartement', 'securite'));
    }

    protected function technicianDashboard()
    {
        $user = auth()->user();

        $myTickets = Ticket::where('assigned_to', $user->id)
            ->whereNotIn('ticket_status_id', $this->statutsTraites())
            ->with(['statut', 'priorite', 'demandeur'])
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'mes_tickets' => $myTickets->count(),
            'tickets_critiques' => $myTickets->filter(fn (Ticket $t) => ($t->priorite?->niveau ?? 0) >= 3)->count(),
            'en_attente_assignment' => $this->ouverts()->whereNull('assigned_to')->count(),
            'resolus_aujourdhui' => Ticket::where('assigned_to', $user->id)
                ->whereHas('statut', fn ($q) => $q->where('slug', 'resolu'))
                ->whereDate('updated_at', today())
                ->count(),
        ];

        // The team board is scoped like every other listing: a technicien sees
        // their department, never another department's queue.
        $teamTickets = Ticket::visibleA($user)
            ->whereNotIn('ticket_status_id', $this->statutsTraites())
            ->where('assigned_to', '!=', $user->id)
            ->whereNotNull('assigned_to')
            ->with(['statut', 'priorite', 'technicien'])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.technician', compact('myTickets', 'teamTickets', 'stats'));
    }

    protected function userDashboard()
    {
        $myTickets = Ticket::where('user_id', auth()->id())
            ->with(['statut', 'priorite'])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.user', compact('myTickets'));
    }

    /**
     * Ids des statuts traités, résolus une seule fois par requête.
     *
     * @var \Illuminate\Support\Collection<int, int>|null
     */
    private ?\Illuminate\Support\Collection $statutsTraites = null;

    /** @return \Illuminate\Support\Collection<int, int> */
    protected function statutsTraites()
    {
        return $this->statutsTraites ??= TicketStatus::whereIn('slug', self::STATUTS_TRAITES)->pluck('id');
    }

    protected function ouverts(): Builder
    {
        return Ticket::whereNotIn('ticket_status_id', $this->statutsTraites());
    }
}
