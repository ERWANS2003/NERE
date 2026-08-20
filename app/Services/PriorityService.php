<?php

namespace App\Services;

use App\Models\PriorityMatrix;
use App\Models\Ticket;
use App\Models\TicketPriority;

/**
 * Phase 5 : Calcul automatique des priorités.
 *
 * La matrice Impact x Urgence est stockée en base (table priority_matrices)
 * afin d'être configurable depuis l'espace Administration sans toucher au code.
 */
class PriorityService
{
    public function calculer(string $impact, string $urgence): ?TicketPriority
    {
        $entree = PriorityMatrix::where('impact', $impact)
            ->where('urgence', $urgence)
            ->first();

        if ($entree) {
            return $entree->priorite;
        }

        // Repli si aucune règle configurée : priorité par défaut basée sur le niveau le plus élevé
        return $this->reglesParDefaut($impact, $urgence);
    }

    public function appliquerAuTicket(Ticket $ticket): Ticket
    {
        $priorite = $this->calculer($ticket->impact, $ticket->urgence);

        if ($priorite) {
            $ticket->ticket_priority_id = $priorite->id;
        }

        return $ticket;
    }

    protected function reglesParDefaut(string $impact, string $urgence): ?TicketPriority
    {
        // Exemple équivalent au code métier donné dans le cahier des charges :
        // if ($impact == "Critique" && $urgence == "Critique") { $priorite = "Critique"; }
        if ($impact === 'Critique' && $urgence === 'Critique') {
            return TicketPriority::where('nom', 'Critique')->first();
        }

        $niveaux = ['Faible' => 1, 'Moyen' => 2, 'Élevé' => 3, 'Critique' => 4];
        $score = max($niveaux[$impact] ?? 1, $niveaux[$urgence] ?? 1);

        return TicketPriority::where('niveau', $score)->first()
            ?? TicketPriority::orderByDesc('niveau')->first();
    }
}
