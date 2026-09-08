<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\KnowledgeArticle;
use App\Models\TicketCategory;
use Illuminate\Support\Collection;

/**
 * Service d'Intelligence pour les Tickets
 * Fournit des suggestions automatiques et des actions intelligentes
 */
class TicketIntelligenceService
{
    /**
     * Suggère des articles de la base de connaissances
     */
    public function suggestKnowledgeArticles(string $title, string $description): Collection
    {
        $keywords = $this->extractKeywords($title . ' ' . $description);
        
        return KnowledgeArticle::where('status', 'published')
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'LIKE', "%{$keyword}%")
                          ->orWhere('content', 'LIKE', "%{$keyword}%");
                }
            })
            ->limit(5)
            ->get();
    }

    /**
     * Suggère le meilleur assigné basé sur l'historique
     */
    public function suggestAssignee(Ticket $ticket): ?User
    {
        // 1. Chercher les tickets similaires résolus avec succès
        $similarTickets = Ticket::where('category_id', $ticket->category_id)
            ->whereNotNull('assigned_to')
            ->whereHas('status', fn($q) => $q->where('slug', 'resolved'))
            ->get();

        if ($similarTickets->isEmpty()) {
            return $this->getDefaultAssignee($ticket);
        }

        // 2. Compter les résolutions par technicien
        $assigneeCounts = $similarTickets->groupBy('assigned_to')
            ->map->count()
            ->sortDesc();

        // 3. Vérifier la disponibilité (charge actuelle)
        foreach ($assigneeCounts->keys() as $userId) {
            $user = User::find($userId);
            if ($user && $this->isUserAvailable($user)) {
                return $user;
            }
        }

        return $this->getDefaultAssignee($ticket);
    }

    /**
     * Suggère la priorité optimale
     */
    public function suggestPriority(string $title, string $description): array
    {
        $keywords = strtolower($title . ' ' . $description);
        
        // Mots-clés critiques
        $criticalKeywords = ['urgent', 'critique', 'bloquant', 'production', 'arrêt', 'panne', 'sécurité'];
        $highKeywords = ['important', 'rapidement', 'problème', 'erreur'];
        
        foreach ($criticalKeywords as $keyword) {
            if (str_contains($keywords, $keyword)) {
                return [
                    'slug' => 'critical',
                    'name' => 'Critique',
                    'confidence' => 0.9,
                    'reason' => "Détecté mot-clé critique: '{$keyword}'"
                ];
            }
        }
        
        foreach ($highKeywords as $keyword) {
            if (str_contains($keywords, $keyword)) {
                return [
                    'slug' => 'high',
                    'name' => 'Haute',
                    'confidence' => 0.7,
                    'reason' => "Détecté mot-clé haute priorité: '{$keyword}'"
                ];
            }
        }
        
        return [
            'slug' => 'normal',
            'name' => 'Normal',
            'confidence' => 0.5,
            'reason' => 'Priorité par défaut'
        ];
    }

    /**
     * Suggère une catégorie basée sur le contenu
     */
    public function suggestCategory(string $title, string $description): ?TicketCategory
    {
        $content = strtolower($title . ' ' . $description);
        
        // Mapping mots-clés -> catégories
        $categoryKeywords = [
            'Matériel / Équipement' => ['ordinateur', 'écran', 'clavier', 'souris', 'imprimante', 'matériel', 'équipement'],
            'Logiciel / Application' => ['application', 'logiciel', 'programme', 'excel', 'word', 'email'],
            'Réseau / Connexion' => ['réseau', 'internet', 'wifi', 'connexion', 'vpn'],
            'Sécurité' => ['sécurité', 'virus', 'malware', 'accès', 'mot de passe', 'authentification'],
            'Compte / Accès' => ['compte', 'accès', 'permissions', 'droits', 'utilisateur'],
        ];
        
        foreach ($categoryKeywords as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($content, $keyword)) {
                    return TicketCategory::where('name', 'LIKE', "%{$categoryName}%")->first();
                }
            }
        }
        
        return null;
    }

    /**
     * Détecte les tickets similaires non résolus (possible duplicata)
     */
    public function detectSimilarTickets(string $title, string $description): Collection
    {
        $keywords = $this->extractKeywords($title);
        
        return Ticket::whereDoesntHave('status', fn($q) => $q->whereIn('slug', ['resolved', 'closed']))
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'LIKE', "%{$keyword}%")
                          ->orWhere('description', 'LIKE', "%{$keyword}%");
                }
            })
            ->limit(5)
            ->get();
    }

    /**
     * Analyse le sentiment du ticket
     */
    public function analyzeSentiment(string $content): array
    {
        $content = strtolower($content);
        
        $urgentWords = ['urgent', 'immédiat', 'critique', 'rapidement', 'vite'];
        $frustratedWords = ['frustré', 'agacé', 'inacceptable', 'énervé'];
        $politeWords = ['merci', 's\'il vous plaît', 'cordialement'];
        
        $urgentScore = 0;
        $frustrationScore = 0;
        $politenessScore = 0;
        
        foreach ($urgentWords as $word) {
            if (str_contains($content, $word)) $urgentScore++;
        }
        
        foreach ($frustratedWords as $word) {
            if (str_contains($content, $word)) $frustrationScore++;
        }
        
        foreach ($politeWords as $word) {
            if (str_contains($content, $word)) $politenessScore++;
        }
        
        return [
            'urgency' => $urgentScore > 0 ? 'high' : 'normal',
            'frustration' => $frustrationScore > 1 ? 'high' : ($frustrationScore > 0 ? 'medium' : 'low'),
            'politeness' => $politenessScore > 0 ? 'polite' : 'neutral',
            'recommendation' => $frustrationScore > 1 ? 'Traiter en priorité - utilisateur frustré' : null
        ];
    }

    /**
     * Estime le temps de résolution
     */
    public function estimateResolutionTime(Ticket $ticket): array
    {
        // Chercher tickets similaires résolus
        $similarResolved = Ticket::where('category_id', $ticket->category_id)
            ->whereNotNull('resolved_at')
            ->get();
        
        if ($similarResolved->isEmpty()) {
            return [
                'estimated_hours' => 4,
                'confidence' => 'low',
                'reason' => 'Estimation par défaut (pas d\'historique)'
            ];
        }
        
        // Calculer le temps moyen
        $times = $similarResolved->map(function ($t) {
            return $t->created_at->diffInHours($t->resolved_at);
        });
        
        $avgHours = round($times->average(), 1);
        
        return [
            'estimated_hours' => $avgHours,
            'confidence' => 'high',
            'reason' => "Basé sur {$similarResolved->count()} tickets similaires résolus"
        ];
    }

    /**
     * Génère des actions recommandées
     */
    public function generateRecommendedActions(Ticket $ticket): array
    {
        $actions = [];
        
        // Action 1: Assignation
        $suggestedAssignee = $this->suggestAssignee($ticket);
        if ($suggestedAssignee) {
            $actions[] = [
                'type' => 'assign',
                'title' => 'Assigner automatiquement',
                'description' => "Assigner à {$suggestedAssignee->name}",
                'data' => ['user_id' => $suggestedAssignee->id],
                'confidence' => 0.8
            ];
        }
        
        // Action 2: Articles de connaissance
        $articles = $this->suggestKnowledgeArticles($ticket->title, $ticket->description);
        if ($articles->isNotEmpty()) {
            $actions[] = [
                'type' => 'knowledge',
                'title' => 'Articles suggérés',
                'description' => "{$articles->count()} article(s) pertinent(s) trouvé(s)",
                'data' => $articles->pluck('title', 'id')->toArray(),
                'confidence' => 0.7
            ];
        }
        
        // Action 3: Escalade si priorité critique
        if ($ticket->priority && $ticket->priority->slug === 'critical') {
            $actions[] = [
                'type' => 'escalate',
                'title' => 'Escalader',
                'description' => 'Ticket critique - notifier le manager',
                'data' => ['level' => 2],
                'confidence' => 0.9
            ];
        }
        
        return $actions;
    }

    /**
     * Extrait les mots-clés pertinents
     */
    protected function extractKeywords(string $text): array
    {
        // Supprimer les mots vides (stop words)
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'mais', 'donc', 'car', 'pour'];
        
        $words = str_word_count(strtolower($text), 1, 'àâäéèêëïîôùûüÿç');
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return array_unique(array_slice($keywords, 0, 5));
    }

    /**
     * Vérifie si un utilisateur est disponible
     */
    protected function isUserAvailable(User $user): bool
    {
        // Compter les tickets ouverts assignés
        $openTickets = Ticket::where('assigned_to', $user->id)
            ->whereHas('status', fn($q) => $q->whereIn('slug', ['new', 'open', 'in_progress']))
            ->count();
        
        // Maximum 10 tickets ouverts par technicien
        return $openTickets < 10;
    }

    /**
     * Récupère l'assigné par défaut pour une catégorie
     */
    protected function getDefaultAssignee(Ticket $ticket): ?User
    {
        // Chercher dans l'équipe par défaut de la catégorie
        if ($ticket->category && $ticket->category->default_team_id) {
            return User::whereHas('teams', function ($q) use ($ticket) {
                $q->where('teams.id', $ticket->category->default_team_id);
            })->first();
        }
        
        return null;
    }
}
