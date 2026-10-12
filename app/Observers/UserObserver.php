<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Intranet\IntranetCache;

/**
 * Invalide le cache structurel quand un utilisateur change : l'organigramme
 * affiche les effectifs par département et le compteur d'employés actifs, qui
 * deviennent faux dès qu'un compte est créé, désactivé ou rattaché ailleurs.
 *
 * On ne réagit qu'aux changements qui comptent pour les vues en cache (statut
 * actif, rattachement, niveau hiérarchique, nom) afin de ne pas vider le cache
 * à chaque mise à jour anodine (ex. date de dernière connexion).
 */
class UserObserver
{
    /** Colonnes dont la modification doit invalider le cache structurel. */
    private const WATCHED = ['actif', 'org_level', 'manager_id', 'name'];

    public function __construct(private readonly IntranetCache $cache)
    {
    }

    public function created(User $user): void
    {
        $this->cache->flushStructure();
    }

    public function updated(User $user): void
    {
        foreach (self::WATCHED as $colonne) {
            if ($user->wasChanged($colonne)) {
                $this->cache->flushStructure();

                return;
            }
        }
    }

    public function deleted(User $user): void
    {
        $this->cache->flushStructure();
    }
}
