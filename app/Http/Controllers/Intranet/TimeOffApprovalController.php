<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Notifications\TimeOffReviewed;
use App\Services\TimeOffBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Workflow d'approbation des congés (côté manager).
 *
 * Un manager voit les demandes en attente de ses collaborateurs directs
 * (users.manager_id), un directeur celles des membres de ses departements.
 * Le super-admin voit toutes les demandes en attente.
 * L'approbation décrémente le solde en transaction ; le refus ne touche pas
 * au solde.
 */
class TimeOffApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeApprover($request);

        $requests = TimeOffRequest::with(['user', 'policy'])
            ->where('status', 'pending')
            ->approvableBy($request->user())->orderBy('start_date')->paginate(15);

        return view('intranet.time-off.approvals', compact('requests'));
    }

    public function update(Request $request, TimeOffRequest $timeOffRequest, TimeOffBalanceService $balances): RedirectResponse
    {
        $this->authorizeApprover($request);
        $this->authorizeManages($request, $timeOffRequest);

        abort_unless($timeOffRequest->status === 'pending', 409, 'Cette demande a déjà été traitée.');

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'approval_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $approuve = $validated['decision'] === 'approve';

        $resultat = DB::transaction(function () use ($timeOffRequest, $request, $validated, $approuve, $balances) {
            // Verrou sur la demande puis nouvelle lecture du statut : deux clics
            // simultanés ne peuvent plus approuver (et décompter) deux fois.
            $demande = TimeOffRequest::query()->lockForUpdate()->find($timeOffRequest->getKey());

            if ($demande === null || $demande->status !== 'pending') {
                return 'deja-traitee';
            }

            if ($approuve && ! $balances->consume($demande)) {
                return 'solde-insuffisant';
            }

            $demande->update([
                // La base contraint le statut à pending/approved/denied/cancelled.
                'status' => $approuve ? 'approved' : 'denied',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approval_comment' => $validated['approval_comment'] ?? null,
            ]);

            return 'ok';
        });

        if ($resultat === 'deja-traitee') {
            abort(409, 'Cette demande a déjà été traitée.');
        }

        if ($resultat === 'solde-insuffisant') {
            return redirect()
                ->route('intranet.time-off.approvals')
                ->with('error', 'Solde insuffisant : cette demande dépasse les jours restants du collaborateur. Elle reste en attente.');
        }

        // Notifier l'employé (hors transaction : un échec d'envoi ne doit pas
        // annuler la décision déjà enregistrée).
        $timeOffRequest->user?->notify(new TimeOffReviewed($timeOffRequest->fresh(['policy'])));

        return redirect()
            ->route('intranet.time-off.approvals')
            ->with('success', $approuve ? 'Demande approuvée.' : 'Demande refusée.');
    }

    private function authorizeApprover(Request $request): void
    {
        abort_unless($request->user()->canApproveTimeOff(), 403, "Vous n'avez pas de demandes à approuver.");
    }

    private function authorizeManages(Request $request, TimeOffRequest $req): void
    {
        $autorise = TimeOffRequest::query()->approvableBy($request->user())->whereKey($req->getKey())->exists();

        abort_unless($autorise, 403, "Cette demande n'est pas de votre ressort.");
    }
}
