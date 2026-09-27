<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Suggestions automatiques appliquées à un ticket.
 *
 * Chaque endpoint travaille sur un ticket résolu par route model binding, donc
 * chaque action commence par authorizeTicket(). Sans cela n'importe quel
 * utilisateur connecté pouvait lire l'intelligence d'un ticket d'un autre
 * département, et executeAction() permettait de réaffecter n'importe quel
 * ticket en postant simplement son id.
 */
class TicketIntelligenceController extends Controller
{
    public function __construct(
        protected TicketIntelligenceService $intelligence
    ) {}

    /**
     * Analyse une nouvelle demande et retourne des suggestions.
     */
    public function analyzeNewTicket(Request $request)
    {
        // The create form still posts the English `title` key; accept both so
        // the endpoint can converge on the `titre` column name without
        // breaking the form mid-migration.
        if (! $request->filled('titre') && $request->filled('title')) {
            $request->merge(['titre' => $request->input('title')]);
        }

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $titre = $validated['titre'];
        $description = $validated['description'];

        return response()->json([
            'success' => true,
            'suggestions' => [
                'priority' => $this->intelligence->suggestPriority($titre, $description),
                'category' => $this->intelligence->suggestCategory($titre, $description),
                'knowledge_articles' => $this->intelligence
                    ->suggestKnowledgeArticles($titre, $description)
                    ->map(fn ($article) => [
                        'id' => $article->id,
                        'titre' => $article->titre,
                        'url' => route('knowledge.show', $article),
                    ])->values(),
                'similar_tickets' => $this->intelligence
                    ->detectSimilarTickets($titre, $description, $user)
                    ->map(fn (Ticket $ticket) => [
                        'id' => $ticket->id,
                        'reference' => $ticket->reference,
                        'titre' => $ticket->titre,
                        'status' => $ticket->statut?->nom ?? '—',
                        'url' => route('tickets.show', $ticket),
                    ])->values(),
                'sentiment' => $this->intelligence->analyzeSentiment($titre.' '.$description),
            ],
        ]);
    }

    /**
     * Suggère un affecté pour un ticket.
     */
    public function suggestAssignee(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);
        $this->authorizeAssignment();

        $assignee = $this->intelligence->suggestAssignee($ticket, $request->user());

        if (! $assignee) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun technicien disponible pour cette catégorie.',
            ]);
        }

        return response()->json([
            'success' => true,
            'assignee' => [
                'id' => $assignee->id,
                'name' => $assignee->name,
                'email' => $assignee->email,
                'current_load' => $this->intelligence->chargeCourante($assignee),
            ],
        ]);
    }

    /**
     * Estime le temps de résolution.
     */
    public function estimateResolutionTime(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        return response()->json([
            'success' => true,
            'estimation' => $this->intelligence->estimateResolutionTime($ticket, $request->user()),
        ]);
    }

    /**
     * Génère les actions recommandées.
     */
    public function getRecommendedActions(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        return response()->json([
            'success' => true,
            'actions' => $this->intelligence->generateRecommendedActions($ticket, $request->user()),
        ]);
    }

    /**
     * Exécute une action recommandée.
     */
    public function executeAction(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        $validated = $request->validate([
            'action_type' => ['required', 'string', 'in:assign,escalate,knowledge,notify'],
            'action_data' => ['required', 'array'],
            'action_data.user_id' => ['required_with:action_type', 'integer', 'exists:users,id'],
            'action_data.article_id' => ['required_if:action_type,knowledge', 'integer', 'exists:knowledge_articles,id'],
        ]);

        // L'affectation est réservée : la suggérer est sans danger, l'appliquer
        // ne l'est pas.
        if ($validated['action_type'] === 'assign') {
            $this->authorizeAssignment();
        }

        try {
            $result = match ($validated['action_type']) {
                'assign' => $this->executeAssignAction($ticket, $validated['action_data']),
                'escalate' => $this->executeEscalateAction($ticket, $validated['action_data']),
                'knowledge' => $this->executeLinkKnowledgeAction($request, $ticket, $validated['action_data']),
                'notify' => $this->executeNotifyAction($ticket, $validated['action_data']),
            };
        } catch (\Throwable $e) {
            Log::warning('Ticket intelligence action failed', [
                'ticket_id' => $ticket->id,
                'action' => $validated['action_type'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "L'action n'a pas pu être appliquée.",
            ], 422);
        }

        return response()->json($result);
    }

    /**
     * Affecte le ticket via le workflow, pas via un update brut : l'historique
     * et les effets de bord SLA sont produit par le service.
     */
    protected function executeAssignAction(Ticket $ticket, array $data): array
    {
        $ticket->loadMissing('statut');

        app(\App\Services\TicketWorkflowService::class)->reaffecter(
            $ticket,
            auth()->user(),
            (int) $data['user_id'],
        );

        return [
            'success' => true,
            'message' => 'Ticket affecté.',
        ];
    }

    protected function executeEscalateAction(Ticket $ticket, array $data): array
    {
        $ticket->comments()->create([
            'user_id' => auth()->id(),
            'contenu' => 'Ticket escaladé (niveau '.($data['niveau'] ?? 1).').',
            'interne' => true,
        ]);

        return [
            'success' => true,
            'message' => 'Ticket escaladé.',
        ];
    }

    protected function executeLinkKnowledgeAction(Request $request, Ticket $ticket, array $data): array
    {
        $article = \App\Models\KnowledgeArticle::findOrFail($data['article_id']);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'contenu' => 'Article de la base de connaissances lié : '.$article->titre,
            'interne' => false,
        ]);

        return [
            'success' => true,
            'message' => 'Article lié au ticket.',
        ];
    }

    protected function executeNotifyAction(Ticket $ticket, array $data): array
    {
        $ticket->comments()->create([
            'user_id' => auth()->id(),
            'contenu' => 'Notification envoyée à '.($data['destinataire'] ?? 'l\'équipe').'.',
            'interne' => true,
        ]);

        return [
            'success' => true,
            'message' => 'Notification enregistrée.',
        ];
    }
}
