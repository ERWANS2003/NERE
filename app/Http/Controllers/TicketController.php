<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Departement;
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
use App\Enums\TicketStatusSlug;
use App\Notifications\TicketCreatedNotification;
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
        $perPage = in_array((int) $request->query('per_page'), [10, 20, 25, 50, 100], true)
            ? (int) $request->query('per_page')
            : 20;

        $query = $this->ticketsVisibles();

        if ($request->query('archives') === 'uniquement' || $request->query('trash') === 'only') {
            $query->onlyTrashed();
        } elseif ($request->query('archives') === 'avec' || $request->query('trash') === 'with') {
            $query->withTrashed();
        }

        $tickets = $query
            ->with(['demandeur', 'categorie', 'priorite', 'statut', 'technicien', 'site'])
            ->recherche($request->query('q'))
            ->when($request->filled('statut'), fn($q) => $q->where('ticket_status_id', $request->query('statut')))
            ->when($request->filled('priorite'), fn($q) => $q->where('ticket_priority_id', $request->query('priorite')))
            ->when($request->filled('categorie'), fn($q) => $q->where('ticket_category_id', $request->query('categorie')))
            ->when($request->filled('site'), fn($q) => $q->where('site_id', $request->query('site')))
            ->when($request->filled('assigne_a'), fn($q) => $q->where('assigned_to', $request->query('assigne_a')))
            ->when($request->filled('departement'), fn($q) => $q->where('departement_id', $request->query('departement')))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $statuts = TicketStatus::orderBy('ordre')->get();
        $priorites = TicketPriority::orderByDesc('niveau')->get();
        $categories = TicketCategory::where('actif', true)->orderBy('nom')->get();
        $departements = Departement::where('actif', true)->orderBy('nom')->get();
        $sites = Site::orderBy('nom')->get();

        return view('tickets.index', compact('tickets', 'statuts', 'priorites', 'categories', 'departements', 'sites', 'perPage'));
    }

    public function create(Request $request)
    {
        $categories = TicketCategory::with('team.departement')->where('actif', true)->orderBy('nom')->get();
        $departements = Departement::where('actif', true)->orderBy('nom')->get();
        $sites = Site::orderBy('nom')->get();

        // Pre-select department if passed from URL (from dashboard cards)
        $selectedDepartment = $request->query('department');

        return view('tickets.create', compact('categories', 'departements', 'sites', 'selectedDepartment'));
    }

    // Phase 4 : Créer un ticket (avec calcul de priorité, affectation et SLA automatiques)
    public function store(Request $request)
    {
        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:incident,demande',
            'departement_id' => 'required|exists:departements,id',
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'site_id' => 'nullable|exists:sites,id',
            'impact' => 'required|in:Faible,Moyen,Élevé,Critique',
            'urgence' => 'required|in:Faible,Moyen,Élevé,Critique',
            'service_data' => 'nullable|array',
            'service_data.date' => 'nullable|date',
            'service_data.heure' => 'nullable|date_format:H:i',
            'service_data.lieu' => 'nullable|string|max:255',
            'service_data.gravite' => 'nullable|string|max:100',
            'service_data.personnes' => 'nullable|string|max:1000',
            'pieces_jointes.*' => 'nullable|file|max:10240',
        ]);

        $categorie = TicketCategory::with('team')->findOrFail($data['ticket_category_id']);
        abort_unless($categorie->team?->departement_id === (int) $data['departement_id'], 422, 'La catégorie ne correspond pas au service choisi.');

        $gravite = strtolower((string) data_get($data, 'service_data.gravite'));
        $service = Departement::find($data['departement_id']);
        if ($service?->nom === 'HSE' && in_array($gravite, ['grave', 'critique'], true)) {
            $data['impact'] = 'Critique';
            $data['urgence'] = 'Critique';
        }

        $ticket = DB::transaction(function () use ($data, $request) {
            $ticket = new Ticket($data);
            $ticket->user_id = Auth::id();
            $ticket->validation_statut = $this->validationRequise($data['departement_id']) ? 'en_attente' : 'non_requise';
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

        $ticket->load(['demandeur', 'technicien', 'team.techniciens']);
        $notifiables = collect([$ticket->demandeur, $ticket->technicien])
            ->merge($ticket->team?->techniciens ?? collect())
            ->filter()
            ->unique('id');
        foreach ($notifiables as $notifiable) {
            $evenement = $notifiable->id === $ticket->assigned_to ? 'ticket_assigne' : 'ticket_cree';
            $notifiable->notify(new TicketCreatedNotification($ticket, $evenement));
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket créé : ' . $ticket->reference);
    }

    public function valider(Request $request, Ticket $ticket)
    {
        abort_unless($this->peutGerer($ticket), 403);
        abort_unless($ticket->validation_statut === 'en_attente', 422, 'Ce ticket ne nécessite pas de validation.');

        $ticket->update([
            'validation_statut' => 'valide',
            'valide_par' => Auth::id(),
            'date_validation' => now(),
        ]);
        $this->enregistrerHistorique($ticket, 'validation', 'en_attente', 'valide');

        return back()->with('success', 'Demande validée par le responsable du service.');
    }

    public function refuser(Request $request, Ticket $ticket)
    {
        abort_unless($this->peutGerer($ticket), 403);
        $data = $request->validate(['motif' => 'required|string|max:1000']);
        abort_unless($ticket->validation_statut === 'en_attente', 422, 'Ce ticket ne nécessite pas de validation.');

        $serviceData = $ticket->service_data ?? [];
        $serviceData['motif_refus'] = $data['motif'];
        $ticket->update([
            'validation_statut' => 'refuse',
            'valide_par' => Auth::id(),
            'date_validation' => now(),
            'service_data' => $serviceData,
        ]);
        $this->enregistrerHistorique($ticket, 'validation', 'en_attente', 'refuse');

        return back()->with('success', 'Demande refusée et motif enregistré.');
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

    protected function validationRequise(int $departementId): bool
    {
        return Departement::whereKey($departementId)->whereIn('nom', ['RH', 'Finance', 'HSE'])->exists();
    }

    public function show(Ticket $ticket)
    {
        abort_unless($this->peutConsulter($ticket), 403);

        $ticket->load(['comments.auteur', 'attachments.uploader', 'histories.utilisateur', 'assets', 'categorie', 'priorite', 'statut', 'technicien', 'site', 'departement', 'team']);

        $statutsMap = TicketStatus::pluck('nom', 'id');
        $techniciensMap = User::pluck('name', 'id');
        $techniciens = User::where('est_technicien', true)
            ->where('actif', true)
            ->when($ticket->team_id, fn($query) => $query->whereHas('teams', fn($teamQuery) => $teamQuery->whereKey($ticket->team_id)))
            ->orderBy('name')
            ->get(['id', 'name', 'site_id', 'disponible']);

        return view('tickets.show', compact('ticket', 'statutsMap', 'techniciensMap', 'techniciens'));
    }

    public function edit(Ticket $ticket)
    {
        abort_unless($this->peutModifier($ticket), 403);

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
            'assigned_to' => 'sometimes|nullable|exists:users,id',
        ]);

        abort_unless($this->peutModifier($ticket), 403);

        if (array_key_exists('assigned_to', $data) && ! $this->peutAffecter()) {
            abort(403);
        }

        DB::transaction(function () use ($ticket, $data) {
            $ancienAssigne = $ticket->assigned_to;
            $nouvelAssigne = array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $ancienAssigne;
            unset($data['assigned_to']);
            if (isset($data['impact']) || isset($data['urgence'])) {
                $ticket->fill($data);
                $this->priorityService->appliquerAuTicket($ticket);
            } else {
                $ticket->fill($data);
            }

            $ticket->save();

            if ($nouvelAssigne != $ancienAssigne) {
                $this->ticketWorkflowService->reaffecter($ticket->fresh(['statut']), Auth::user(), $nouvelAssigne ? (int) $nouvelAssigne : null);
            }
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket mis à jour.');
    }

    public function transition(Request $request, Ticket $ticket)
    {
        $request->validate([
            'statut' => 'required|in:nouveau,assigne,en_cours,en_attente,resolu,clos,annule',
            'motif_attente' => 'nullable|string|max:1000',
            'solution' => 'nullable|string',
            'satisfaction_note' => 'nullable|integer|min:1|max:5',
            'satisfaction_commentaire' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        abort_unless($this->peutConsulter($ticket), 403);

        try {
            $this->ticketWorkflowService->transitionner(
                $ticket->load('statut'),
                TicketStatusSlug::from($request->string('statut')->value()),
                Auth::user(),
                $request->only(['motif_attente', 'solution', 'satisfaction_note', 'satisfaction_commentaire', 'assigned_to']),
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['statut' => $exception->getMessage()]);
        }

        return back()->with('success', 'Statut du ticket mis à jour.');
    }

    // Phase 4 : Archiver / Supprimer (suppression douce)
    public function destroy(Ticket $ticket)
    {
        abort_unless($this->peutGerer($ticket) || $this->peutModifier($ticket) || Auth::user()->hasRole('admin'), 403);

        $ticket->delete();
        $this->enregistrerHistorique($ticket, 'annulation', null, null);

        return redirect()->route('tickets.index')->with('success', "Ticket {$ticket->reference} archivé avec succès.");
    }

    // Ajouter un commentaire
    public function storeComment(Request $request, Ticket $ticket)
    {
        abort_unless($this->peutConsulter($ticket), 403);

        $data = $request->validate([
            'contenu' => 'required|string',
            'interne' => 'boolean',
        ]);

        $ticket->comments()->create([
            'user_id' => Auth::id(),
            'contenu' => $data['contenu'],
            'interne' => $this->peutGerer($ticket) && ($data['interne'] ?? false),
        ]);

        if (! ($data['interne'] ?? false) && Auth::id() !== $ticket->user_id && $this->peutGerer($ticket)) {
            $this->slaService->enregistrerPremiereReponse($ticket);
        }

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

        return $this->estDansPerimetre($ticket)
            && ($user->role?->permissions()->where('slug', 'tickets.assign')->exists()
                || $user->est_technicien
                || $user->role?->slug === 'admin');
    }

    protected function peutModifier(Ticket $ticket): bool
    {
        return $ticket->user_id === Auth::id() || Auth::user()->hasRole('admin');
    }

    protected function peutAffecter(): bool
    {
        return Auth::user()->hasRole('dsi') || Auth::user()->hasRole('admin');
    }

    protected function peutConsulter(Ticket $ticket): bool
    {
        return $this->peutGerer($ticket)
            || $ticket->user_id === Auth::id()
            || ($ticket->assigned_to === Auth::id() && $this->estDansPerimetre($ticket));
    }

    protected function estDansPerimetre(Ticket $ticket): bool
    {
        $user = Auth::user();

        return $user->hasRole('admin')
            || ($user->departement_id && (int) $user->departement_id === (int) $ticket->departement_id);
    }

    public function restore(string $id)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);

        $ticket = Ticket::withTrashed()->findOrFail($id);
        $ticket->restore();

        return redirect()->route('tickets.index')->with('success', "Ticket {$ticket->reference} restauré avec succès.");
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
