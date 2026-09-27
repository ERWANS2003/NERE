<?php

namespace App\Services;

use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Suggestions automatiques à la création et au traitement d'un ticket.
 *
 * Toutes les requêtes passent par Ticket::visibleA() : ces suggestions sont
 * exposées en JSON à tout utilisateur connecté, donc une requête non scopée
 * y transformerait « qui traite quoi » en fuite inter-départements.
 */
class TicketIntelligenceService
{
    /**
     * Slugs de statuts considérés comme « encore ouverts ».
     * Les anciens libellés english (`new`/`open`/`in_progress`) ne
     * correspondaient à aucune colonne et renvoyaient 0 par défaut.
     */
    private const STATUTS_OUVERTS = ['nouveau', 'assigne', 'en_cours', 'en_attente'];

    /**
     * Suggère des articles de la base de connaissances.
     */
    public function suggestKnowledgeArticles(string $titre, string $description): Collection
    {
        $keywords = $this->extractKeywords($titre.' '.$description);

        if ($keywords === []) {
            return collect();
        }

        return KnowledgeArticle::where('publie', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('titre', 'LIKE', "%{$keyword}%")
                        ->orWhere('contenu', 'LIKE', "%{$keyword}%")
                        ->orWhere('mots_cles', 'ILIKE', "%{$keyword}%");
                }
            })
            ->orderByDesc('utile_count')
            ->limit(5)
            ->get();
    }

    /**
     * Suggère le meilleur assigné en s'appuyant sur l'historique du service.
     */
    public function suggestAssignee(Ticket $ticket, ?User $demandeur = null): ?User
    {
        $demandeur ??= auth()->user();

        $candidats = Ticket::visibleA($demandeur)
            ->where('ticket_category_id', $ticket->ticket_category_id)
            ->whereNotNull('assigned_to')
            ->whereHas('statut', fn ($q) => $q->where('slug', 'resolu'))
            ->with('technicien')
            ->latest('date_resolution')
            ->limit(200)
            ->get()
            ->pluck('technicien')
            ->filter();

        if ($candidats->isEmpty()) {
            return $this->getDefaultAssignee($ticket);
        }

        // Les techniciens les plus souvent formés sur cette catégorie d'abord.
        foreach ($candidats->countBy('id')->sortDesc() as $userId => $count) {
            $user = $candidats->firstWhere('id', $userId);
            if ($user && $user->estDisponiblePourAffectation()) {
                return $user;
            }
        }

        return $this->getDefaultAssignee($ticket);
    }

    /**
     * Suggère la priorité optimale à partir de mots-clés.
     *
     * @return array{slug: string, name: string, confidence: float, reason: string}
     */
    public function suggestPriority(string $titre, string $description): array
    {
        $keywords = mb_strtolower($titre.' '.$description);

        // Du plus urgent au moins urgent : le premier niveau qui matche gagne.
        $niveaux = [
            [
                'triggers' => ['urgent', 'critique', 'bloquant', 'production', 'arrêt', 'panne', 'sécurité'],
                'slug' => 'critique',
                'name' => 'Critique',
                'confidence' => 0.9,
            ],
            [
                'triggers' => ['important', 'rapidement', 'problème', 'erreur'],
                'slug' => 'haute',
                'name' => 'Haute',
                'confidence' => 0.7,
            ],
        ];

        foreach ($niveaux as $niveau) {
            foreach ($niveau['triggers'] as $trigger) {
                if (str_contains($keywords, $trigger)) {
                    return [
                        'slug' => $niveau['slug'],
                        'name' => $niveau['name'],
                        'confidence' => $niveau['confidence'],
                        'reason' => "Mot-clé détecté : « {$trigger} ».",
                    ];
                }
            }
        }

        return [
            'slug' => 'normale', 'name' => 'Normale', 'confidence' => 0.5,
            'reason' => 'Aucun mot-clé de priorité détecté.',
        ];
    }

    /**
     * Suggère une catégorie à partir de mots-clés.
     */
    public function suggestCategory(string $titre, string $description): ?array
    {
        $keywords = $this->extractKeywords($titre.' '.$description);

        if ($keywords === []) {
            return null;
        }

        $categorie = TicketCategory::where('actif', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('nom', 'ILIKE', "%{$keyword}%")
                        ->orWhere('description', 'ILIKE', "%{$keyword}%");
                }
            })
            ->first();

        if (! $categorie) {
            return null;
        }

        return [
            'id' => $categorie->id,
            'name' => $categorie->nom,
            'confidence' => 0.6,
            'reason' => 'Catégorie la plus proche des mots-clés saisis.',
        ];
    }

    /**
     * Détecte les tickets similaires non résolus (doublons possibles).
     */
    public function detectSimilarTickets(
        string $titre,
        string $description,
        ?User $demandeur = null,
    ): Collection {
        $demandeur ??= auth()->user();
        $keywords = $this->extractKeywords($titre);

        if ($keywords === []) {
            return collect();
        }

        return Ticket::visibleA($demandeur)
            ->whereHas('statut', fn ($q) => $q->whereNotIn('slug', ['resolu', 'clos', 'annule']))
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('titre', 'ILIKE', "%{$keyword}%")
                        ->orWhere('description', 'ILIKE', "%{$keyword}%");
                }
            })
            ->with('statut')
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * Analyse le sentiment du ticket.
     */
    public function analyzeSentiment(string $contenu): array
    {
        $contenu = mb_strtolower($contenu);

        $urgentWords = ['urgent', 'immédiat', 'critique', 'rapidement', 'vite'];
        $frustratedWords = ['frustré', 'agacé', 'inacceptable', 'énervé'];
        $politeWords = ['merci', 's\'il vous plaît', 'cordialement'];

        $urgentScore = 0;
        $frustrationScore = 0;
        $politenessScore = 0;

        foreach ($urgentWords as $word) {
            if (str_contains($contenu, $word)) {
                $urgentScore++;
            }
        }
        foreach ($frustratedWords as $word) {
            if (str_contains($contenu, $word)) {
                $frustrationScore++;
            }
        }
        foreach ($politeWords as $word) {
            if (str_contains($contenu, $word)) {
                $politenessScore++;
            }
        }

        return [
            'urgency' => $urgentScore > 0 ? 'high' : 'normal',
            'frustration' => match (true) {
                $frustrationScore > 1 => 'high',
                $frustrationScore > 0 => 'medium',
                default => 'low',
            },
            'politeness' => $politenessScore > 0 ? 'polite' : 'neutral',
            'recommendation' => $frustrationScore > 1
                ? 'Traiter en priorité — utilisateur frustré.'
                : null,
        ];
    }

    /**
     * Estime le temps de résolution à partir de l'historique du service.
     */
    public function estimateResolutionTime(Ticket $ticket, ?User $demandeur = null): array
    {
        $demandeur ??= auth()->user();

        $similaires = Ticket::visibleA($demandeur)
            ->where('ticket_category_id', $ticket->ticket_category_id)
            ->whereNotNull('date_resolution')
            ->whereNotNull('created_at')
            ->limit(200)
            ->get();

        if ($similaires->isEmpty()) {
            return [
                'estimated_hours' => 4,
                'confidence' => 'low',
                'reason' => 'Estimation par défaut, aucun historique comparable.',
            ];
        }

        $heures = $similaires
            ->map(fn (Ticket $t) => $t->created_at->diffInHours($t->date_resolution, absolute: true))
            ->filter()
            ->average();

        return [
            'estimated_hours' => round($heures ?: 4, 1),
            'confidence' => $similaires->count() >= 5 ? 'high' : 'medium',
            'reason' => "Basé sur {$similaires->count()} ticket(s) similaire(s) résolu(s).",
        ];
    }

    /**
     * Génère les actions recommandées pour un ticket.
     */
    public function generateRecommendedActions(Ticket $ticket, ?User $demandeur = null): array
    {
        $actions = [];

        $assigne = $this->suggestAssignee($ticket, $demandeur);
        if ($assigne) {
            $actions[] = [
                'type' => 'assign',
                'title' => 'Affecter à '.$assigne->name,
                'description' => 'Affectation proposée par l\'historique du service.',
                'data' => ['user_id' => $assigne->id],
                'confidence' => 0.8,
            ];
        }

        $articles = $this->suggestKnowledgeArticles($ticket->titre, (string) $ticket->description);
        if ($articles->isNotEmpty()) {
            $actions[] = [
                'type' => 'knowledge',
                'title' => 'Articles suggérés',
                'description' => $articles->count().' article(s) pertinent(s) trouvé(s).',
                'data' => $articles->map->only(['id', 'titre'])->all(),
                'confidence' => 0.7,
            ];
        }

        // Escalade si la priorité est au plus haut niveau de l'échelle.
        $niveauMax = (int) TicketPriority::max('niveau');
        if ($niveauMax > 0 && (int) $ticket->priorite?->niveau === $niveauMax) {
            $actions[] = [
                'type' => 'escalate',
                'title' => 'Escalader',
                'description' => 'Priorité maximale — notification du responsable.',
                'data' => ['niveau' => 2],
                'confidence' => 0.9,
            ];
        }

        return $actions;
    }

    /**
     * Charge courante d'un technicien, utilisée par le front.
     */
    public function chargeCourante(User $user): int
    {
        return Ticket::where('assigned_to', $user->id)
            ->whereHas('statut', fn ($q) => $q->whereIn('slug', self::STATUTS_OUVERTS))
            ->count();
    }

    /**
     * Extrait les mots-clés pertinents.
     */
    protected function extractKeywords(string $texte): array
    {
        $stopWords = [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'mais',
            'donc', 'car', 'pour', 'avec', 'sur', 'pas', 'plus', 'mon', 'ma', 'mes',
            'nous', 'vous', 'ils', 'elles', 'est', 'sont', 'dans', 'ce', 'cette',
        ];

        $mots = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($texte), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $keywords = array_filter($mots, fn (string $mot) => mb_strlen($mot) > 3
            && ! in_array($mot, $stopWords, true));

        return array_slice(array_values(array_unique($keywords)), 0, 5);
    }

    /**
     * Récupère l'affecté par défaut : premier technicien disponible de
     * l'équipe associée à la catégorie.
     */
    protected function getDefaultAssignee(Ticket $ticket): ?User
    {
        $teamId = $ticket->categorie?->team_id;

        if (! $teamId) {
            return null;
        }

        return User::where('actif', true)
            ->whereHas('teams', fn ($q) => $q->where('teams.id', $teamId))
            ->get()
            ->first(fn (User $u) => $u->estDisponiblePourAffectation());
    }
}
