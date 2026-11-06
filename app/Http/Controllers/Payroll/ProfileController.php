<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollProfile;
use App\Models\PayrollRubric;
use App\Models\User;
use App\Services\AdminAudit;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Fiche de paie de chaque salarié : salaire de base, situation, éléments fixes. */
class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = $request->query('statut');

        $users = User::query()
            ->active()
            ->with('payrollProfile')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('matricule', 'like', "%{$search}%")))
            ->when($status === 'avec', fn ($query) => $query->whereHas('payrollProfile', fn ($p) => $p->where('active', true)))
            ->when($status === 'sans', fn ($query) => $query->whereDoesntHave('payrollProfile', fn ($p) => $p->where('active', true)))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('payroll.profiles.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
            'paidCount' => PayrollProfile::where('active', true)->count(),
        ]);
    }

    public function edit(User $user): View
    {
        $profile = $user->payrollProfile ?? new PayrollProfile(['payment_method' => 'virement', 'active' => true, 'dependents' => 0]);

        return view('payroll.profiles.form', [
            'user' => $user,
            'profile' => $profile,
            'fixedAmounts' => $profile->exists ? $profile->rubrics->pluck('pivot.amount', 'id')->all() : [],
            'rubrics' => PayrollRubric::query()->where('active', true)->whereIn('kind', [PayrollRubric::GAIN, PayrollRubric::DEDUCTION])->orderBy('kind')->orderBy('position')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'base_salary' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'category' => ['nullable', 'string', 'max:50'],
            'contract_type' => ['nullable', 'string', 'max:50'],
            'social_security_number' => ['nullable', 'string', 'max:50'],
            'dependents' => ['required', 'integer', 'between:0,20'],
            'payment_method' => ['required', Rule::in(array_keys(PayrollProfile::PAYMENT_METHODS))],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'fixed' => ['nullable', 'array'],
            'fixed.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $before = $user->payrollProfile?->only(['base_salary', 'category', 'dependents', 'payment_method', 'active']);

        DB::transaction(function () use ($request, $user, $validated): void {
            $profile = PayrollProfile::query()->updateOrCreate(['user_id' => $user->id], [
                ...collect($validated)->except('fixed')->all(),
                'active' => $request->boolean('active'),
            ]);

            $allowed = PayrollRubric::query()->whereIn('kind', [PayrollRubric::GAIN, PayrollRubric::DEDUCTION])->pluck('id');
            $profile->rubrics()->sync(
                collect($validated['fixed'] ?? [])
                    ->filter(fn ($amount, $id) => $amount !== null && $amount !== '' && (float) $amount > 0 && $allowed->contains((int) $id))
                    ->mapWithKeys(fn ($amount, $id) => [(int) $id => ['amount' => (float) $amount]])
                    ->all()
            );
        });

        $profile = $user->payrollProfile()->first();
        AdminAudit::record('modification fiche de paie', 'paie', $user, $before, $profile->only(['base_salary', 'category', 'dependents', 'payment_method', 'active']));
        PayrollService::markStale($user->id);

        return redirect()->route('payroll.profiles.index')->with('success', 'La fiche de paie de '.$user->name.' a été enregistrée.');
    }
}
