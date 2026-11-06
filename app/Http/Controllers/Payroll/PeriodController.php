<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\PayrollProfile;
use App\Models\PayrollRubric;
use App\Models\PayrollVariable;
use App\Models\Payslip;
use App\Models\User;
use App\Services\AdminAudit;
use App\Services\Payroll\PayrollLedgerExporter;
use App\Services\Payroll\PayrollService;
use App\Services\Payroll\PayslipPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeriodController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    public function index(): View
    {
        return view('payroll.index', [
            'periods' => PayrollPeriod::query()->withCount('payslips')->withSum('payslips', 'net')->orderByDesc('year')->orderByDesc('month')->paginate(12),
            'openPeriod' => PayrollPeriod::where('status', 'open')->first(),
            'missing' => [
                'contribution' => ! PayrollRubric::where('active', true)->where('kind', PayrollRubric::CONTRIBUTION)->exists(),
                'tax' => ! PayrollRubric::where('active', true)->where('kind', PayrollRubric::TAX)->exists(),
                'profiles' => ! PayrollProfile::where('active', true)->exists(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        abort_if(PayrollPeriod::where('status', 'open')->exists(), 409, 'Clôturez la paie en cours avant d’en ouvrir une autre.');

        [$year, $month] = array_map('intval', explode('-', $validated['month']));
        if (PayrollPeriod::where('year', $year)->where('month', $month)->exists()) {
            throw ValidationException::withMessages(['month' => 'Cette paie existe déjà.']);
        }

        $period = PayrollPeriod::create(['year' => $year, 'month' => $month, 'status' => 'open']);
        AdminAudit::record('ouverture paie', 'paie', $period, null, ['mois' => $period->label()], 'Paie '.$period->label());

        return redirect()->route('payroll.periods.show', $period)->with('success', 'La paie de '.$period->label().' est ouverte.');
    }

    public function show(PayrollPeriod $period): View
    {
        $payslips = $period->payslips()->orderBy('employee->name')->get()->keyBy('user_id');
        $profiles = $period->isClosed()
            ? collect()
            : PayrollProfile::query()->where('active', true)->whereHas('user', fn ($q) => $q->where('actif', true))->with('user')->get()->sortBy('user.name');

        return view('payroll.period', [
            'period' => $period,
            'payslips' => $payslips,
            'profiles' => $profiles,
            'entries' => $period->entries()->pluck('unpaid_days', 'user_id'),
            'variableCounts' => $period->variables()->selectRaw('user_id, COUNT(*) as total')->groupBy('user_id')->pluck('total', 'user_id'),
            'totals' => [
                'gross' => $payslips->sum('gross'),
                'employee_contributions' => $payslips->sum('employee_contributions'),
                'employer_contributions' => $payslips->sum('employer_contributions'),
                'tax' => $payslips->sum('tax'),
                'deductions' => $payslips->sum('deductions'),
                'net' => $payslips->sum('net'),
            ],
        ]);
    }

    public function calculate(PayrollPeriod $period): RedirectResponse
    {
        $count = $this->payroll->run($period);
        AdminAudit::record('calcul paie', 'paie', $period, null, ['bulletins' => $count], 'Paie '.$period->label());

        return back()->with('success', $count.' bulletin(s) calculé(s).');
    }

    public function close(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $expected = Str::lower(Str::ascii($period->label()));
        $typed = Str::lower(Str::ascii(trim((string) $request->input('confirmation'))));
        if ($typed !== $expected) {
            throw ValidationException::withMessages(['confirmation' => 'Tapez exactement « '.$period->label().' » pour clôturer.']);
        }

        $this->payroll->close($period, $request->user()->id);
        AdminAudit::record('clôture paie', 'paie', $period, null, ['net_total' => $period->payslips()->sum('net')], 'Paie '.$period->label());

        return back()->with('success', 'La paie de '.$period->label().' est clôturée : les bulletins sont visibles par les salariés.');
    }

    public function employee(PayrollPeriod $period, User $user): View
    {
        $profile = PayrollProfile::where('user_id', $user->id)->firstOrFail();

        return view('payroll.employee', [
            'period' => $period,
            'user' => $user,
            'profile' => $profile,
            'unpaidDays' => (float) (PayrollEntry::where('payroll_period_id', $period->id)->where('user_id', $user->id)->value('unpaid_days') ?? 0),
            'variables' => PayrollVariable::where('payroll_period_id', $period->id)->where('user_id', $user->id)->with('rubric')->get(),
            'rubrics' => PayrollRubric::where('active', true)->whereIn('kind', [PayrollRubric::GAIN, PayrollRubric::DEDUCTION])->orderBy('kind')->orderBy('position')->get(),
            'preview' => $this->payroll->preview($period, $profile),
            'payslip' => $period->payslips()->where('user_id', $user->id)->first(),
        ]);
    }

    public function updateEntry(Request $request, PayrollPeriod $period, User $user): RedirectResponse
    {
        abort_if($period->isClosed(), 409, 'Cette paie est clôturée.');
        $validated = $request->validate(['unpaid_days' => ['required', 'numeric', 'between:0,31']]);

        PayrollEntry::updateOrCreate(['payroll_period_id' => $period->id, 'user_id' => $user->id], ['unpaid_days' => $validated['unpaid_days']]);
        $this->recalculateIfExists($period, $user);

        return back()->with('success', 'Absences enregistrées.');
    }

    public function storeVariable(Request $request, PayrollPeriod $period, User $user): RedirectResponse
    {
        abort_if($period->isClosed(), 409, 'Cette paie est clôturée.');
        $validated = $request->validate([
            'payroll_rubric_id' => ['required', Rule::exists('payroll_rubrics', 'id')->whereIn('kind', [PayrollRubric::GAIN, PayrollRubric::DEDUCTION])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'note' => ['nullable', 'string', 'max:100'],
        ]);

        PayrollVariable::create([...$validated, 'payroll_period_id' => $period->id, 'user_id' => $user->id]);
        $this->recalculateIfExists($period, $user);

        return back()->with('success', 'Élément variable ajouté.');
    }

    public function destroyVariable(PayrollPeriod $period, PayrollVariable $variable): RedirectResponse
    {
        abort_if($period->isClosed() || $variable->payroll_period_id !== $period->id, 409);

        $user = User::findOrFail($variable->user_id);
        $variable->delete();
        $this->recalculateIfExists($period, $user);

        return back()->with('success', 'Élément variable supprimé.');
    }

    public function payslip(Payslip $payslip, PayslipPdf $pdf): Response
    {
        return $pdf->download(collect([$payslip]), 'bulletin-'.Str::slug($payslip->employee['name'] ?? 'salarie').'-'.$this->suffix($payslip->period));
    }

    public function batch(PayrollPeriod $period, PayslipPdf $pdf): Response
    {
        $payslips = $period->payslips()->orderBy('employee->name')->get();
        abort_if($payslips->isEmpty(), 404);

        return $pdf->download($payslips, 'bulletins-'.$this->suffix($period));
    }

    public function ledger(PayrollPeriod $period, PayrollLedgerExporter $exporter): StreamedResponse
    {
        return $exporter->download($period);
    }

    private function recalculateIfExists(PayrollPeriod $period, User $user): void
    {
        $profile = PayrollProfile::where('user_id', $user->id)->where('active', true)->with(['user', 'rubrics'])->first();
        if ($profile && $period->payslips()->where('user_id', $user->id)->exists()) {
            $this->payroll->calculateFor($period, $profile);
        }
    }

    private function suffix(PayrollPeriod $period): string
    {
        return sprintf('%04d-%02d', $period->year, $period->month);
    }
}
