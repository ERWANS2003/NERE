<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatusSlug;
use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Notifications\TicketCreatedNotification;
use App\Services\TicketWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function __construct(protected TicketWorkflowService $workflow) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = $this->visibleQuery($request->user())
            ->with(['demandeur', 'categorie', 'priorite', 'statut', 'technicien', 'site'])
            ->when($request->filled('q'), fn($query) => $query->recherche($request->string('q')->value()))
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:incident,demande'],
            'departement_id' => ['required', 'exists:departements,id'],
            'ticket_category_id' => ['required', 'exists:ticket_categories,id'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'impact' => ['required', 'in:Faible,Moyen,Élevé,Critique'],
            'urgence' => ['required', 'in:Faible,Moyen,Élevé,Critique'],
            'service_data' => ['nullable', 'array'],
        ]);

        $category = TicketCategory::with('team')->findOrFail($data['ticket_category_id']);
        abort_unless($category->team?->departement_id === (int) $data['departement_id'], 422, 'La catégorie ne correspond pas au service choisi.');

        $ticket = DB::transaction(function () use ($data, $request) {
            $ticket = new Ticket($data);
            $ticket->user_id = $request->user()->id;
            $ticket->validation_statut = in_array(Departement::find($data['departement_id'])?->nom, ['RH', 'Finance', 'HSE'], true)
                ? 'en_attente'
                : 'non_requise';

            return $this->workflow->appliquerApresCreation($ticket, $request->user());
        });

        $ticket->load(['demandeur', 'technicien', 'team.techniciens']);
        collect([$ticket->demandeur, $ticket->technicien])
            ->merge($ticket->team?->techniciens ?? collect())
            ->filter()
            ->unique('id')
            ->each(fn($user) => $user->notify(new TicketCreatedNotification(
                $ticket,
                $user->id === $ticket->assigned_to ? 'ticket_assigne' : 'ticket_cree',
            )));

        return response()->json($ticket->load(['demandeur', 'categorie', 'priorite', 'statut', 'site']), 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($this->visibleQuery($request->user())->whereKey($ticket->id)->exists(), 403);

        return response()->json($ticket->load([
            'demandeur',
            'categorie',
            'priorite',
            'statut',
            'technicien',
            'site',
            'departement',
            'comments.auteur',
            'attachments.uploader',
            'histories.utilisateur',
        ]));
    }

    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->hasRole('admin'), 403);
        $ticket->update($request->validate([
            'titre' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'impact' => ['sometimes', 'in:Faible,Moyen,Élevé,Critique'],
            'urgence' => ['sometimes', 'in:Faible,Moyen,Élevé,Critique'],
        ]));

        return response()->json($ticket->fresh()->load(['categorie', 'priorite', 'statut']));
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->hasRole('admin'), 403);
        $ticket->delete();

        return response()->json(['message' => 'Ticket archivé.']);
    }

    public function comment(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($this->visibleQuery($request->user())->whereKey($ticket->id)->exists(), 403);
        $data = $request->validate(['contenu' => ['required', 'string'], 'interne' => ['boolean']]);
        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'contenu' => $data['contenu'],
            'interne' => $request->user()->est_technicien && ($data['interne'] ?? false),
        ]);

        return response()->json($comment->load('auteur'), 201);
    }

    public function attachment(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($this->visibleQuery($request->user())->whereKey($ticket->id)->exists(), 403);
        $request->validate(['fichier' => ['required', 'file', 'max:10240']]);
        $file = $request->file('fichier');
        $attachment = $ticket->attachments()->create([
            'nom_original' => $file->getClientOriginalName(),
            'chemin' => $file->store("tickets/{$ticket->id}", 'public'),
            'type_mime' => $file->getClientMimeType(),
            'taille' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json($attachment, 201);
    }

    public function transition(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($this->visibleQuery($request->user())->whereKey($ticket->id)->exists(), 403);
        $data = $request->validate(['statut' => ['required', 'in:nouveau,assigne,en_cours,en_attente,resolu,clos,annule']]);
        $updated = $this->workflow->transitionner($ticket->load('statut'), TicketStatusSlug::from($data['statut']), $request->user(), $request->only(['assigned_to', 'solution', 'motif_attente']));

        return response()->json($updated);
    }

    private function visibleQuery($user)
    {
        return Ticket::query()->where(function ($query) use ($user) {
            $query->where('user_id', $user->id);
            if ($user->hasRole('admin')) {
                $query->orWhereNotNull('tickets.id');
            } elseif ($user->departement_id && ! $user->hasRole('demandeur')) {
                $query->orWhere('departement_id', $user->departement_id);
            }
        });
    }
}
