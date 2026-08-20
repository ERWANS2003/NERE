<?php

namespace App\Enums;

/**
 * Statuts alignés sur le cycle de vie GLPI / ITIL.
 */
enum TicketStatusSlug: string
{
    case Nouveau = 'nouveau';
    case Assigne = 'assigne';
    case EnCours = 'en_cours';
    case EnAttente = 'en_attente';
    case Resolu = 'resolu';
    case Clos = 'clos';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::Assigne => 'En cours (assigné)',
            self::EnCours => 'En cours',
            self::EnAttente => 'En attente',
            self::Resolu => 'Résolu',
            self::Clos => 'Clos',
            self::Annule => 'Annulé',
        };
    }

    public function metEnPauseSla(): bool
    {
        return $this === self::EnAttente;
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::Clos, self::Annule], true);
    }
}
