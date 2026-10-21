<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    private const PERIODES = [7 => '7 jours', 30 => '30 jours', 90 => '3 mois', 365 => '1 an'];

    public function index(Request $request): View
    {
        return view('intranet.analytics.index', $this->analyticsData($request));
    }

    public function exportPdf(Request $request): Response
    {
        $data = $this->analyticsData($request);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('intranet.analytics.pdf', $data)->render(), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="analytics-'.$data['jours'].'-jours-'.now()->format('Ymd-His').'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        $data = $this->analyticsData($request);
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Impossible de préparer l’export CSV.');
        }

        if (fwrite($stream, "\xEF\xBB\xBF") === false) {
            fclose($stream);
            throw new RuntimeException('Impossible de préparer l’export CSV.');
        }

        foreach ($this->exportRows($data) as $row) {
            if (fputcsv($stream, array_map($this->csvSafeValue(...), $row), ';', '"', '') === false) {
                fclose($stream);
                throw new RuntimeException('Impossible de générer l’export CSV.');
            }
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            throw new RuntimeException('Impossible de lire l’export CSV généré.');
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->exportFilename($data, 'csv').'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function exportJson(Request $request): JsonResponse
    {
        $data = $this->analyticsData($request);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'scope' => $data['scopeLabel'],
            'period_days' => $data['jours'],
            'period_label' => $data['periodes'][$data['jours']],
            'overview' => $data['overview'],
            'kpis' => $data['kpis'],
            'charts' => $data['charts'],
        ], 200, [
            'Content-Disposition' => 'attachment; filename="'.$this->exportFilename($data, 'json').'"',
            'Cache-Control' => 'private, no-store',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function exportXlsx(Request $request): StreamedResponse
    {
        $data = $this->analyticsData($request);
        $spreadsheet = new Spreadsheet;
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Indicateurs');
        $this->writeWorksheetRows($summary, $this->exportRows($data));
        $summary->getStyle('A1:D1')->getFont()->setBold(true);

        $charts = $spreadsheet->createSheet();
        $charts->setTitle('Graphiques');
        $this->writeWorksheetRows($charts, $this->chartRows($data));
        $charts->getStyle('A1:C1')->getFont()->setBold(true);

        foreach ([[$summary, 4], [$charts, 3]] as [$worksheet, $columnCount]) {
            for ($column = 1; $column <= $columnCount; $column++) {
                $worksheet->getColumnDimensionByColumn($column)->setAutoSize(true);
            }
        }

        $spreadsheet->getProperties()
            ->setTitle('Rapport analytique')
            ->setSubject($data['scopeLabel']);

        return response()->streamDownload(
            static function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $this->exportFilename($data, 'xlsx'),
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|int>>
     */
    private function exportRows(array $data): array
    {
        $rows = [['Type', 'Indicateur', 'Valeur', 'Variation']];

        if ($data['overview'] !== null) {
            foreach ([
                'Départements actifs' => $data['overview']['departements'],
                'Employés actifs' => $data['overview']['effectif'],
                'Demandes enregistrées' => $data['overview']['demandes'],
            ] as $label => $value) {
                $rows[] = ['Vue d’ensemble', $label, $value, ''];
            }
        }

        foreach ($data['kpis'] as $kpi) {
            $variation = $kpi['variation'] === null
                ? ''
                : ucfirst($kpi['variation']['sens']).' de '.$kpi['variation']['pourcentage'].' %';
            $rows[] = ['Indicateur', $kpi['label'], $kpi['valeur'], $variation];
        }

        foreach ($this->chartRows($data) as [$chart, $label, $value]) {
            if ($chart !== 'Graphique') {
                $rows[] = ['Donnée graphique', $chart.' - '.$label, $value, ''];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|int>>
     */
    private function chartRows(array $data): array
    {
        $titles = [
            'parSemaine' => 'Demandes par semaine',
            'parStatut' => 'Répartition par statut',
            'parDepartement' => 'Demandes par département',
            'effectifs' => 'Effectifs par département',
        ];
        $rows = [['Graphique', 'Élément', 'Valeur']];

        foreach ($titles as $key => $title) {
            foreach ($data['charts'][$key]['labels'] as $index => $label) {
                $rows[] = [$title, $label, $data['charts'][$key]['data'][$index]];
            }
        }

        return $rows;
    }

    private function csvSafeValue(string|int $value): string|int
    {
        if (is_string($value) && preg_match('/^[\t\r ]*[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function exportFilename(array $data, string $extension): string
    {
        return 'analytics-'.$data['jours'].'-jours-'.now()->format('Ymd-His').'.'.$extension;
    }

    /**
     * @param  list<list<string|int>>  $rows
     */
    private function writeWorksheetRows(Worksheet $worksheet, array $rows): void
    {
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $cell = $worksheet->getCell([$columnIndex + 1, $rowIndex + 1]);

                if (is_int($value)) {
                    $cell->setValue($value);
                } else {
                    $cell->setValueExplicit($value, DataType::TYPE_STRING);
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsData(Request $request): array
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

        return [
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
        ];
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
