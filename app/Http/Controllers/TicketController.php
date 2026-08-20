<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\PriorityService;
use App\Services\SlaService;
use App\Services\TicketWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function __construct(
        protected PriorityService $priorityService,
        protected AssignmentService $assignmentService,
        protected SlaService $slaService,
        protected TicketWorkflowService $ticketWorkflowService,
    ) {}

    // Phase 4 : Consulter / Recherche / Filtres
    public function index(Request $request)
    {
        $tickets = Ticket::query()
            ->with(['demandeur', 'categorie', 'priorite', 'statut', 'technicien', 'site'])
            ->recherche($request->query('q'))
            ->when($request->filled('statut'), fn($q) => $q->where('ticket_status_id', $request->query('statut')))
            ->when($request->filled('priorite'), fn($q) => $q->where('ticket_priority_id', $request->query('priorite')))
            ->when($request->filled('categorie'), fn($q) => $q->where('ticket_category_id', $request->query('categorie')))
            ->when($request->filled('site'), fn($q) => $q->where('site_id', $request->query('site')))
            ->when($request->filled('assigne_a'), fn($q) => $q->where('assigned_to', $request->query('assigne_a')))
            ->when(! Auth::user()->est_technicien, fn($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statuts = TicketStatus::orderBy('ordre')->get();
        $priorites = TicketPriority::orderByDesc('niveau')->get();
        $categories = TicketCategory::where('actif', true)->orderBy('nom')->get();
        $sites = Site::orderBy('nom')->get();

        return view('tickets.index', compact('tickets', 'statuts', 'priorites', 'categories', 'sites'));
    }

    public function create()
    {
        $categories = TicketCategory::where('actif', true)->orderBy('nom')->get();
        $sites = Site::orderBy('nom')->get();

        return view('tickets.create', compact('categories', 'sites'));
    }

    // Phase 4 : Créer un ticket (avec calcul de priorité, affectation et SLA automatiques)
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'site_id' => 'nullable|exists:sites,id',
            'impact' => 'required|in:Faible,Moyen,Élevé,Critique',
            'urgence' => 'required|in:Faible,Moyen,Élevé,Critique',
            'pieces_jointes.*' => 'nullable|file|max:10240',
        ]);

        $ticket = DB::transaction(function () use ($data, $request) {
            $ticket = new Ticket($data);
            $ticket->user_id = Auth::id();
            $ticket->departement_id = Auth::user()->departement_id;
            $ticket = $this->ticketWorkflowService->appliquerApresCreation($ticket, Auth::user());

            if ($request->hasFile('pieces_jointes')) {
                foreach ($request->file('pieces_jointes') as $fichier) {
                    $chemin = $fichier->store("tickets/{$ticket->id}", 'public');
                    $ticket->attachments()->create([
                        'nom_original' => $fichier->getClientOriginalName(),
                        'chemin' => $chemin,
                        'type_mime' => $fichier->getClientMimeType(),
                        'taille' => $fichier->getSize(),
                        'uploaded_by' => Auth::id(),
                    ]);
                }
            }

            return $ticket;
        });

        // Phase 10 : notification "nouveau ticket" / "ticket assigné" à déclencher ici
        // NotificationDispatcher::ticketCree($ticket);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket créé : ' . $ticket->reference);
    }

    public function show(Ticket $ticket)
    {
        abort_unless($this->peutConsulter($ticket), 403);

        $ticket->load(['comments.auteur', 'attachments.uploader', 'histories.utilisateur', 'assets', 'categorie', 'priorite', 'statut', 'technicien', 'site', 'departement', 'team']);

        $statutsMap = TicketStatus::pluck('nom', 'id');
        $techniciensMap = User::pluck('name', 'id');

        return view('tickets.show', compact('ticket', 'statutsMap', 'techniciensMap'));
    }

    public function edit(Ticket $ticket)
    {
        abort_unless($this->peutGerer($ticket) || $ticket->user_id === Auth::id(), 403);

        $statuts = TicketStatus::orderBy('ordre')->get();
        $techniciens = User::where('est_technicien', true)->where('actif', true)->orderBy('name')->get();

        return view('tickets.edit', compact('ticket', 'statuts', 'techniciens'));
    }

    // Phase 4 : Modifier
    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'impact' => 'sometimes|in:Faible,Moyen,Élevé,Critique',
            'urgence' => 'sometimes|in:Faible,Moyen,Élevé,Critique',
            'ticket_status_id' => 'sometimes|exists:ticket_statuses,id',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
        ]);

        abort_unless($this->peutGerer($ticket) || $ticket->user_id === Auth::id(), 403);

        if ((array_key_exists('ticket_status_id', $data) || array_key_exists('assigned_to', $data)) && ! $this->peutGerer($ticket)) {
            abort(403);
        }

        DB::transaction(function () use ($ticket, $data) {
            $ancienStatut = $ticket->ticket_status_id;
            $ancienAssigne = $ticket->assigned_to;

            if (isset($data['impact']) || isset($data['urgence'])) {
                $ticket->fill($data);
                $this->priorityService->appliquerAuTicket($ticket);
            } else {
                $ticket->fill($data);
            }

            $ticket->save();

            if (isset($data['ticket_status_id']) && $data['ticket_status_id'] != $ancienStatut) {
                $this->enregistrerHistorique($ticket, 'changement_statut', (string) $ancienStatut, (string) $data['ticket_status_id']);

                $nouveauStatut = TicketStatus::find($data['ticket_status_id']);
                if ($nouveauStatut?->est_final) {
                    $ticket->update(['date_cloture' => now()]);
                }
            }

            if (array_key_exists('assigned_to', $data) && $data['assigned_to'] != $ancienAssigne) {
                $this->enregistrerHistorique($ticket, 'affectation', (string) $ancienAssigne, (string) $data['assigned_to']);
                $ticket->update(['assigned_by' => Auth::id(), 'date_assignation' => now()]);
            }
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket mis à jour.');
    }

    // Phase 4 : Annuler (suppression douce)
    public function destroy(Ticket $ticket)
    {
        abort_unless($ticket->user_id === Auth::id() || Auth::user()->role?->slug === 'admin', 403);

        $ticket->delete();
        $this->enregistrerHistorique($ticket, 'annulation', null, null);

        return redirect()->route('tickets.index')->with('success', 'Ticket annulé.');
    }

    // Ajouter un commentaire
    public function storeComment(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'contenu' => 'required|string',
            'interne' => 'boolean',
        ]);

        $ticket->comments()->create([
            'user_id' => Auth::id(),
            'contenu' => $data['contenu'],
            'interne' => $this->peutGerer($ticket) && ($data['interne'] ?? false),
        ]);

        $this->enregistrerHistorique($ticket, 'commentaire', null, null);

        return back()->with('success', 'Commentaire ajouté.');
    }

    // Joindre un fichier
    public function storeAttachment(Request $request, Ticket $ticket)
    {
        abort_unless($this->peutConsulter($ticket), 403);

        $request->validate(['fichier' => 'required|file|max:10240']);

        $fichier = $request->file('fichier');
        $chemin = $fichier->store("tickets/{$ticket->id}", 'public');

        $ticket->attachments()->create([
            'nom_original' => $fichier->getClientOriginalName(),
            'chemin' => $chemin,
            'type_mime' => $fichier->getClientMimeType(),
            'taille' => $fichier->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Pièce jointe ajoutée.');
    }

    protected function peutGerer(Ticket $ticket): bool
    {
        $user = Auth::user();

        return $user->role?->permissions()->where('slug', 'tickets.assign')->exists()
            || $user->est_technicien
            || $user->role?->slug === 'admin';
    }

    protected function peutConsulter(Ticket $ticket): bool
    {
        return $this->peutGerer($ticket)
            || $ticket->user_id === Auth::id()
            || $ticket->assigned_to === Auth::id();
    }

    protected function enregistrerHistorique(Ticket $ticket, string $action, ?string $ancien, ?string $nouveau): void
    {
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ancienne_valeur' => $ancien,
            'nouvelle_valeur' => $nouveau,
        ]);
    }
}
