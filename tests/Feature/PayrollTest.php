<?php

use App\Models\PayrollPeriod;
use App\Models\PayrollProfile;
use App\Models\PayrollRubric;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\User;
use App\Services\Payroll\PayrollCalculator;

// Valeurs de test uniquement : ce ne sont pas des taux réglementaires.
function payrollRubrics(): array
{
    return [
        'prime' => PayrollRubric::create(['code' => 'PRIME', 'label' => 'Prime de poste', 'kind' => 'gain', 'is_taxable' => true, 'is_contributory' => true, 'position' => 10, 'active' => true]),
        'transport' => PayrollRubric::create(['code' => 'TRANSPORT', 'label' => 'Indemnité de transport', 'kind' => 'gain', 'position' => 20, 'active' => true]),
        'avance' => PayrollRubric::create(['code' => 'AVANCE', 'label' => 'Avance sur salaire', 'kind' => 'deduction', 'position' => 30, 'active' => true]),
        'cnss' => PayrollRubric::create(['code' => 'COTIS', 'label' => 'Cotisation test', 'kind' => 'contribution', 'base' => 'contributory', 'employee_rate' => 5.5, 'employer_rate' => 16, 'ceiling' => 200000, 'position' => 40, 'active' => true]),
        'impot' => PayrollRubric::create(['code' => 'IMPOT', 'label' => 'Impôt test', 'kind' => 'tax', 'base' => 'taxable', 'position' => 50, 'active' => true, 'tax_rules' => [
            'deduct_contributions' => true,
            'abatement_rate' => 20,
            'round_base_to' => 100,
            'brackets' => [['from' => 0, 'to' => 50000, 'rate' => 0], ['from' => 50000, 'to' => 100000, 'rate' => 10], ['from' => 100000, 'to' => null, 'rate' => 20]],
            'family_reductions' => [1 => 10, 2 => 20],
        ]]),
    ];
}

function payrollManager(): User
{
    $manager = User::factory()->create();
    $manager->roles()->attach(Role::where('slug', 'gestionnaire-paie')->firstOrFail());

    return $manager;
}

it('computes a payslip from configured rubrics', function () {
    $r = payrollRubrics();
    $items = collect([
        ['rubric' => $r['prime'], 'amount' => 50000],
        ['rubric' => $r['transport'], 'amount' => 20000],
        ['rubric' => $r['avance'], 'amount' => 10000],
    ]);

    $result = app(PayrollCalculator::class)->calculate(200000, 3, 2, $items, collect([$r['cnss'], $r['impot']]), 30);

    // Brut 250 000 ; cotisable et imposable 230 000 ; cotisation plafonnée à 200 000.
    // Impôt : (230 000 - 11 000) x 0,8 = 175 200 -> 5 000 + 15 040 = 20 040, moins 20 % = 16 032.
    expect($result['totals'])->toMatchArray([
        'gross' => 250000.0,
        'contributory_base' => 230000.0,
        'taxable_base' => 230000.0,
        'employee_contributions' => 11000.0,
        'employer_contributions' => 32000.0,
        'tax' => 16032.0,
        'deductions' => 10000.0,
        'net' => 212968.0,
    ]);
    expect(collect($result['lines'])->pluck('code')->all())->toBe(['SALBASE', 'ABSNP', 'PRIME', 'TRANSPORT', 'COTIS', 'IMPOT', 'AVANCE'])
        ->and(collect($result['lines'])->firstWhere('code', 'ABSNP')['amount'])->toBe(-20000.0)
        ->and(collect($result['lines'])->firstWhere('code', 'IMPOT')['base'])->toBe(175200.0);
});

it('restricts payroll screens to payroll managers', function () {
    $this->actingAs(User::factory()->create())->get(route('payroll.index'))->assertForbidden();
    $this->actingAs(payrollManager())->get(route('payroll.index'))->assertOk()->assertSee('Paramétrage incomplet');
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('payroll.rubrics.index'))->assertOk();
});

it('creates rubrics from the form, including a tax scale', function () {
    $manager = payrollManager();

    $this->actingAs($manager)->post(route('payroll.rubrics.store'), [
        'code' => 'IMPOT', 'label' => 'Impôt test', 'kind' => 'tax', 'position' => 50, 'active' => '1',
        'base' => 'taxable', 'abatement_rate' => '20', 'round_base_to' => '100', 'deduct_contributions' => '1',
        'brackets' => "0;50 000;0\n50000;100000;10\n100000;;20",
        'family_reductions' => "1;10\n2;20",
    ])->assertRedirect(route('payroll.rubrics.index'));

    $rules = PayrollRubric::where('code', 'IMPOT')->firstOrFail()->tax_rules;
    expect($rules['brackets'][0])->toEqual(['from' => 0, 'to' => 50000, 'rate' => 0])
        ->and($rules['brackets'][2]['to'])->toBeNull()
        ->and($rules['family_reductions'])->toEqual([1 => 10, 2 => 20])
        ->and($rules['deduct_contributions'])->toBeTrue();

    $this->actingAs($manager)->post(route('payroll.rubrics.store'), [
        'code' => 'IMPOT2', 'label' => 'Impôt', 'kind' => 'tax', 'position' => 50, 'brackets' => "0;abc;0",
    ])->assertSessionHasErrors('brackets');
    $this->actingAs($manager)->post(route('payroll.rubrics.store'), [
        'code' => 'cnss bad', 'label' => 'X', 'kind' => 'gain', 'position' => 1,
    ])->assertSessionHasErrors('code');
    $this->actingAs($manager)->post(route('payroll.rubrics.store'), [
        'code' => 'COTIS', 'label' => 'X', 'kind' => 'contribution', 'position' => 1,
    ])->assertSessionHasErrors('employee_rate');
});

it('runs a full monthly payroll and publishes payslips at closing', function () {
    $r = payrollRubrics();
    $manager = payrollManager();
    $employee = User::factory()->create(['name' => 'Salif Paie', 'matricule' => 'M001']);
    $other = User::factory()->create();

    $this->actingAs($manager)->put(route('payroll.profiles.update', $employee), [
        'base_salary' => 200000, 'dependents' => 2, 'payment_method' => 'virement', 'bank_name' => 'Banque test', 'bank_account' => '000123', 'active' => '1',
        'fixed' => [$r['prime']->id => 50000, $r['transport']->id => 20000, $r['avance']->id => ''],
    ])->assertRedirect(route('payroll.profiles.index'));

    $this->post(route('payroll.periods.store'), ['month' => '2026-11'])->assertRedirect();
    $period = PayrollPeriod::firstOrFail();
    $this->post(route('payroll.periods.store'), ['month' => '2026-12'])->assertStatus(409);

    $this->put(route('payroll.periods.entry', [$period, $employee]), ['unpaid_days' => 3])->assertRedirect();
    $this->post(route('payroll.periods.variables.store', [$period, $employee]), ['payroll_rubric_id' => $r['avance']->id, 'amount' => 10000, 'note' => 'novembre'])->assertRedirect();
    $this->get(route('payroll.periods.employee', [$period, $employee]))->assertOk()->assertSee('212 968');

    $this->post(route('payroll.periods.calculate', $period))->assertRedirect();
    $payslip = Payslip::where('user_id', $employee->id)->firstOrFail();
    expect($payslip->net)->toBe(212968.0)
        ->and($payslip->employee['bank_account'])->toBe('000123');

    $this->get(route('payroll.periods.show', $period))->assertOk()->assertSee('Salif Paie')->assertSee('212 968');
    $this->get(route('payroll.periods.ledger', $period))->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect(substr($this->get(route('payroll.periods.batch', $period))->assertOk()->getContent(), 0, 4))->toBe('%PDF');

    // L'employé ne voit rien avant la clôture.
    $this->actingAs($employee)->get(route('intranet.payslips.download', $payslip))->assertNotFound();

    // Un changement de fiche impose un recalcul avant la clôture.
    $this->actingAs($manager)->put(route('payroll.profiles.update', $employee), [
        'base_salary' => 210000, 'dependents' => 2, 'payment_method' => 'virement', 'active' => '1',
        'fixed' => [$r['prime']->id => 50000, $r['transport']->id => 20000],
    ]);
    expect($payslip->fresh()->stale)->toBeTrue();
    $this->post(route('payroll.periods.close', $period), ['confirmation' => 'Novembre 2026'])->assertStatus(422);

    $this->post(route('payroll.periods.calculate', $period));
    $this->post(route('payroll.periods.close', $period), ['confirmation' => 'octobre 2026'])->assertSessionHasErrors('confirmation');
    $this->post(route('payroll.periods.close', $period), ['confirmation' => 'novembre 2026'])->assertRedirect();
    expect($period->fresh()->isClosed())->toBeTrue();

    $this->post(route('payroll.periods.calculate', $period))->assertStatus(409);
    $this->put(route('payroll.periods.entry', [$period, $employee]), ['unpaid_days' => 0])->assertStatus(409);

    $this->actingAs($employee)->get(route('intranet.payslips.index'))->assertOk()->assertSee('Novembre 2026');
    expect(substr($this->actingAs($employee)->get(route('intranet.payslips.download', $payslip))->assertOk()->getContent(), 0, 4))->toBe('%PDF');
    $this->actingAs($other)->get(route('intranet.payslips.download', $payslip))->assertNotFound();
    $this->actingAs($employee)->get(route('payroll.payslips.show', $payslip))->assertForbidden();
});

it('excludes inactive profiles from the payroll run', function () {
    payrollRubrics();
    $active = User::factory()->create();
    $inactive = User::factory()->create();
    PayrollProfile::create(['user_id' => $active->id, 'base_salary' => 100000, 'active' => true]);
    PayrollProfile::create(['user_id' => $inactive->id, 'base_salary' => 100000, 'active' => false]);
    $period = PayrollPeriod::create(['year' => 2026, 'month' => 11, 'status' => 'open']);

    $this->actingAs(payrollManager())->post(route('payroll.periods.calculate', $period));

    expect(Payslip::pluck('user_id')->all())->toBe([$active->id]);
});

it('keeps a disabled payroll module visible to system administrators only', function () {
    app(\App\Support\IntranetModuleRegistry::class)->setEnabled('payroll', false);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs(payrollManager())->get(route('payroll.index'))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('intranet.payslips.index'))->assertNotFound();
    $this->actingAs($admin)->get(route('payroll.index'))->assertOk();
    $this->actingAs($admin)->get(route('intranet.dashboard'))->assertSee('Masqué');
});
