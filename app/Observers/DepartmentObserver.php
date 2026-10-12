<?php

namespace App\Observers;

use App\Models\Department;
use App\Services\Intranet\IntranetCache;

/**
 * Invalide le cache structurel de l'intranet (organigramme, liste des
 * départements, compteurs) dès qu'un département change. Sans cela, une
 * modification resterait invisible jusqu'à l'expiration naturelle du cache.
 */
class DepartmentObserver
{
    public function __construct(private readonly IntranetCache $cache)
    {
    }

    public function saved(Department $department): void
    {
        $this->cache->flushStructure();
    }

    public function deleted(Department $department): void
    {
        $this->cache->flushStructure();
    }
}
