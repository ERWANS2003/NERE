<?php

namespace App\Services;

use App\Enums\TicketStatusSlug;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Cycle de vie des tickets inspiré de GLPI / ITIL.
 *
 * Nouveau → En cours (assigné) → En cours → Résolu → Clos
 *                    ↘ En attente ↗        ↘ Réouverture
 */
class TicketWorkflowService
{
    /** @var array<string, array<string>> */
    protected array $transitions = [
        'nouveau' => ['assigne', 'en_attente', 'annule'],
        'assigne' => ['en_cours', 'en_attente', 'resolu', 'annule'],
        'en_cours' => ['en_attente', 'resolu', 'annule'],
        'en_attente' => ['assigne', 'en_cours', 'annule'],
        'resolu' => ['clos', 'en_cours'],
        'clos' => ['en_cours'],
        'annule' => [],
    ];

    public function __construct(
        protected SlaService $slaService,
        protected AssignmentService $assignmentService,
        protected PriorityService $priorityService,
    ) {}

    public function statutParSlug(TicketStatusSlug $slug): TicketStatus
    {
        return TicketStatus::where('slug', $slug->value)->firstOrFail();
    }

    public function peutTransitionner(Ticket $ticket, TicketStatusSlug $vers, User $user): bool
    {
        if ($ticket->statut?->est_final) {
            return $vers === TicketStatusSlug::EnCours && $this->peutReouvrir($user);
        }

        $depuis = $ticket->statut?->slug;
        if (! $depuis || ! in_array($vers->value, $this->transitions[$depuis] ?? [], true)) {
            return false;
        }

        return match ($vers) {
            TicketStatusSlug::Assigne => $this->peutAffecter($user),
            TicketStatusSlug::EnCours => $this->peutTraiter($ticket, $user),
            TicketStatusSlug::EnAttente => $this->peutTraiter($ticket, $user),
            TicketStatusSlug::Resolu => $this->peutTraiter($ticket, $user),
            TicketStatusSlug::Clos => $this->peutCloturer($ticket, $user),
            TicketStatusSlug::Annule => $this->peutAnnuler($ticket, $user),
            default => true,
        };
    }

    public function transitionner(
        Ticket $ticket,
        TicketStatusSlug $vers,
        User $user,
        array $contexte = [],
    ): Ticket {
        if (! $this->peutTransitionner($ticket, $vers, $user)) {
            throw new InvalidArgumentException('Transition de statut non autorisée.');
        }

        $ancienStatutId = $ticket->ticket_status_id;
        $nouveauStatut = $this->statutParSlug($vers);

        match ($vers) {
            TicketStatusSlug::Assigne => $this->appliquerAssignation($ticket, $user, $contexte),
            TicketStatusSlug::EnCours => $this->appliquerPriseEnCharge($ticket, $user),
            TicketStatusSlug::EnAttente => $this->appliquerMiseEnAttente($ticket, $contexte),
            TicketStatusSlug::Resolu => $this->appliquerResolution($ticket, $contexte),
            TicketStatusSlug::Clos => $this->appliquerCloture($ticket, $user, $contexte),
            TicketStatusSlug::Annule => $this->appliquerAnnulation($ticket),
            default => null,
        };

        if ($vers !== TicketStatusSlug::Assigne || ! isset($contexte['assigned_to'])) {
            $ticket->ticket_status_id = $nouveauStatut->id;
        }

        $ticket->save();

        if ($ancienStatutId !== $ticket->ticket_status_id) {
            $this->historique($ticket, 'changement_statut', (string) $ancienStatutId, (string) $ticket->ticket_status_id);
        }

        return $ticket->fresh(['statut', 'technicien', 'priorite']);
    }

    /** Actions disponibles dans l'interface (libellés GLPI). */
    public function actionsDisponibles(Ticket $ticket, User $user): array
    {
        $actions = [];
        $slug = $ticket->statut?->slug;

        if (! $slug) {
            return $actions;
        }

        foreach ($this->transitions[$slug] ?? [] as $cible) {
            $enum = TicketStatusSlug::from($cible);
            if (! $this->peutTransitionner($ticket, $enum, $user)) {
                continue;
            }

            $actions[$cible] = match ($enum) {
                TicketStatusSlug::Assigne => ['label' => 'Affecter', 'methode' => 'assigner', 'style' => 'secondaire'],
                TicketStatusSlug::EnCours => ['label' => $slug === 'resolu' || $slug === 'clos' ? 'Réouvrir' : 'Prendre en charge', 'methode' => 'prendre_en_charge', 'style' => 'primaire'],
                TicketStatusSlug::EnAttente => ['label' => 'Mettre en attente', 'methode' => 'mettre_en_attente', 'style' => 'secondaire'],
                TicketStatusSlug::Resolu => ['label' => 'Marquer résolu', 'methode' => 'resoudre', 'style' => 'primaire'],
                TicketStatusSlug::Clos => ['label' => 'Clore le ticket', 'methode' => 'clore', 'style' => 'primaire'],
                TicketStatusSlug::Annule => ['label' => 'Annuler', 'methode' => 'annuler', 'style' => 'danger'],
            };
        }

        return $actions;
    }

    /** À la création : assignation auto → statut « En cours (assigné) » comme GLPI. */
    public function appliquerApresCreation(Ticket $ticket, User $auteur): Ticket
    {
        $ticket->ticket_status_id = $this->statutParSlug(TicketStatusSlug::Nouveau)->id;

        $this->priorityService->appliquerAuTicket($ticket);
        $this->assignmentService->affecter($ticket, $auteur);
        $this->slaService->appliquer($ticket);

        if ($ticket->assigned_to) {
            $ticket->ticket_status_id = $this->statutParSlug(TicketStatusSlug::Assigne)->id;
        }

        $ticket->save();

        $this->historique($ticket, 'creation', null, $ticket->reference);

        if ($ticket->assigned_to) {
            $this->historique($ticket, 'affectation', null, (string) $ticket->assigned_to);
            $this->historique(
                $ticket,
                'changement_statut',
                (string) $this->statutParSlug(TicketStatusSlug::Nouveau)->id,
                (string) $ticket->ticket_status_id,
            );
        }

        return $ticket;
    }

    public function reaffecter(Ticket $ticket, User $operateur, ?int $technicienId): Ticket
    {
        if (! $this->peutAffecter($operateur)) {
            throw new InvalidArgumentException('Droit d\'affectation insuffisant.');
        }

        if ($ticket->statut?->est_final) {
            throw new InvalidArgumentException('Impossible d\'affecter un ticket clos ou annulé.');
        }

        $ancien = $ticket->assigned_to;

        if ($technicienId && $ticket->team_id && ! User::findOrFail($technicienId)->teams()->whereKey($ticket->team_id)->exists()) {
            throw new InvalidArgumentException('Le technicien doit appartenir à l’équipe du service.');
        }

        $ticket->assigned_to = $technicienId;
        $ticket->assigned_by = $operateur->id;
        $ticket->date_assignation = now();

        if ($technicienId && in_array($ticket->statut?->slug, ['nouveau', 'en_attente'], true)) {
            $ticket->ticket_status_id = $this->statutParSlug(TicketStatusSlug::Assigne)->id;
            if ($ticket->statut?->slug === 'en_attente') {
                $this->slaService->reprendre($ticket);
            }
        } elseif (! $technicienId && $ticket->statut?->slug === 'assigne') {
            $ticket->ticket_status_id = $this->statutParSlug(TicketStatusSlug::Nouveau)->id;
        }

        $ticket->save();

        $this->historique($ticket, 'affectation', $ancien ? (string) $ancien : null, $technicienId ? (string) $technicienId : null);

        return $ticket->fresh(['statut', 'technicien']);
    }

    protected function appliquerAssignation(Ticket $ticket, User $user, array $contexte): void
    {
        if (isset($contexte['assigned_to'])) {
            $this->reaffecter($ticket, $user, (int) $contexte['assigned_to']);

            return;
        }

        if (! $ticket->assigned_to && ($user->est_technicien || $user->hasRole('admin'))) {
            $this->reaffecter($ticket, $user, $user->id);

            return;
        }

        if (! $ticket->assigned_to) {
            throw new InvalidArgumentException('Un technicien doit être sélectionné.');
        }
    }

    protected function appliquerPriseEnCharge(Ticket $ticket, User $user): void
    {
        if (! $ticket->assigned_to) {
            $ticket->assigned_to = $user->id;
            $ticket->assigned_by = $user->id;
            $ticket->date_assignation = now();
            $this->historique($ticket, 'affectation', null, (string) $user->id);
        }

        if ($ticket->statut?->slug === 'en_attente') {
            $this->slaService->reprendre($ticket);
            $ticket->motif_attente = null;
            $ticket->date_mise_en_attente = null;
        }

        if ($ticket->statut?->slug === 'resolu') {
            $ticket->solution = null;
            $ticket->date_resolution = null;
        }
    }

    protected function appliquerMiseEnAttente(Ticket $ticket, array $contexte): void
    {
        $motif = trim($contexte['motif_attente'] ?? '');
        if ($motif === '') {
            throw new InvalidArgumentException('Le motif de mise en attente est obligatoire (GLPI).');
        }

        $ticket->motif_attente = $motif;
        $ticket->date_mise_en_attente = now();
        $this->slaService->mettreEnPause($ticket);
    }

    protected function appliquerResolution(Ticket $ticket, array $contexte): void
    {
        $solution = trim($contexte['solution'] ?? '');
        if ($solution === '') {
            throw new InvalidArgumentException('Une solution est obligatoire pour résoudre le ticket (GLPI).');
        }

        $ticket->solution = $solution;
        $ticket->date_resolution = now();

        if ($ticket->statut?->slug === 'en_attente') {
            $this->slaService->reprendre($ticket);
        }
    }

    protected function appliquerCloture(Ticket $ticket, User $user, array $contexte): void
    {
        $ticket->date_cloture = now();

        if (isset($contexte['satisfaction_note'])) {
            $ticket->satisfaction_note = (int) $contexte['satisfaction_note'];
            $ticket->satisfaction_commentaire = $contexte['satisfaction_commentaire'] ?? null;
        }
    }

    protected function appliquerAnnulation(Ticket $ticket): void
    {
        $ticket->date_cloture = now();
    }

    protected function peutTraiter(Ticket $ticket, User $user): bool
    {
        return $user->est_technicien
            || $user->hasPermission('tickets.assign')
            || $user->hasRole('admin')
            || $ticket->assigned_to === $user->id;
    }

    protected function peutAffecter(User $user): bool
    {
        return $user->hasRole('dsi') || $user->hasRole('admin');
    }

    protected function peutCloturer(Ticket $ticket, User $user): bool
    {
        if ($ticket->validation_statut === 'en_attente') {
            return false;
        }

        return $ticket->user_id === $user->id
            || $user->hasPermission('tickets.assign')
            || $user->hasRole('admin');
    }

    protected function peutAnnuler(Ticket $ticket, User $user): bool
    {
        return $ticket->user_id === $user->id
            || $user->hasPermission('tickets.assign')
            || $user->hasRole('admin');
    }

    protected function peutReouvrir(User $user): bool
    {
        return $user->hasPermission('tickets.assign')
            || $user->est_technicien
            || $user->hasRole('admin');
    }

    protected function historique(Ticket $ticket, string $action, ?string $ancien, ?string $nouveau): void
    {
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ancienne_valeur' => $ancien,
            'nouvelle_valeur' => $nouveau,
        ]);
    }
}
