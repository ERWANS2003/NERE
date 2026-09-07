<?php

namespace App\Services;

use App\Models\Sla;
use App\Models\Ticket;

/**
 * Gestion SLA inspirée GLPI : TTR/TTO, pause en statut « En attente »,
 * première réponse enregistrée au premier suivi public.
 */
class SlaService
{
    public function appliquer(Ticket $ticket): Ticket
    {
        $sla = Sla::where('ticket_priority_id', $ticket->ticket_priority_id)
            ->where('actif', true)
            ->where(function ($q) use ($ticket) {
                $q->whereNull('site_id')->orWhere('site_id', $ticket->site_id);
            })
            ->orderByRaw('site_id IS NULL')
            ->first();

        if (! $sla) {
            return $ticket;
        }

        $ticket->sla_id = $sla->id;
        $ticket->date_echeance_reponse = now()->addHours($sla->temps_reponse_heures);
        $ticket->date_echeance_resolution = now()->addHours($sla->temps_resolution_heures);

        return $ticket;
    }

    /** GLPI : le SLA se met en pause quand le ticket passe « En attente ». */
    public function mettreEnPause(Ticket $ticket): void
    {
        if ($ticket->date_mise_en_attente) {
            return;
        }

        $ticket->date_mise_en_attente = now();
    }

    /** Reprend le compteur SLA et décale les échéances. */
    public function reprendre(Ticket $ticket): void
    {
        if (! $ticket->date_mise_en_attente) {
            return;
        }

        $pauseSecondes = (int) $ticket->date_mise_en_attente->diffInSeconds(now());
        $ticket->sla_temps_pause_secondes += $pauseSecondes;

        if ($ticket->date_echeance_reponse) {
            $ticket->date_echeance_reponse = $ticket->date_echeance_reponse->copy()->addSeconds($pauseSecondes);
        }
        if ($ticket->date_echeance_resolution) {
            $ticket->date_echeance_resolution = $ticket->date_echeance_resolution->copy()->addSeconds($pauseSecondes);
        }

        $ticket->date_mise_en_attente = null;
    }

    public function enregistrerPremiereReponse(Ticket $ticket): void
    {
        if ($ticket->date_premiere_reponse) {
            return;
        }

        $ticket->date_premiere_reponse = now();
        $ticket->save();
    }

    public function verifierDepassement(Ticket $ticket): bool
    {
        if ($ticket->statut?->met_en_pause_sla || $ticket->date_mise_en_attente) {
            return false;
        }

        $depasse = $ticket->estEnRetard();

        if ($depasse && ! $ticket->sla_depasse) {
            $ticket->sla_depasse = true;
            $ticket->save();
        }

        return $depasse;
    }

    /** Retourne le seuil atteint une seule fois : 75 ou 100 pour cent. */
    public function verifierAlerte(Ticket $ticket): ?int
    {
        if (! $ticket->date_echeance_resolution || $ticket->statut?->est_final || $ticket->date_mise_en_attente) {
            return null;
        }

        $sla = $ticket->sla;
        if (! $sla || $sla->temps_resolution_heures <= 0) {
            return null;
        }

        $debut = $ticket->created_at->copy()->addSeconds((int) $ticket->sla_temps_pause_secondes);
        $dureeTotale = max(1, $ticket->date_echeance_resolution->diffInSeconds($debut));
        $elapsed = $debut->diffInSeconds(now());
        $pourcentage = ($elapsed / $dureeTotale) * 100;

        if ($pourcentage >= 100 && ! $ticket->sla_alerte_100_envoyee) {
            $ticket->forceFill(['sla_alerte_100_envoyee' => true, 'sla_depasse' => true])->save();
            return 100;
        }

        if ($pourcentage >= 75 && ! $ticket->sla_alerte_75_envoyee) {
            $ticket->forceFill(['sla_alerte_75_envoyee' => true])->save();
            return 75;
        }

        return null;
    }
}
