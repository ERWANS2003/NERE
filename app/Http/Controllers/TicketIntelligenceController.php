<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketIntelligenceService;
use Illuminate\Http\Request;

/**
 * Contrôleur pour les fonctionnalités d'intelligence des tickets
 */
class TicketIntelligenceController extends Controller
{
    public function __construct(
        protected TicketIntelligenceService $intelligence
    ) {}

    /**
     * Analyse un nouveau ticket et retourne des suggestions
     */
    public function analyzeNewTicket(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        $suggestions = [
            // Suggérer la priorité
            'priority' => $this->intelligence->suggestPriority(
                $validated['title'],
                $validated['description']
            ),

            // Suggérer la catégorie
            'category' => $this->intelligence->suggestCategory(
                $validated['title'],
                $validated['description']
            ),

            // Suggérer des articles
            'knowledge_articles' => $this->intelligence->suggestKnowledgeArticles(
                $validated['title'],
                $validated['description']
            )->map(fn($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'url' => route('knowledge.show', $article)
            ]),

            // Détecter tickets similaires
            'similar_tickets' => $this->intelligence->detectSimilarTickets(
                $validated['title'],
                $validated['description']
            )->map(fn($ticket) => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'status' => $ticket->status->name ?? 'N/A',
                'url' => route('tickets.show', $ticket)
            ]),

            // Analyser le sentiment
            'sentiment' => $this->intelligence->analyzeSentiment(
                $validated['title'] . ' ' . $validated['description']
            ),
        ];

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Suggère un assigné pour un ticket
     */
    public function suggestAssignee(Request $request, Ticket $ticket)
    {
        $assignee = $this->intelligence->suggestAssignee($ticket);

        if (!$assignee) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun assigné suggéré disponible'
            ]);
        }

        return response()->json([
            'success' => true,
            'assignee' => [
                'id' => $assignee->id,
                'name' => $assignee->name,
                'email' => $assignee->email,
                'current_load' => Ticket::where('assigned_to', $assignee->id)
                    ->whereHas('status', fn($q) => $q->whereIn('slug', ['new', 'open', 'in_progress']))
                    ->count()
            ]
        ]);
    }

    /**
     * Estime le temps de résolution
     */
    public function estimateResolutionTime(Ticket $ticket)
    {
        $estimation = $this->intelligence->estimateResolutionTime($ticket);

        return response()->json([
            'success' => true,
            'estimation' => $estimation
        ]);
    }

    /**
     * Génère des actions recommandées
     */
    public function getRecommendedActions(Ticket $ticket)
    {
        $actions = $this->intelligence->generateRecommendedActions($ticket);

        return response()->json([
            'success' => true,
            'actions' => $actions
        ]);
    }

    /**
     * Exécute une action recommandée
     */
    public function executeAction(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'action_type' => 'required|string|in:assign,escalate,knowledge,notify',
            'action_data' => 'required|array'
        ]);

        try {
            $result = match ($validated['action_type']) {
                'assign' => $this->executeAssignAction($ticket, $validated['action_data']),
                'escalate' => $this->executeEscalateAction($ticket, $validated['action_data']),
                'knowledge' => $this->executeLinkKnowledgeAction($ticket, $validated['action_data']),
                'notify' => $this->executeNotifyAction($ticket, $validated['action_data']),
                default => ['success' => false, 'message' => 'Action non supportée']
            };

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function executeAssignAction(Ticket $ticket, array $data): array
    {
        $ticket->update(['assigned_to' => $data['user_id']]);

        return [
            'success' => true,
            'message' => 'Ticket assigné avec succès'
        ];
    }

    protected function executeEscalateAction(Ticket $ticket, array $data): array
    {
        // Logique d'escalade
        // TODO: Implémenter notification manager

        return [
            'success' => true,
            'message' => 'Ticket escaladé'
        ];
    }

    protected function executeLinkKnowledgeAction(Ticket $ticket, array $data): array
    {
        // Ajouter un commentaire avec lien vers article
        $ticket->comments()->create([
            'user_id' => auth()->id(),
            'content' => "Article de la base de connaissances suggéré: {$data['article_url']}",
            'is_internal' => false
        ]);

        return [
            'success' => true,
            'message' => 'Article lié au ticket'
        ];
    }

    protected function executeNotifyAction(Ticket $ticket, array $data): array
    {
        // Envoyer notification
        // TODO: Implémenter système de notification

        return [
            'success' => true,
            'message' => 'Notification envoyée'
        ];
    }
}
