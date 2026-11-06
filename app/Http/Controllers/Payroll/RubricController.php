<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\PayrollRubric;
use App\Services\AdminAudit;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RubricController extends Controller
{
    public function index(): View
    {
        return view('payroll.rubrics.index', [
            'rubrics' => PayrollRubric::query()->orderBy('kind')->orderBy('position')->orderBy('code')->get()->groupBy('kind'),
            'daysPerMonth' => PayrollService::daysPerMonth(),
        ]);
    }

    public function create(): View
    {
        return view('payroll.rubrics.form', ['rubric' => new PayrollRubric(['kind' => PayrollRubric::GAIN, 'active' => true, 'position' => 100])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rubric = PayrollRubric::create($this->validated($request));
        AdminAudit::record('création rubrique de paie', 'paie', $rubric, null, $rubric->only(['code', 'kind', 'employee_rate', 'employer_rate', 'ceiling', 'tax_rules']));
        PayrollService::markStale();

        return redirect()->route('payroll.rubrics.index')->with('success', 'La rubrique '.$rubric->code.' a été créée.');
    }

    public function edit(PayrollRubric $rubric): View
    {
        return view('payroll.rubrics.form', ['rubric' => $rubric]);
    }

    public function update(Request $request, PayrollRubric $rubric): RedirectResponse
    {
        $before = $rubric->only(['code', 'label', 'kind', 'employee_rate', 'employer_rate', 'ceiling', 'tax_rules', 'active']);
        $rubric->update($this->validated($request, $rubric));
        AdminAudit::record('modification rubrique de paie', 'paie', $rubric, $before, $rubric->only(array_keys($before)));
        PayrollService::markStale();

        return redirect()->route('payroll.rubrics.index')->with('success', 'La rubrique '.$rubric->code.' a été modifiée.');
    }

    public function settings(Request $request): RedirectResponse
    {
        $validated = $request->validate(['days_per_month' => ['required', 'integer', 'between:1,31']]);
        AppSetting::saveValues(['payroll_days_per_month' => (string) $validated['days_per_month']]);
        PayrollService::markStale();

        return back()->with('success', 'Les paramètres de paie ont été enregistrés.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PayrollRubric $rubric = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/', Rule::unique('payroll_rubrics', 'code')->ignore($rubric)],
            'label' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(PayrollRubric::KINDS))],
            'base' => ['nullable', Rule::in(array_keys(PayrollRubric::BASES))],
            'employee_rate' => ['nullable', 'numeric', 'between:0,100'],
            'employer_rate' => ['nullable', 'numeric', 'between:0,100'],
            'ceiling' => ['nullable', 'numeric', 'min:0'],
            'brackets' => ['nullable', 'string', 'max:2000'],
            'family_reductions' => ['nullable', 'string', 'max:1000'],
            'abatement_rate' => ['nullable', 'numeric', 'between:0,100'],
            'round_base_to' => ['nullable', 'integer', 'min:0'],
            'position' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'code.regex' => 'Le code ne contient que des majuscules, chiffres et tirets bas (ex. CNSS_SAL).',
        ]);

        $kind = $validated['kind'];
        $data = [
            'code' => $validated['code'],
            'label' => $validated['label'],
            'kind' => $kind,
            'position' => $validated['position'],
            'active' => $request->boolean('active'),
            'is_taxable' => $kind === PayrollRubric::GAIN && $request->boolean('is_taxable'),
            'is_contributory' => $kind === PayrollRubric::GAIN && $request->boolean('is_contributory'),
            'base' => null,
            'employee_rate' => null,
            'employer_rate' => null,
            'ceiling' => null,
            'tax_rules' => null,
        ];

        if ($kind === PayrollRubric::CONTRIBUTION) {
            if (($validated['employee_rate'] ?? null) === null && ($validated['employer_rate'] ?? null) === null) {
                throw ValidationException::withMessages(['employee_rate' => 'Indiquez au moins un taux (salarial ou patronal).']);
            }
            $data['base'] = $validated['base'] ?? 'contributory';
            $data['employee_rate'] = $validated['employee_rate'] ?? 0;
            $data['employer_rate'] = $validated['employer_rate'] ?? 0;
            $data['ceiling'] = $validated['ceiling'] ?? null;
        }

        if ($kind === PayrollRubric::TAX) {
            $brackets = $this->parseLines($validated['brackets'] ?? '', 'brackets', 3);
            if ($brackets === []) {
                throw ValidationException::withMessages(['brackets' => 'Saisissez au moins une tranche du barème.']);
            }
            $data['base'] = $validated['base'] ?? 'taxable';
            $data['tax_rules'] = [
                'deduct_contributions' => $request->boolean('deduct_contributions'),
                'abatement_rate' => (float) ($validated['abatement_rate'] ?? 0),
                'round_base_to' => (int) ($validated['round_base_to'] ?? 0),
                'brackets' => array_map(fn (array $b) => ['from' => $b[0], 'to' => $b[1], 'rate' => $b[2]], $brackets),
                'family_reductions' => collect($this->parseLines($validated['family_reductions'] ?? '', 'family_reductions', 2))
                    ->mapWithKeys(fn (array $r) => [(int) $r[0] => $r[1]])->all(),
            ];
        }

        return $data;
    }

    /**
     * Lignes « valeur;valeur;… ». Une valeur vide (dernière tranche) devient null.
     *
     * @return list<list<float|null>>
     */
    private function parseLines(string $text, string $field, int $columns): array
    {
        $rows = [];
        foreach (preg_split('/\r?\n/', trim($text)) as $number => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = array_map('trim', explode(';', $line));
            if (count($cells) !== $columns) {
                throw ValidationException::withMessages([$field => 'Ligne '.($number + 1).' : '.$columns.' valeurs séparées par « ; » attendues.']);
            }
            $values = [];
            foreach ($cells as $index => $cell) {
                $cell = str_replace([' ', ','], ['', '.'], $cell);
                if ($cell === '' && $field === 'brackets' && $index === 1) {
                    $values[] = null;

                    continue;
                }
                if (! is_numeric($cell)) {
                    throw ValidationException::withMessages([$field => 'Ligne '.($number + 1).' : « '.$cell.' » n’est pas un nombre.']);
                }
                $values[] = (float) $cell;
            }
            $rows[] = $values;
        }

        return $rows;
    }
}
