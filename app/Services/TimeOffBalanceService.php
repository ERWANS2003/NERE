<?php

namespace App\Services;

use App\Models\TimeOffBalance;
use App\Models\TimeOffRequest;
use Illuminate\Support\Carbon;

/**
 * Règles de solde des congés, partagées par la demande (employé) et
 * l'approbation (manager) pour qu'elles ne divergent plus.
 */
class TimeOffBalanceService
{
    /**
     * Jours encore réservables : solde restant moins les demandes déjà en
     * attente sur la même politique et la même année. Null si aucun solde
     * n'est défini pour cette politique (pas de plafond).
     */
    public function availableDays(int $userId, int $policyId, int $year, ?int $ignoreRequestId = null): ?float
    {
        $balance = TimeOffBalance::firstWhere([
            'user_id' => $userId,
            'policy_id' => $policyId,
            'year' => $year,
        ]);

        if ($balance === null) {
            return null;
        }

        $pending = (float) TimeOffRequest::query()
            ->where('user_id', $userId)
            ->where('policy_id', $policyId)
            ->where('status', 'pending')
            ->whereYear('start_date', $year)
            ->when($ignoreRequestId, fn ($query) => $query->whereKeyNot($ignoreRequestId))
            ->sum('total_days');

        return (float) $balance->remaining_days - $pending;
    }

    /**
     * Une demande en attente ou approuvée chevauche-t-elle déjà ces dates ?
     */
    public function overlaps(int $userId, Carbon $start, Carbon $end): bool
    {
        return TimeOffRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();
    }

    /**
     * Décompte les jours d'une demande approuvée sur le solde de l'année de
     * début. Doit être appelé dans une transaction : la ligne de solde est
     * verrouillée pour que deux approbations simultanées ne se chevauchent pas.
     *
     * @return bool false si le solde est insuffisant (rien n'est modifié)
     */
    public function consume(TimeOffRequest $request): bool
    {
        $balance = TimeOffBalance::query()
            ->where('user_id', $request->user_id)
            ->where('policy_id', $request->policy_id)
            ->where('year', $request->start_date->year)
            ->lockForUpdate()
            ->first();

        if ($balance === null) {
            return true; // pas de solde défini : rien à décompter
        }

        if ((float) $request->total_days > (float) $balance->remaining_days) {
            return false;
        }

        $balance->used_days = (float) $balance->used_days + (float) $request->total_days;
        $balance->remaining_days = (float) $balance->total_days
            + (float) $balance->carry_over_days
            - (float) $balance->used_days;
        $balance->save();

        return true;
    }
}
