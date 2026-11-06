<?php

namespace App\Services\Payroll;

use App\Models\AppSetting;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\PayrollProfile;
use App\Models\PayrollRubric;
use App\Models\PayrollVariable;
use App\Models\Payslip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(private PayrollCalculator $calculator) {}

    public static function daysPerMonth(): int
    {
        return max(1, (int) AppSetting::value('payroll_days_per_month', '30'));
    }

    /** Calcule (ou recalcule) les bulletins de tous les salariés actifs de la période. */
    public function run(PayrollPeriod $period): int
    {
        abort_if($period->isClosed(), 409, 'Cette paie est clôturée.');

        $profiles = PayrollProfile::query()
            ->where('active', true)
            ->whereHas('user', fn ($query) => $query->where('actif', true))
            ->with(['user', 'rubrics'])
            ->get();

        DB::transaction(function () use ($period, $profiles): void {
            $period->payslips()->whereNotIn('user_id', $profiles->pluck('user_id'))->delete();
            foreach ($profiles as $profile) {
                $this->calculateFor($period, $profile);
            }
        });

        return $profiles->count();
    }

    /** Aperçu sans enregistrement. */
    public function preview(PayrollPeriod $period, PayrollProfile $profile): array
    {
        return $this->compute($period, $profile->loadMissing(['user', 'rubrics']));
    }

    public function calculateFor(PayrollPeriod $period, PayrollProfile $profile): Payslip
    {
        $result = $this->compute($period, $profile);
        $user = $profile->user;

        $payslip = Payslip::query()->updateOrCreate(
            ['payroll_period_id' => $period->id, 'user_id' => $user->id],
            [
                ...$result['totals'],
                'stale' => false,
                'employee' => [
                    'name' => $user->name,
                    'matricule' => $user->matricule,
                    'poste' => $user->poste,
                    'hire_date' => $user->hire_date?->format('d/m/Y'),
                    'category' => $profile->category,
                    'contract_type' => $profile->contract_type,
                    'social_security_number' => $profile->social_security_number,
                    'dependents' => $profile->dependents,
                    'base_salary' => $profile->base_salary,
                    'payment_method' => $profile->payment_method,
                    'bank_name' => $profile->bank_name,
                    'bank_account' => $profile->bank_account,
                ],
            ],
        );
        $payslip->lines()->delete();
        $payslip->lines()->createMany($result['lines']);

        return $payslip;
    }

    /** Marque les bulletins à recalculer après un changement de paramètres. */
    public static function markStale(?int $userId = null): void
    {
        $query = Payslip::query()->whereHas('period', fn ($period) => $period->where('status', 'open'));
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        $query->update(['stale' => true]);
    }

    public function close(PayrollPeriod $period, int $userId): void
    {
        abort_if($period->isClosed(), 409, 'Cette paie est déjà clôturée.');
        abort_if($period->payslips()->count() === 0, 422, 'Aucun bulletin calculé.');
        abort_if($period->payslips()->where('stale', true)->exists(), 422, 'Des bulletins sont à recalculer avant la clôture.');

        $period->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $userId]);
    }

    private function compute(PayrollPeriod $period, PayrollProfile $profile): array
    {
        $userId = $profile->user_id;
        $unpaidDays = (float) (PayrollEntry::query()
            ->where('payroll_period_id', $period->id)
            ->where('user_id', $userId)
            ->value('unpaid_days') ?? 0);

        /** @var Collection<int, array> $items */
        $items = $profile->rubrics
            ->where('active', true)
            ->whereIn('kind', [PayrollRubric::GAIN, PayrollRubric::DEDUCTION])
            ->map(fn (PayrollRubric $rubric) => ['rubric' => $rubric, 'amount' => (float) $rubric->pivot->amount])
            ->values();

        PayrollVariable::query()
            ->where('payroll_period_id', $period->id)
            ->where('user_id', $userId)
            ->with('rubric')
            ->get()
            ->each(function (PayrollVariable $variable) use ($items): void {
                $items->push(['rubric' => $variable->rubric, 'amount' => $variable->amount, 'note' => $variable->note]);
            });

        $rubrics = PayrollRubric::query()
            ->where('active', true)
            ->whereIn('kind', [PayrollRubric::CONTRIBUTION, PayrollRubric::TAX])
            ->get();

        return $this->calculator->calculate($profile->base_salary, $unpaidDays, $profile->dependents, $items, $rubrics, self::daysPerMonth());
    }
}
