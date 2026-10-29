<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MenuCampaign;
use App\Models\MenuRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCanChoose($request);

        $campaigns = MenuCampaign::query()
            ->openForRegistration()
            ->with('days')
            ->latest('starts_on')
            ->paginate(20);

        return view('menu.portal', compact('campaigns'));
    }

    public function show(MenuCampaign $campaign, Request $request): View
    {
        $this->ensureCanChoose($request);
        abort_if($campaign->status === 'draft', 404);

        $employeeIdentifier = $this->employeeIdentifier($request);
        $registration = $campaign->registrations()
            ->where('employee_identifier', $employeeIdentifier)
            ->with('choices')
            ->first();

        return view('menu.registration.show', [
            'campaign' => $campaign->load('days'),
            'registration' => $registration,
            'departments' => Department::active()->orderBy('name')->get(['name']),
            'canRegister' => $campaign->status === 'open'
                && ($campaign->registration_closes_at === null || $campaign->registration_closes_at->isFuture()),
        ]);
    }

    public function store(MenuCampaign $campaign, Request $request): RedirectResponse
    {
        $this->ensureCanChoose($request);
        abort_unless(
            $campaign->status === 'open'
                && ($campaign->registration_closes_at === null || $campaign->registration_closes_at->isFuture()),
            409,
            'Les inscriptions à cette semaine de menus sont clôturées.'
        );

        $validated = $request->validate([
            'department' => ['required', 'string', 'max:150'],
            'choices' => ['required', 'array', 'max:5'],
            'choices.*' => ['nullable', 'integer', 'in:1,2'],
        ]);

        $campaignDays = $campaign->days()->get();
        $submittedDayIds = collect(array_keys($validated['choices']))->map(fn ($id) => (int) $id);
        if ($submittedDayIds->diff($campaignDays->modelKeys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'choices' => 'Un ou plusieurs jours ne font pas partie de cette semaine de menus.',
            ]);
        }

        $user = $request->user();
        $employeeIdentifier = $this->employeeIdentifier($request);

        DB::transaction(function () use ($campaign, $validated, $campaignDays, $user, $employeeIdentifier) {
            $registration = MenuRegistration::query()->updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'employee_identifier' => $employeeIdentifier,
                ],
                [
                    'user_id' => $user->id,
                    'employee_name' => $user->name,
                    'department' => trim($validated['department']),
                ],
            );

            $registration->choices()->delete();

            foreach ($campaignDays as $day) {
                $menu = $validated['choices'][$day->id] ?? null;
                if ($menu !== null && $menu !== '') {
                    $registration->choices()->create([
                        'menu_day_id' => $day->id,
                        'selected_menu' => (int) $menu,
                    ]);
                }
            }
        });

        return redirect()
            ->route('menu.registration.show', $campaign->link_token)
            ->with('success', 'Vos choix de menus ont été enregistrés.');
    }

    private function ensureCanChoose(Request $request): void
    {
        abort_unless(
            $request->user()->canChooseMenu(),
            403,
            'Le choix des menus est réservé aux employés désignés par l’administration.'
        );
    }

    private function employeeIdentifier(Request $request): string
    {
        $user = $request->user();

        if (filled($user->domain) && filled($user->guid)) {
            return strtolower($user->domain).'|'.strtolower($user->guid);
        }

        return 'local|'.$user->getKey();
    }
}
