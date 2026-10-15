<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\WeeklyMenu;
use App\Models\WeeklyMenuChoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Espace employé : consultation du menu de la semaine à venir et saisie des
 * choix (un choix indépendant par jour). Réservé aux utilisateurs rattachés à
 * une localisation de restauration.
 */
class MenuChoiceController extends Controller
{
    private const CHOICES = [
        WeeklyMenuChoice::MENU_1,
        WeeklyMenuChoice::MENU_2,
        WeeklyMenuChoice::NO_MEAL,
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->meal_location_id, 403, "Vous n'êtes rattaché à aucun service de restauration.");

        $menu = WeeklyMenu::with('days')
            ->where('meal_location_id', $user->meal_location_id)
            ->where('status', WeeklyMenu::PUBLISHED)
            ->orderByDesc('week_start')
            ->first();

        $choices = [];
        if ($menu) {
            $choices = WeeklyMenuChoice::whereIn('weekly_menu_day_id', $menu->days->pluck('id'))
                ->where('user_id', $user->id)
                ->pluck('choice', 'weekly_menu_day_id')
                ->all();
        }

        return view('intranet.menu.index', [
            'menu' => $menu,
            'choices' => $choices,
            'open' => $menu?->isOpen() ?? false,
        ]);
    }

    public function store(Request $request, WeeklyMenu $menu): RedirectResponse
    {
        $user = $request->user();

        abort_unless($menu->meal_location_id === $user->meal_location_id, 403, 'Ce menu ne vous concerne pas.');
        abort_unless($menu->isOpen(), 403, 'La période de réponse est close.');

        $validated = $request->validate([
            'choices' => ['required', 'array'],
            'choices.*' => ['required', 'in:'.implode(',', self::CHOICES)],
        ]);

        $dayIds = $menu->days()->pluck('id')->all();

        DB::transaction(function () use ($validated, $user, $dayIds) {
            foreach ($validated['choices'] as $dayId => $choice) {
                // On ignore toute clé qui ne correspond pas à un jour de ce menu.
                if (! in_array((int) $dayId, $dayIds, true)) {
                    continue;
                }

                WeeklyMenuChoice::updateOrCreate(
                    ['weekly_menu_day_id' => (int) $dayId, 'user_id' => $user->id],
                    ['choice' => $choice, 'selected_at' => now()],
                );
            }
        });

        return redirect()
            ->route('intranet.menu.index')
            ->with('success', 'Vos choix de menu ont été enregistrés.');
    }
}
