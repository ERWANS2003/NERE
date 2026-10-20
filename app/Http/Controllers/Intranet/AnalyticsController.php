<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    private const PERIODES = [7 => '7 jours', 30 => '30 jours', 90 => '3 mois', 365 => '1 an'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->is_super_admin;
        $departments = $isAdmin ? collect() : $user->directedDepartments()->sortBy('name')->values();

        abort_unless($isAdmin || $departments->isNotEmpty(), 403);

        $departmentIds = $isAdmin ? null : $departments->modelKeys();
        $days = $request->integer('periode', 30);
        if (! array_key_exists($days, self::PERIODES)) {
            $days = 30;
        }

        $start = Carbon::now()->subDays($days)->startOfDay();
        $previousStart = (clone $start)->subDays($days);

        return view('intranet.analytics.index', [
            'jours' => $days,
            'periodes' => self::PERIODES,
            'isAdmin' => $isAdmin,
            'scopeLabel' => $isAdmin
                ? 'Vue globale de l’entreprise'
                : 'Statistiques de : '.$departments->pluck('name')->join(', '),
            'overview' => $isAdmin ? [
                'departements' => Department::active()->count(),
                'effectif' => User::where('actif', true)->count(),
                'demandes' => FormSubmission::count(),
            ] : null,
            'kpis' => $this->kpis($start, $previousStart, $departmentIds),
            'charts' => $this->charts($start, $days, $departmentIds),
        ]);
    }

    /**
     * @param  list<int>|null  $departmentIds  Null means all departments (admin only).
     * @return array<string, array<string, mixed>>
     */
    private function kpis(Carbon $start, Carbon $previousStart, ?array $departmentIds): array
    {
        $period = $this->submissions($departmentIds)->where('created_at', '>=', $start);
        $previous = $this->submissions($departmentIds)
            ->where('created_at', '>=', $previousStart)
            ->where('created_at', '<', $start);

        $periodCount = (clone $period)->count();
        $previousCount = (clone $previous)->count();
        $processedCount = (clone $period)->whereIn('status', ['approved', 'rejected'])->count();
        $processingRate = $periodCount > 0 ? round($processedCount / $periodCount * 100) : 0;
        $pendingCount = $this->submissions($departmentIds)->where('status', 'pending')->count();

        $averageHours = (clone $period)
            ->whereNotNull('reviewed_at')
            ->get()
            ->map(fn (FormSubmission $submission) => $submission->created_at->diffInHours($submission->reviewed_at))
            ->avg();

        return [
            'demandes' => [
                'label' => 'Demandes reçues',
                'valeur' => $periodCount,
                'variation' => $this->variation($periodCount, $previousCount),
                'icon' => 'inbox',
            ],
            'traitement' => [
                'label' => 'Taux de traitement',
                'valeur' => $processingRate.' %',
                'variation' => null,
                'icon' => 'check',
            ],
            'delai' => [
                'label' => 'Délai moyen de traitement',
                'valeur' => $averageHours ? round($averageHours).' h' : '—',
                'variation' => null,
                'icon' => 'clock',
            ],
            'attente' => [
                'label' => 'Demandes en attente',
                'valeur' => $pendingCount,
                'variation' => null,
                'icon' => 'hourglass',
            ],
        ];
    }

    /**
     * @return array{sens: string, pourcentage: int}|null
     */
    private function variation(int $current, int $previous): ?array
    {
        if ($previous === 0) {
            return null;
        }

        $percentage = (int) round(($current - $previous) / $previous * 100);

        return [
            'sens' => $percentage >= 0 ? 'hausse' : 'baisse',
            'pourcentage' => abs($percentage),
        ];
    }

    /**
     * @param  list<int>|null  $departmentIds
     * @return array<string, mixed>
     */
    private function charts(Carbon $start, int $days, ?array $departmentIds): array
    {
        return [
            'parSemaine' => $this->demandesParSemaine($start, $days, $departmentIds),
            'parDepartement' => $this->demandesParDepartement($start, $departmentIds),
            'parStatut' => $this->repartitionStatuts($start, $departmentIds),
            'effectifs' => $this->effectifsParDepartement($departmentIds),
        ];
    }

    /**
     * @param  list<int>|null  $departmentIds
     * @return array{labels: list<string>, data: list<int>}
     */
    private function demandesParSemaine(Carbon $start, int $days, ?array $departmentIds): array
    {
        $submissions = $this->submissions($departmentIds)
            ->where('created_at', '>=', $start)
            ->orderBy('created_at')
            ->get(['created_at']);

        $weekCount = max(1, (int) ceil($days / 7));
        $labels = [];
        $data = array_fill(0, $weekCount, 0);

        for ($index = 0; $index < $weekCount; $index++) {
            $labels[] = 'S-'.($weekCount - 1 - $index);
        }

        foreach ($submissions as $submission) {
            $index = min($weekCount - 1, (int) floor($start->diffInDays($submission->created_at) / 7));
            $data[$index]++;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @param  list<int>|null  $departmentIds
     * @return array{labels: list<string>, data: list<int>}
     */
    private function demandesParDepartement(Carbon $start, ?array $departmentIds): array
    {
        $query = FormSubmission::query()
            ->where('form_submissions.created_at', '>=', $start)
            ->join('department_forms', 'form_submissions.form_id', '=', 'department_forms.id')
            ->join('departments', 'department_forms.department_id', '=', 'departments.id');

        if ($departmentIds !== null) {
            $query->whereIn('departments.id', $departmentIds);
        }

        $byDepartment = $query
            ->selectRaw('departments.name, count(*) as total')
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->pluck('total', 'name');

        return [
            'labels' => $byDepartment->keys()->all(),
            'data' => $byDepartment->values()->map(fn ($value) => (int) $value)->all(),
        ];
    }

    /**
     * @param  list<int>|null  $departmentIds
     * @return array{labels: list<string>, data: list<int>}
     */
    private function repartitionStatuts(Carbon $start, ?array $departmentIds): array
    {
        $labelsByStatus = ['approved' => 'Approuvées', 'rejected' => 'Rejetées', 'pending' => 'En attente'];
        $counts = $this->submissions($departmentIds)
            ->where('created_at', '>=', $start)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        foreach ($labelsByStatus as $status => $label) {
            $labels[] = $label;
            $data[] = (int) ($counts[$status] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @param  list<int>|null  $departmentIds
     * @return array{labels: list<string>, data: list<int>}
     */
    private function effectifsParDepartement(?array $departmentIds): array
    {
        $departments = Department::active()
            ->when($departmentIds !== null, fn ($query) => $query->whereKey($departmentIds))
            ->withCount(['users' => fn ($query) => $query->where('actif', true)])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return [
            'labels' => $departments->pluck('name')->all(),
            'data' => $departments->pluck('users_count')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    /**
     * @param  list<int>|null  $departmentIds  Null means all departments.
     * @return Builder<FormSubmission>
     */
    private function submissions(?array $departmentIds): Builder
    {
        return FormSubmission::query()
            ->when(
                $departmentIds !== null,
                fn (Builder $query) => $query->whereHas(
                    'form',
                    fn (Builder $forms) => $forms->whereIn('department_id', $departmentIds)
                )
            );
    }
}
