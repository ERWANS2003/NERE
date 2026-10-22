<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeOffBalance;
use App\Models\TimeOffPolicy;
use App\Models\User;
use App\Support\IntranetModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimeOffManagementController extends Controller
{
    public function index(Request $request, IntranetModuleRegistry $modules): View
    {
        $year = $request->integer('year') ?: (int) now()->year;

        $balances = TimeOffBalance::query()
            ->with(['user', 'policy'])
            ->where('year', $year)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->whereHas('user', fn ($users) => $users
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderBy('user_id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.time-off.index', [
            'balances' => $balances,
            'policies' => TimeOffPolicy::orderBy('name')->get(),
            'users' => User::active()->orderBy('name')->get(['id', 'name', 'email']),
            'year' => $year,
            'moduleEnabled' => $modules->enabled('time_off'),
        ]);
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $validated = $this->validatePolicy($request);
        TimeOffPolicy::create($validated);

        return back()->with('success', 'Politique de congés créée.');
    }

    public function updatePolicy(Request $request, TimeOffPolicy $policy): RedirectResponse
    {
        $validated = $this->validatePolicy($request, $policy);
        $policy->update($validated);

        return back()->with('success', 'Politique de congés mise à jour.');
    }

    public function deactivatePolicy(TimeOffPolicy $policy): RedirectResponse
    {
        $policy->update(['is_active' => false]);

        return back()->with('success', 'Politique désactivée. Les soldes et demandes existants sont conservés.');
    }

    public function storeBalance(Request $request): RedirectResponse
    {
        $validated = $this->validateBalance($request, true);

        $this->saveBalance($validated);

        return back()->with('success', 'Solde enregistré sans modifier les jours déjà consommés.');
    }

    public function updateBalance(Request $request, TimeOffBalance $balance): RedirectResponse
    {
        $validated = $this->validateBalance($request);

        $this->saveBalance($validated + [
            'user_id' => $balance->user_id,
            'policy_id' => $balance->policy_id,
            'year' => $balance->year,
        ], $balance);

        return back()->with('success', 'Solde ajusté. L’historique des jours consommés est conservé.');
    }

    private function validatePolicy(Request $request, ?TimeOffPolicy $policy = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('time_off_policies', 'type')->ignore($policy?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'annual_allocation' => ['required', 'integer', 'min:0', 'max:366'],
            'carry_over_limit' => ['required', 'integer', 'min:0', 'max:366'],
            'advance_notice_days' => ['required', 'integer', 'min:0', 'max:366'],
            'requires_approval' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function validateBalance(Request $request, bool $includeIdentity = false): array
    {
        $rules = [
            'total_days' => ['required', 'numeric', 'min:0', 'max:366', 'decimal:0,1'],
            'carry_over_days' => ['required', 'numeric', 'min:0', 'max:366', 'decimal:0,1'],
        ];

        if ($includeIdentity) {
            $rules += [
                'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('actif', true)],
                'policy_id' => ['required', 'exists:time_off_policies,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            ];
        }

        return $request->validate($rules);
    }

    private function saveBalance(array $validated, ?TimeOffBalance $balance = null): void
    {
        DB::transaction(function () use ($validated, $balance) {
            $balance = $balance
                ? TimeOffBalance::whereKey($balance->id)->lockForUpdate()->firstOrFail()
                : TimeOffBalance::firstOrNew([
                    'user_id' => $validated['user_id'],
                    'policy_id' => $validated['policy_id'],
                    'year' => $validated['year'],
                ]);

            $usedDays = (float) $balance->used_days;
            $availableDays = (float) $validated['total_days'] + (float) $validated['carry_over_days'];

            $policy = TimeOffPolicy::findOrFail($validated['policy_id']);
            if ((float) $validated['carry_over_days'] > $policy->carry_over_limit) {
                throw ValidationException::withMessages([
                    'carry_over_days' => 'Le report dépasse la limite définie par la politique.',
                ]);
            }

            if ($availableDays < $usedDays) {
                throw ValidationException::withMessages([
                    'total_days' => 'Le solde total ne peut pas être inférieur aux jours déjà consommés.',
                ]);
            }

            $balance->fill([
                'total_days' => $validated['total_days'],
                'carry_over_days' => $validated['carry_over_days'],
                'remaining_days' => $availableDays - $usedDays,
            ])->save();
        });
    }
}
