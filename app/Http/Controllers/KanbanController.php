<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatusSlug;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use ValueError;

class KanbanController extends Controller
{
    /**
     * Display the Kanban board.
     *
     * Scoped through Ticket::visibleA() — the board used to query every ticket
     * in the table with no ownership filter, so any authenticated user could
     * read every department's board.
     */
    public function index(Request $request)
    {
        $statuses = TicketStatus::orderBy('ordre')->get();

        $tickets = Ticket::visibleA(auth()->user())
            ->with(['statut', 'priorite', 'technicien', 'demandeur', 'departement'])
            ->when(
                $request->filled('departement'),
                fn ($q) => $q->where('departement_id', $request->integer('departement'))
            )
            ->when(
                $request->filled('priorite'),
                fn ($q) => $q->where('ticket_priority_id', $request->integer('priorite'))
            )
            ->when(
                $request->filled('assignee'),
                fn ($q) => $q->where('assigned_to', $request->integer('assignee'))
            )
            ->latest()
            ->get()
            ->groupBy(fn (Ticket $ticket) => $ticket->ticket_status_id);

        return view('kanban.index', compact('statuses', 'tickets'));
    }

    /**
     * Move a ticket to a different status (AJAX).
     */
    public function moveTicket(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status_id' => ['required', 'integer', 'exists:ticket_statuses,id'],
        ]);

        $user = $request->user();

        // The board is scoped by Ticket::visibleA(); a drag-and-drop must respect
        // the same boundary, otherwise any authenticated user could probe ticket
        // ids and read the status of tickets outside their scope.
        abort_unless(
            Ticket::visibleA($user)->whereKey($ticket->getKey())->exists(),
            403,
            'Ticket hors de votre périmètre.'
        );

        if ($ticket->ticket_status_id === (int) $validated['status_id']) {
            return response()->json(['success' => true, 'message' => 'Statut inchangé.']);
        }

        $cible = TicketStatus::findOrFail($validated['status_id']);

        try {
            $vers = TicketStatusSlug::from($cible->slug);
        } catch (ValueError) {
            return response()->json([
                'success' => false,
                'message' => "Statut « {$cible->nom} » non pris en charge par le workflow.",
            ], 422);
        }

        // Delegate to the workflow: it owns the transition matrix, the per-role
        // authorisation, the SLA side effects and the history trail. Writing
        // ticket_status_id directly here bypassed all four.
        try {
            app(TicketWorkflowService::class)->transitionner($ticket, $vers, $user);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Ticket déplacé vers « {$cible->nom} ».",
        ]);
    }
}
