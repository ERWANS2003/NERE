<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;

/**
 * Phase 6 : Affectation automatique.
 *
 * Chaîne : Catégorie -> Équipe -> Technicien
 * Stratégie de sélection : technicien disponible, le moins chargé,
 * en priorité rattaché au même site que le ticket.
 */
class AssignmentService
{
    public function affecter(Ticket $ticket, ?User $operateur = null): Ticket
    {
        $team = $ticket->categorie?->team;

        if (! $team) {
            return $ticket;
        }

        $ticket->team_id = $team->id;

        $technicien = $this->trouverTechnicien($team, $ticket->site_id);

        if ($technicien) {
            $ticket->assigned_to = $technicien->id;
            $ticket->assigned_by = $operateur?->id;
            $ticket->date_assignation = now();
        }

        return $ticket;
    }

    protected function trouverTechnicien(Team $team, ?int $siteId): ?User
    {
        $query = $team->techniciens()
            ->where('actif', true)
            ->where('disponible', true);

        // Priorité au technicien rattaché au site concerné
        if ($siteId) {
            $rattacheAuSite = (clone $query)->where('site_id', $siteId);
            if ($technicien = $this->leMoinsCharge($rattacheAuSite)) {
                return $technicien;
            }
        }

        // Sinon, le technicien disponible le moins chargé de l'équipe
        return $this->leMoinsCharge($query);
    }

    protected function leMoinsCharge($query): ?User
    {
        return $query
            ->withCount(['ticketsAssignes as tickets_ouverts_count' => function ($q) {
                $q->whereHas('statut', fn ($s) => $s->where('est_final', false));
            }])
            ->orderBy('tickets_ouverts_count')
            ->first();
    }
}
