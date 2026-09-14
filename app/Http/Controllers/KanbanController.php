<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    /**
     * Display Kanban board view
     */
    public function index(Request $request)
    {
        $statuses = TicketStatus::orderBy('ordre')->get();

        $tickets = Ticket::with(['status', 'priorite', 'assignee', 'createur'])
            ->when($request->filled('departement'), fn ($q) => $q->where('departement_id', $request->query('departement')))
            ->when($request->filled('priorite'), fn ($q) => $q->where('priorite_id', $request->query('priorite')))
            ->when($request->filled('assignee'), fn ($q) => $q->where('assignee_id', $request->query('assignee')))
            ->latest()
            ->get()
            ->groupBy('status_id');

        return view('kanban.index', compact('statuses', 'tickets'));
    }

    /**
     * Move ticket to different status (AJAX)
     */
    public function moveTicket(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status_id' => 'required|exists:ticket_statuses,id',
            'position' => 'nullable|integer|min:0',
        ]);

        $oldStatus = $ticket->status_id;
        $newStatus = $request->input('status_id');

        // Update ticket status
        $ticket->update(['status_id' => $newStatus]);

        // Log the change
        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action' => 'status_change',
            'description' => "Statut changé de {$oldStatus} à {$newStatus}",
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ]);

        return response()->json(['success' => true, 'message' => 'Ticket moved successfully']);
    }
}
