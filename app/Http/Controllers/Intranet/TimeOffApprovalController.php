<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\TimeOffBalance;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Notifications\TimeOffReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Workflow d'approbation des congés (côté manager).
 *
 * Un manager voit les demandes en attente de ses collaborateurs directs
 * (users.manager_id). Le super-admin voit toutes les demandes en attente.
 * L'approbation décrémente le solde en transaction ; le refus ne touche pas
 * au solde.
 */
class TimeOffApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeApprover($request);

        $user = $request->user();

        $query = TimeOffRequest::with(['user', 'policy'])
            ->where('status', 'pending');

        if (! $user->is_super_admin) {
            // Seulement les demandes des collaborateurs directs du manager.
            $query->whereHas('user', fn ($q) => $q->where('manager_id', $user->id));
        }

        $requests = $query->orderBy('start_date')->paginate(15);

        return view('intranet.time-off.approvals', compact('requests'));
    }

    public function update(Request $request, TimeOffRequest $timeOffRequest): RedirectResponse
    {
        $this->authorizeApprover($request);
        $this->authorizeManages($request, $timeOffRequest);

        // Seule une demande en attente peut être traitée : évite une double
        // approbation (et donc un double décrément du solde).
        abort_unless($timeOffRequest->status === 'pending', 409, 'Cette demande a déjà été traitée.');

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'approval_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $approuve = $validated['decision'] === 'approve';

        DB::transaction(function () use ($timeOffRequest, $request, $validated, $approuve) {
            if ($approuve) {
                $this->decrementBalance($timeOffRequest);
            }

            $timeOffRequest->update([
                // La base contraint le statut à pending/approved/denied/cancelled.
                'status' => $approuve ? 'approved' : 'denied',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approval_comment' => $validated['approval_comment'] ?? null,
            ]);
        });

        // Notifier l'employé (hors transaction : un échec d'envoi ne doit pas
        // annuler la décision déjà enregistrée).
        $timeOffRequest->user?->notify(new TimeOffReviewed($timeOffRequest->fresh(['policy'])));

        return redirect()
            ->route('intranet.time-off.approvals')
            ->with('success', $approuve ? 'Demande approuvée.' : 'Demande refusée.');
    }

    /**
     * Décrémente le solde de l'année de début de la demande. Verrouille la
     * ligne pour la durée de la transaction (lockForUpdate) afin que deux
     * approbations concurrentes ne corrompent pas le solde.
     */
    private function decrementBalance(TimeOffRequest $req): void
    {
        $balance = TimeOffBalance::where([
            'user_id' => $req->user_id,
            'policy_id' => $req->policy_id,
            'year' => $req->start_date->year,
        ])->lockForUpdate()->first();

        if (! $balance) {
            return; // pas de solde défini : rien à décrémenter
        }

        $balance->used_days = (float) $balance->used_days + (float) $req->total_days;
        $balance->remaining_days = (float) $balance->total_days
            + (float) $balance->carry_over_days
            - (float) $balance->used_days;
        $balance->save();
    }

    private function authorizeApprover(Request $request): void
    {
        $user = $request->user();
        // Un manager est quelqu'un qui a au moins un collaborateur direct.
        $estManager = $user->is_super_admin
            || User::where('manager_id', $user->id)->exists();

        abort_unless($estManager, 403, "Vous n'avez pas de demandes à approuver.");
    }

    private function authorizeManages(Request $request, TimeOffRequest $req): void
    {
        $user = $request->user();
        $autorise = $user->is_super_admin || $req->user?->manager_id === $user->id;

        abort_unless($autorise, 403, "Cette demande n'est pas de votre ressort.");
    }
}
