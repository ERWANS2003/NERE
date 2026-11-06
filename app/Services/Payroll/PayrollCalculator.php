<?php

namespace App\Services\Payroll;

use App\Models\PayrollRubric;
use Illuminate\Support\Collection;

/**
 * Calcul d'un bulletin à partir des rubriques paramétrées. Aucun taux ni
 * barème n'est codé ici : tout vient des rubriques saisies par le service paie.
 *
 * Ordre : gains -> brut, brut cotisable, brut imposable -> cotisations ->
 * impôt (barème progressif) -> retenues -> net. Montants arrondis au franc.
 */
class PayrollCalculator
{
    /**
     * @param  Collection<int, array{rubric: PayrollRubric, amount: float, note?: ?string}>  $items  gains et retenues (fixes et variables)
     * @param  Collection<int, PayrollRubric>  $rubrics  cotisations et impôts actifs
     * @return array{lines: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function calculate(float $baseSalary, float $unpaidDays, int $dependents, Collection $items, Collection $rubrics, int $daysPerMonth = 30): array
    {
        $lines = [];
        $position = 0;

        $unpaid = $daysPerMonth > 0 ? $this->round($baseSalary * min($unpaidDays, $daysPerMonth) / $daysPerMonth) : 0.0;
        $lines[] = $this->line(++$position, 'SALBASE', 'Salaire de base', 'gain', $baseSalary, null, $baseSalary);
        $gross = $baseSalary;
        $contributory = $baseSalary;
        $taxable = $baseSalary;

        if ($unpaid > 0) {
            $lines[] = $this->line(++$position, 'ABSNP', 'Absences non payées ('.$this->days($unpaidDays).' j)', 'gain', $baseSalary, null, -$unpaid);
            $gross -= $unpaid;
            $contributory -= $unpaid;
            $taxable -= $unpaid;
        }

        $sorted = $items->sortBy(fn (array $item) => [$item['rubric']->position, $item['rubric']->code])->values();

        foreach ($sorted->filter(fn (array $item) => $item['rubric']->kind === PayrollRubric::GAIN) as $item) {
            $rubric = $item['rubric'];
            $amount = $this->round($item['amount']);
            $lines[] = $this->line(++$position, $rubric->code, $this->label($rubric, $item), 'gain', null, null, $amount);
            $gross += $amount;
            $contributory += $rubric->is_contributory ? $amount : 0;
            $taxable += $rubric->is_taxable ? $amount : 0;
        }

        $bases = ['gross' => $gross, 'contributory' => $contributory, 'taxable' => $taxable];
        $employeeContributions = 0.0;
        $employerContributions = 0.0;

        foreach ($rubrics->where('kind', PayrollRubric::CONTRIBUTION)->sortBy(['position', 'code']) as $rubric) {
            $base = $bases[$rubric->base ?? 'contributory'] ?? $contributory;
            if ($rubric->ceiling) {
                $base = min($base, $rubric->ceiling);
            }
            $base = max(0, $base);
            $employee = $this->round($base * ($rubric->employee_rate ?? 0) / 100);
            $employer = $this->round($base * ($rubric->employer_rate ?? 0) / 100);
            $lines[] = $this->line(++$position, $rubric->code, $rubric->label, 'contribution', $base, $rubric->employee_rate, $employee, $rubric->employer_rate, $employer);
            $employeeContributions += $employee;
            $employerContributions += $employer;
        }

        $tax = 0.0;
        foreach ($rubrics->where('kind', PayrollRubric::TAX)->sortBy(['position', 'code']) as $rubric) {
            [$base, $amount] = $this->tax($rubric->tax_rules ?? [], $bases[$rubric->base ?? 'taxable'] ?? $taxable, $employeeContributions, $dependents);
            $lines[] = $this->line(++$position, $rubric->code, $rubric->label, 'tax', $base, null, $amount);
            $tax += $amount;
        }

        $deductions = 0.0;
        foreach ($sorted->filter(fn (array $item) => $item['rubric']->kind === PayrollRubric::DEDUCTION) as $item) {
            $amount = $this->round($item['amount']);
            $lines[] = $this->line(++$position, $item['rubric']->code, $this->label($item['rubric'], $item), 'deduction', null, null, $amount);
            $deductions += $amount;
        }

        return [
            'lines' => $lines,
            'totals' => [
                'gross' => $gross,
                'contributory_base' => $contributory,
                'taxable_base' => $taxable,
                'employee_contributions' => $employeeContributions,
                'employer_contributions' => $employerContributions,
                'tax' => $tax,
                'deductions' => $deductions,
                'net' => $gross - $employeeContributions - $tax - $deductions,
            ],
        ];
    }

    /**
     * Impôt progressif : base imposable éventuellement diminuée des cotisations
     * salariales, puis d'un abattement, arrondie, soumise au barème par tranches,
     * puis réduite selon les charges de famille.
     *
     * @return array{0: float, 1: float} base retenue, montant
     */
    public function tax(array $rules, float $taxable, float $employeeContributions, int $dependents): array
    {
        $base = $taxable;
        if (! empty($rules['deduct_contributions'])) {
            $base -= $employeeContributions;
        }
        $base *= 1 - ((float) ($rules['abatement_rate'] ?? 0)) / 100;
        $roundTo = (int) ($rules['round_base_to'] ?? 0);
        if ($roundTo > 0) {
            $base = floor($base / $roundTo) * $roundTo;
        }
        $base = max(0, $this->round($base));

        $tax = 0.0;
        foreach ($rules['brackets'] ?? [] as $bracket) {
            $from = (float) $bracket['from'];
            $to = isset($bracket['to']) && $bracket['to'] !== null && $bracket['to'] !== '' ? (float) $bracket['to'] : INF;
            if ($base > $from) {
                $tax += (min($base, $to) - $from) * ((float) $bracket['rate']) / 100;
            }
        }

        $reductions = $rules['family_reductions'] ?? [];
        if ($dependents > 0 && $reductions !== []) {
            $key = min($dependents, max(array_map('intval', array_keys($reductions))));
            $tax -= $tax * ((float) ($reductions[$key] ?? $reductions[(string) $key] ?? 0)) / 100;
        }

        return [$base, max(0, $this->round($tax))];
    }

    private function line(int $position, string $code, string $label, string $kind, ?float $base, ?float $rate, float $amount, ?float $employerRate = null, float $employerAmount = 0): array
    {
        return [
            'position' => $position,
            'code' => $code,
            'label' => $label,
            'kind' => $kind,
            'base' => $base,
            'rate' => $rate,
            'employer_rate' => $employerRate,
            'amount' => $amount,
            'employer_amount' => $employerAmount,
        ];
    }

    private function label(PayrollRubric $rubric, array $item): string
    {
        return filled($item['note'] ?? null) ? $rubric->label.' ('.$item['note'].')' : $rubric->label;
    }

    private function days(float $days): string
    {
        return rtrim(rtrim(number_format($days, 2, ',', ''), '0'), ',');
    }

    private function round(float $amount): float
    {
        return (float) round($amount, 0, PHP_ROUND_HALF_UP);
    }
}
