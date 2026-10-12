<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Tableau de bord analytique de l'intranet.
 *
 * Les métriques portent sur les demandes (soumissions de formulaires), les
 * départements et les effectifs : ce projet n'a pas de système de tickets, les
 * KPI « tickets » de la maquette initiale sont donc remplacés par des
 * équivalents fondés sur les données réellement présentes.
 */
class AnalyticsController extends Controller
{
    /** Périodes proposées au filtre, en jours. */
    private const PERIODES = [7 => '7 jours', 30 => '30 jours', 90 => '3 mois', 365 => '1 an'];

    public function index(Request $request): View
    {
        $jours = (int) $request->integer('periode', 30);
        if (! array_key_exists($jours, self::PERIODES)) {
            $jours = 30;
        }

        $debut = Carbon::now()->subDays($jours)->startOfDay();
        $debutPrecedent = (clone $debut)->subDays($jours);

        return view('intranet.analytics.index', [
            'jours' => $jours,
            'periodes' => self::PERIODES,
            'kpis' => $this->kpis($debut, $debutPrecedent),
            'charts' => $this->charts($debut, $jours),
        ]);
    }

    /**
     * Quatre indicateurs avec variation par rapport à la période précédente.
     *
     * @return array<string, array<string, mixed>>
     */
    private function kpis(Carbon $debut, Carbon $debutPrecedent): array
    {
        $periode = FormSubmission::where('created_at', '>=', $debut);
        $precedente = FormSubmission::whereBetween('created_at', [$debutPrecedent, $debut]);

        $nbPeriode = (clone $periode)->count();
        $nbPrecedent = (clone $precedente)->count();

        $traitees = (clone $periode)->whereIn('status', ['approved', 'rejected'])->count();
        $tauxTraitement = $nbPeriode > 0 ? round($traitees / $nbPeriode * 100) : 0;

        $enAttente = FormSubmission::where('status', 'pending')->count();

        // Délai moyen de traitement (heures) sur les demandes traitées de la période.
        $delaiMoyen = (clone $periode)
            ->whereNotNull('reviewed_at')
            ->get()
            ->map(fn ($s) => $s->created_at->diffInHours($s->reviewed_at))
            ->avg();

        return [
            'demandes' => [
                'label' => 'Demandes reçues',
                'valeur' => $nbPeriode,
                'variation' => $this->variation($nbPeriode, $nbPrecedent),
                'icon' => 'inbox',
            ],
            'traitement' => [
                'label' => 'Taux de traitement',
                'valeur' => $tauxTraitement . ' %',
                'variation' => null,
                'icon' => 'check',
            ],
            'delai' => [
                'label' => 'Délai moyen de traitement',
                'valeur' => $delaiMoyen ? round($delaiMoyen) . ' h' : '—',
                'variation' => null,
                'icon' => 'clock',
            ],
            'attente' => [
                'label' => 'Demandes en attente',
                'valeur' => $enAttente,
                'variation' => null,
                'icon' => 'hourglass',
            ],
        ];
    }

    /**
     * Variation en pourcentage entre deux valeurs (null si base nulle).
     *
     * @return array{sens: string, pourcentage: int}|null
     */
    private function variation(int $actuel, int $precedent): ?array
    {
        if ($precedent === 0) {
            return null;
        }

        $pct = (int) round(($actuel - $precedent) / $precedent * 100);

        return [
            'sens' => $pct >= 0 ? 'hausse' : 'baisse',
            'pourcentage' => abs($pct),
        ];
    }

    /**
     * Jeux de données prêts pour Chart.js.
     *
     * @return array<string, mixed>
     */
    private function charts(Carbon $debut, int $jours): array
    {
        return [
            'parSemaine' => $this->demandesParSemaine($debut, $jours),
            'parDepartement' => $this->demandesParDepartement($debut),
            'parStatut' => $this->repartitionStatuts($debut),
            'effectifs' => $this->effectifsParDepartement(),
        ];
    }

    /**
     * Volume de demandes agrégé par semaine ISO sur la période.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    private function demandesParSemaine(Carbon $debut, int $jours): array
    {
        $soumissions = FormSubmission::where('created_at', '>=', $debut)
            ->orderBy('created_at')
            ->get(['created_at']);

        // Nombre de tranches hebdomadaires à afficher (au moins 1).
        $nbSemaines = max(1, (int) ceil($jours / 7));
        $labels = [];
        $data = array_fill(0, $nbSemaines, 0);

        for ($i = 0; $i < $nbSemaines; $i++) {
            $labels[] = 'S-' . ($nbSemaines - 1 - $i);
        }

        foreach ($soumissions as $s) {
            $index = min($nbSemaines - 1, (int) floor($debut->diffInDays($s->created_at) / 7));
            $data[$index]++;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    private function demandesParDepartement(Carbon $debut): array
    {
        $parDept = FormSubmission::where('form_submissions.created_at', '>=', $debut)
            ->join('department_forms', 'form_submissions.form_id', '=', 'department_forms.id')
            ->join('departments', 'department_forms.department_id', '=', 'departments.id')
            ->selectRaw('departments.name, count(*) as total')
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->pluck('total', 'name');

        return [
            'labels' => $parDept->keys()->all(),
            'data' => $parDept->values()->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    private function repartitionStatuts(Carbon $debut): array
    {
        $libelles = ['approved' => 'Approuvées', 'rejected' => 'Rejetées', 'pending' => 'En attente'];

        $comptes = FormSubmission::where('created_at', '>=', $debut)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        foreach ($libelles as $cle => $libelle) {
            $labels[] = $libelle;
            $data[] = (int) ($comptes[$cle] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    private function effectifsParDepartement(): array
    {
        $effectifs = Department::active()
            ->withCount(['users' => fn ($q) => $q->where('actif', true)])
            ->orderBy('position')
            ->get();

        return [
            'labels' => $effectifs->pluck('name')->all(),
            'data' => $effectifs->pluck('users_count')->map(fn ($v) => (int) $v)->all(),
        ];
    }
}
