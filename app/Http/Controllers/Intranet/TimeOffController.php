<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\TimeOffBalance;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Services\TimeOffBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gestion des congés côté employé : consultation des soldes, historique,
 * et soumission d'une demande.
 */
class TimeOffController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $annee = (int) now()->year;

        $balances = TimeOffBalance::where('user_id', $user->id)
            ->where('year', $annee)
            ->with('policy')
            ->get()
            ->sortBy(fn ($b) => $b->policy?->name)
            ->values();

        $requests = TimeOffRequest::where('user_id', $user->id)
            ->with('policy', 'approver')
            ->latest('start_date')
            ->paginate(10);

        // Un manager (collaborateurs directs) ou un admin accède à l'écran d'approbation.
        $estApprobateur = $user->is_super_admin
            || User::where('manager_id', $user->id)->exists();

        // Nombre de demandes en attente dans son périmètre (badge sur le lien).
        $enAttente = 0;
        if ($estApprobateur) {
            $q = TimeOffRequest::where('status', 'pending');
            if (! $user->is_super_admin) {
                $q->whereHas('user', fn ($sub) => $sub->where('manager_id', $user->id));
            }
            $enAttente = $q->count();
        }

        return view('intranet.time-off.index', compact('balances', 'requests', 'estApprobateur', 'enAttente'));
    }

    public function create(Request $request): View
    {
        $annee = (int) now()->year;

        // On ne charge que le solde de l'utilisateur courant pour l'année en cours :
        // la vue y lit le reste disponible par politique.
        $policies = TimeOffPolicy::active()
            ->with(['balances' => fn ($q) => $q->where('user_id', $request->user()->id)->where('year', $annee)])
            ->orderBy('name')
            ->get();

        return view('intranet.time-off.create', compact('policies'));
    }

    public function store(Request $request, TimeOffBalanceService $balances): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'policy_id' => ['required', Rule::exists('time_off_policies', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $policy = TimeOffPolicy::findOrFail($validated['policy_id']);
        $debut = Carbon::parse($validated['start_date']);
        $fin = Carbon::parse($validated['end_date']);
        $demiJournee = $request->boolean('is_half_day');

        // Une demi-journée ne vaut que pour une seule date.
        if ($demiJournee && ! $debut->isSameDay($fin)) {
            throw ValidationException::withMessages([
                'is_half_day' => 'Une demi-journée doit porter sur une seule date (début = fin).',
            ]);
        }

        $jours = $this->joursOuvres($debut, $fin, $demiJournee);

        if ($jours <= 0) {
            throw ValidationException::withMessages([
                'start_date' => 'La période sélectionnée ne contient aucun jour ouvré.',
            ]);
        }

        // Préavis requis par la politique (sauf maladie, préavis 0).
        if ($policy->advance_notice_days > 0) {
            $preavis = Carbon::today()->diffInDays($debut, false);
            if ($preavis < $policy->advance_notice_days) {
                throw ValidationException::withMessages([
                    'start_date' => "Cette politique exige un préavis d'au moins {$policy->advance_notice_days} jour(s).",
                ]);
            }
        }

        if ($balances->overlaps($user->id, $debut, $fin)) {
            throw ValidationException::withMessages([
                'start_date' => 'Vous avez déjà une demande en attente ou approuvée sur ces dates.',
            ]);
        }

        // Solde de l'année de début, déduction faite des demandes encore en attente.
        $disponible = $balances->availableDays($user->id, $policy->id, $debut->year);

        if ($disponible !== null && $jours > $disponible) {
            $affiche = rtrim(rtrim(number_format(max(0, $disponible), 1, ',', ''), '0'), ',');

            throw ValidationException::withMessages([
                'end_date' => "Solde insuffisant : {$affiche} jour(s) disponible(s) en tenant compte des demandes en attente, {$jours} demandé(s).",
            ]);
        }

        DB::transaction(function () use ($user, $policy, $debut, $fin, $jours, $validated, $demiJournee, $balances) {
            $demande = TimeOffRequest::create([
                'user_id' => $user->id,
                'policy_id' => $policy->id,
                'start_date' => $debut->toDateString(),
                'end_date' => $fin->toDateString(),
                'total_days' => $jours,
                'reason' => $validated['reason'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'is_half_day' => $demiJournee,
                // Les politiques sans approbation sont acceptées d'office.
                'status' => $policy->requires_approval ? 'pending' : 'approved',
                'approved_at' => $policy->requires_approval ? null : now(),
            ]);

            // Une acceptation d'office consomme le solde comme une approbation manuelle.
            if (! $policy->requires_approval && ! $balances->consume($demande)) {
                throw ValidationException::withMessages([
                    'end_date' => 'Solde insuffisant pour cette demande.',
                ]);
            }
        });

        return redirect()
            ->route('intranet.time-off.index')
            ->with('success', 'Votre demande de congé a été soumise.');
    }

    /**
     * Nombre de jours ouvrés (lundi-vendredi) entre deux dates incluses.
     * Une demi-journée compte pour 0,5.
     */
    private function joursOuvres(Carbon $debut, Carbon $fin, bool $demiJournee): float
    {
        if ($demiJournee) {
            return $debut->isWeekday() ? 0.5 : 0.0;
        }

        $jours = 0;
        $curseur = $debut->copy();
        while ($curseur->lte($fin)) {
            if ($curseur->isWeekday()) {
                $jours++;
            }
            $curseur->addDay();
        }

        return (float) $jours;
    }
}
