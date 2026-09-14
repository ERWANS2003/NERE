<?php

namespace App\Http\Controllers;

use App\Models\DashboardNotification;
use App\Models\Sla;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SlaController extends Controller
{
    /**
     * Display SLA management dashboard
     */
    public function index()
    {
        $slas = Sla::with(['priorite', 'site', 'tickets'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total_sla' => Sla::count(),
            'active_sla' => Sla::where('actif', true)->count(),
            'tickets_sla_warning' => Ticket::where('sla_depasse', false)
                ->whereNotNull('date_echeance_resolution')
                ->whereBetween('date_echeance_resolution', [
                    now(),
                    now()->addHours(4)
                ])
                ->count(),
            'tickets_sla_breached' => Ticket::where('sla_depasse', true)->count(),
        ];

        return view('sla.index', compact('slas', 'stats'));
    }

    /**
     * Show SLA creation form
     */
    public function create()
    {
        $priorities = TicketPriority::orderByDesc('niveau')->get();
        $sites = Site::orderBy('nom')->get();

        return view('sla.create', compact('priorities', 'sites'));
    }

    /**
     * Store new SLA
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'ticket_priority_id' => 'required|exists:ticket_priorities,id',
            'site_id' => 'nullable|exists:sites,id',
            'temps_reponse_heures' => 'required|numeric|min:0.25|max:168',
            'temps_resolution_heures' => 'required|numeric|min:0.25|max:720',
            'actif' => 'nullable|boolean',
        ]);

        Sla::create($data);

        return redirect()->route('sla.index')->with('success', 'SLA créé avec succès.');
    }

    /**
     * Show SLA details
     */
    public function show(Sla $sla)
    {
        $sla->load(['priorite', 'site', 'tickets' => function ($q) {
            $q->latest()->limit(20);
        }]);

        // Calculate stats for this SLA
        $tickets = $sla->tickets;
        $stats = [
            'total' => $tickets->count(),
            'met' => $tickets->filter(fn ($t) => !$t->sla_depasse)->count(),
            'breached' => $tickets->filter(fn ($t) => $t->sla_depasse)->count(),
            'percentage_met' => $tickets->count() > 0 
                ? round(($tickets->filter(fn ($t) => !$t->sla_depasse)->count() / $tickets->count()) * 100, 1)
                : 0,
        ];

        return view('sla.show', compact('sla', 'stats'));
    }

    /**
     * Show SLA edit form
     */
    public function edit(Sla $sla)
    {
        $priorities = TicketPriority::orderByDesc('niveau')->get();
        $sites = Site::orderBy('nom')->get();

        return view('sla.edit', compact('sla', 'priorities', 'sites'));
    }

    /**
     * Update SLA
     */
    public function update(Request $request, Sla $sla)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'ticket_priority_id' => 'required|exists:ticket_priorities,id',
            'site_id' => 'nullable|exists:sites,id',
            'temps_reponse_heures' => 'required|numeric|min:0.25|max:168',
            'temps_resolution_heures' => 'required|numeric|min:0.25|max:720',
            'actif' => 'nullable|boolean',
        ]);

        $sla->update($data);

        return redirect()->route('sla.show', $sla)->with('success', 'SLA mis à jour avec succès.');
    }

    /**
     * Delete SLA
     */
    public function destroy(Sla $sla)
    {
        $sla->delete();

        return redirect()->route('sla.index')->with('success', 'SLA supprimé.');
    }

    /**
     * Pause SLA for a specific ticket
     */
    public function pauseTicket(Request $request, Ticket $ticket)
    {
        $request->validate([
            'duration_minutes' => 'required|integer|min:5|max:1440',
        ]);

        $duration = $request->input('duration_minutes') * 60; // Convert to seconds
        $ticket->update([
            'sla_temps_pause_secondes' => ($ticket->sla_temps_pause_secondes ?? 0) + $duration,
        ]);

        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action' => 'sla_paused',
            'description' => "SLA pausé pour {$duration} secondes",
            'old_value' => $ticket->sla_temps_pause_secondes,
            'new_value' => ($ticket->sla_temps_pause_secondes ?? 0) + $duration,
        ]);

        return back()->with('success', "SLA en pause pour {$request->input('duration_minutes')} minutes.");
    }

    /**
     * Resume SLA for a ticket
     */
    public function resumeTicket(Ticket $ticket)
    {
        $pausedSeconds = $ticket->sla_temps_pause_secondes ?? 0;

        // Extend both response and resolution deadlines
        if ($ticket->date_echeance_reponse) {
            $ticket->date_echeance_reponse = $ticket->date_echeance_reponse->addSeconds($pausedSeconds);
        }
        if ($ticket->date_echeance_resolution) {
            $ticket->date_echeance_resolution = $ticket->date_echeance_resolution->addSeconds($pausedSeconds);
        }

        $ticket->update([
            'sla_temps_pause_secondes' => 0,
            'date_echeance_reponse' => $ticket->date_echeance_reponse,
            'date_echeance_resolution' => $ticket->date_echeance_resolution,
        ]);

        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action' => 'sla_resumed',
            'description' => "SLA repris après {$pausedSeconds} secondes de pause",
        ]);

        return back()->with('success', 'SLA repris.');
    }

    /**
     * Escalate SLA (change priority or send notifications)
     */
    public function escalateTicket(Request $request, Ticket $ticket)
    {
        $request->validate([
            'new_priority_id' => 'required|exists:ticket_priorities,id',
        ]);

        $oldPriority = $ticket->priorite?->nom;
        $newPriority = \App\Models\TicketPriority::find($request->input('new_priority_id'))->nom;

        $ticket->update(['priorite_id' => $request->input('new_priority_id')]);

        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action' => 'sla_escalated',
            'description' => "SLA escaladé: priorité changée de {$oldPriority} à {$newPriority}",
            'old_value' => $oldPriority,
            'new_value' => $newPriority,
        ]);

        // Notify relevant parties
        DashboardNotification::create([
            'user_id' => $ticket->assignee_id,
            'type' => 'sla_escalated',
            'title' => '⬆️ Ticket Escaladé',
            'message' => "{$ticket->reference}: Priorité escaladée à {$newPriority}",
            'icon' => '⬆️',
            'color' => 'danger',
            'action_url' => route('tickets.show', $ticket),
            'data' => ['ticket_id' => $ticket->id],
        ]);

        return back()->with('success', 'SLA escaladé. Priorité changée à ' . $newPriority . '.');
    }

    /**
     * Get SLA progress for a ticket (used by AJAX)
     */
    public function getTicketProgress(Ticket $ticket)
    {
        $resolution = $ticket->date_echeance_resolution;
        $response = $ticket->date_echeance_reponse;

        if (!$resolution) {
            return response()->json(['error' => 'No SLA'], 404);
        }

        $totalSeconds = $resolution->diffInSeconds($ticket->created_at);
        $elapsedSeconds = now()->diffInSeconds($ticket->created_at);
        $percentage = min(100, round(($elapsedSeconds / $totalSeconds) * 100, 1));

        $status = $ticket->sla_depasse ? 'breached' : ($percentage >= 75 ? 'warning' : 'ok');

        return response()->json([
            'percentage' => $percentage,
            'status' => $status,
            'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
            'deadline' => $resolution->format('Y-m-d H:i:s'),
            'time_remaining' => now()->diffForHumans($resolution, true),
            'is_paused' => ($ticket->sla_temps_pause_secondes ?? 0) > 0,
            'paused_seconds' => $ticket->sla_temps_pause_secondes ?? 0,
        ]);
    }
}
