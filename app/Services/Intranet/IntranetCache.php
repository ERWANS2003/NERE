<?php

namespace App\Services\Intranet;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache applicatif de l'intranet.
 *
 * Centralise les clés, les durées de vie et surtout l'invalidation : plutôt que
 * de disperser des Cache::remember() dans les contrôleurs (avec le risque
 * d'oublier d'en vider un lors d'une écriture), tout passe par ce service. Les
 * données mises en cache sont celles qui sont coûteuses à calculer et changent
 * rarement — l'organigramme, la liste des départements, les compteurs du
 * tableau de bord.
 *
 * Invalidation : un observateur de modèle (DepartmentObserver / UserObserver)
 * appelle flushStructure() dès qu'un département ou un utilisateur change, ce
 * qui garantit qu'une donnée périmée ne survit jamais à l'écriture qui l'a
 * rendue fausse. Les compteurs à faible enjeu (tableau de bord) expirent seuls
 * au bout de quelques minutes.
 */
class IntranetCache
{
    /** Données structurelles (départements, organigramme) : 1 heure. */
    public const TTL_STRUCTURE = 3600;

    /** Compteurs du tableau de bord : 5 minutes. */
    public const TTL_STATS = 300;

    /**
     * Clés dépendant de la structure (départements/utilisateurs). Vidées
     * ensemble par flushStructure().
     *
     * @var array<int, string>
     */
    private const STRUCTURE_KEYS = [
        'intranet.organization.branches',
        'intranet.departments.active',
    ];

    /**
     * Mémorise la valeur d'une clé structurelle (TTL 1 h).
     *
     * @template T
     * @param  Closure():T  $callback
     * @return T
     */
    public function structure(string $key, Closure $callback): mixed
    {
        return Cache::remember($key, self::TTL_STRUCTURE, $callback);
    }

    /**
     * Mémorise un compteur de tableau de bord (TTL 5 min). La clé inclut
     * l'identifiant utilisateur quand le compteur est personnel.
     *
     * @template T
     * @param  Closure():T  $callback
     * @return T
     */
    public function stats(string $key, Closure $callback): mixed
    {
        return Cache::remember('intranet.stats.'.$key, self::TTL_STATS, $callback);
    }

    /**
     * Vide tout le cache structurel. Appelé lorsqu'un département ou un
     * utilisateur est créé, modifié ou supprimé.
     */
    public function flushStructure(): void
    {
        foreach (self::STRUCTURE_KEYS as $key) {
            Cache::forget($key);
        }
    }
}
