<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\SavedSearch;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Http\Request;

class TicketSearchController extends Controller
{
    /**
     * Display advanced search interface
     */
    public function index(Request $request)
    {
        $filters = [
            'q' => $request->query('q'),
            'status_id' => $request->query('status_id'),
            'priority_id' => $request->query('priority_id'),
            'category_id' => $request->query('category_id'),
            'assigned_to' => $request->query('assigned_to'),
            'created_by' => $request->query('created_by'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'sort' => $request->query('sort', 'latest'),
        ];

        $query = Ticket::with(['status', 'priorite', 'categorie', 'assignee', 'createur']);

        // Full-text search
        if ($filters['q']) {
            $query->where(function ($q) use ($filters) {
                $searchTerm = '%' . $filters['q'] . '%';
                $q->where('reference', 'like', $searchTerm)
                    ->orWhere('titre', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm);
            });
        }

        // Status filter
        if ($filters['status_id']) {
            $query->where('status_id', $filters['status_id']);
        }

        // Priority filter
        if ($filters['priority_id']) {
            $query->where('priorite_id', $filters['priority_id']);
        }

        // Category filter
        if ($filters['category_id']) {
            $query->where('ticket_category_id', $filters['category_id']);
        }

        // Assigned to filter
        if ($filters['assigned_to']) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Created by filter
        if ($filters['created_by']) {
            $query->where('created_by', $filters['created_by']);
        }

        // Date range filter
        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Sorting
        switch ($filters['sort']) {
            case 'oldest':
                $query->oldest();
                break;
            case 'priority':
                $query->join('ticket_priorities', 'tickets.priorite_id', '=', 'ticket_priorities.id')
                    ->orderByDesc('ticket_priorities.niveau');
                break;
            case 'unresolved':
                $query->whereNull('date_resolution')->latest();
                break;
            default:
                $query->latest();
        }

        $tickets = $query->paginate(20)->withQueryString();

        $statuses = TicketStatus::orderBy('ordre')->get();
        $priorities = TicketPriority::orderByDesc('niveau')->get();
        $categories = TicketCategory::where('actif', true)->orderBy('nom')->get();
        $users = User::where('actif', true)->orderBy('name')->get();
        $savedSearches = auth()->user()->savedSearches;

        return view('search.advanced', compact(
            'tickets',
            'filters',
            'statuses',
            'priorities',
            'categories',
            'users',
            'savedSearches'
        ));
    }

    /**
     * Save search filter for later use
     */
    public function save(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|json',
        ]);

        SavedSearch::create([
            'user_id' => auth()->id(),
            'name' => $request->input('name'),
            'filters' => $request->input('filters'),
        ]);

        return back()->with('success', 'Recherche sauvegardée avec succès.');
    }

    /**
     * Load a saved search
     */
    public function load(SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== auth()->id()) {
            return redirect()->route('search.index')->with('error', 'Accès refusé');
        }

        return redirect()->route('search.index', (array) json_decode($savedSearch->filters));
    }

    /**
     * Delete a saved search
     */
    public function deleteSaved(SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== auth()->id()) {
            return redirect()->route('search.index')->with('error', 'Accès refusé');
        }

        $name = $savedSearch->name;
        $savedSearch->delete();

        return back()->with('success', "Recherche '{$name}' supprimée.");
    }

    /**
     * Quick search API endpoint (for typeahead/autocomplete)
     */
    public function quickSearch(Request $request)
    {
        $q = $request->query('q');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $tickets = Ticket::where(function ($query) use ($q) {
            $searchTerm = '%' . $q . '%';
            $query->where('reference', 'like', $searchTerm)
                ->orWhere('titre', 'like', $searchTerm);
        })
            ->with(['status', 'priorite', 'assignee'])
            ->limit(10)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'reference' => $ticket->reference,
                    'titre' => $ticket->titre,
                    'status' => $ticket->status?->nom,
                    'priority' => $ticket->priorite?->nom,
                    'assignee' => $ticket->assignee?->name,
                    'url' => route('tickets.show', $ticket),
                ];
            });

        return response()->json($tickets);
    }
}
