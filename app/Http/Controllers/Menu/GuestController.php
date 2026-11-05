<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\MenuCampaign;
use App\Models\MenuGuest;
use App\Models\MenuRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(): View
    {
        return view('menu.manager.guests', [
            'guests' => MenuGuest::query()->orderByDesc('active')->orderBy('name')->get(),
            'departments' => Department::active()->orderBy('name')->get(['name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MenuGuest::create($this->validated($request));

        return back()->with('success', 'La personne a été ajoutée.');
    }

    public function update(Request $request, MenuGuest $guest): RedirectResponse
    {
        $guest->update($this->validated($request) + ['active' => $request->boolean('active')]);

        return back()->with('success', 'La personne a été modifiée.');
    }

    public function destroy(MenuGuest $guest): RedirectResponse
    {
        $guest->delete();

        return back()->with('success', 'La personne a été retirée de la liste. Ses choix déjà enregistrés sont conservés.');
    }

    public function editChoices(MenuCampaign $campaign, MenuGuest $guest): View
    {
        abort_if($campaign->status === 'draft', 404);

        return view('menu.manager.guest-choices', [
            'campaign' => $campaign->load('days'),
            'guest' => $guest,
            'registration' => $this->registration($campaign, $guest)?->load('choices'),
        ]);
    }

    public function storeChoices(Request $request, MenuCampaign $campaign, MenuGuest $guest): RedirectResponse
    {
        abort_if($campaign->status === 'draft', 404);

        $validated = $request->validate([
            'choices' => ['nullable', 'array', 'max:5'],
            'choices.*' => ['nullable', 'integer', 'in:1,2'],
        ]);
        $choices = collect($validated['choices'] ?? [])->filter(fn ($menu) => $menu !== null && $menu !== '');

        $campaignDays = $campaign->days()->get();
        if ($choices->keys()->map(fn ($id) => (int) $id)->diff($campaignDays->modelKeys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'choices' => 'Un ou plusieurs jours ne font pas partie de cette semaine de menus.',
            ]);
        }

        DB::transaction(function () use ($campaign, $guest, $choices, $campaignDays) {
            if ($choices->isEmpty()) {
                $this->registration($campaign, $guest)?->delete();

                return;
            }

            $registration = MenuRegistration::query()->updateOrCreate(
                ['campaign_id' => $campaign->id, 'employee_identifier' => $guest->employeeIdentifier()],
                ['user_id' => null, 'employee_name' => $guest->name, 'department' => $guest->department],
            );
            $registration->choices()->delete();

            foreach ($campaignDays as $day) {
                if ($choices->has($day->id)) {
                    $registration->choices()->create([
                        'menu_day_id' => $day->id,
                        'selected_menu' => (int) $choices->get($day->id),
                    ]);
                }
            }
        });

        return redirect()
            ->route('menu.manager.tracking', $campaign)
            ->with('success', 'Les choix de '.$guest->name.' ont été enregistrés.');
    }

    private function registration(MenuCampaign $campaign, MenuGuest $guest): ?MenuRegistration
    {
        return $campaign->registrations()->where('employee_identifier', $guest->employeeIdentifier())->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:150'],
            'matricule' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
