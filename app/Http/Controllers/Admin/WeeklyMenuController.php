<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MealLocation;
use App\Models\User;
use App\Models\WeeklyMenu;
use App\Models\WeeklyMenuChoice;
use App\Services\Menu\WeeklyMenuExporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestion administrative des menus hebdomadaires du personnel.
 *
 * Un menu cible une localisation interne (ex. « Bureaux de Ouaga »). Seuls les
 * utilisateurs actifs rattachés à cette localisation voient et répondent au
 * menu. Rien n'est écrit dans l'Active Directory.
 */
class WeeklyMenuController extends Controller
{
    private const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];

    public function index(): View
    {
        $menus = WeeklyMenu::with('location')
            ->withCount('days')
            ->latest('week_start')
            ->paginate(15);

        return view('admin.menus.index', compact('menus'));
    }

    public function create(): View
    {
        $locations = MealLocation::where('is_active', true)->orderBy('name')->get();
        $nextMonday = CarbonImmutable::now()->startOfWeek()->addWeek();

        return view('admin.menus.create', [
            'locations' => $locations,
            'jours' => self::JOURS,
            'weekStart' => $nextMonday->toDateString(),
            'weekEnd' => $nextMonday->addDays(4)->toDateString(),
            'deadline' => $nextMonday->subDays(3)->setTime(17, 0)->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meal_location_id' => ['required', 'exists:meal_locations,id'],
            'week_start' => ['required', 'date'],
            'response_deadline' => ['required', 'date'],
            'days' => ['required', 'array', 'size:5'],
            'days.*.menu_1' => ['required', 'string', 'max:500'],
            'days.*.menu_2' => ['required', 'string', 'max:500'],
            'days.*.dessert' => ['required', 'string', 'max:255'],
        ]);

        $weekStart = CarbonImmutable::parse($validated['week_start'])->startOfDay();

        $exists = WeeklyMenu::where('meal_location_id', $validated['meal_location_id'])
            ->whereDate('week_start', $weekStart)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Un menu existe déjà pour cette localisation et cette semaine.');
        }

        $menu = DB::transaction(function () use ($validated, $weekStart, $request) {
            $menu = WeeklyMenu::create([
                'meal_location_id' => $validated['meal_location_id'],
                'created_by' => $request->user()->id,
                'week_start' => $weekStart,
                'week_end' => $weekStart->addDays(4),
                'response_deadline' => CarbonImmutable::parse($validated['response_deadline']),
                'status' => WeeklyMenu::DRAFT,
            ]);

            foreach (array_values($validated['days']) as $index => $day) {
                $menu->days()->create([
                    'menu_date' => $weekStart->addDays($index),
                    'day_name' => self::JOURS[$index],
                    'menu_1' => $day['menu_1'],
                    'menu_2' => $day['menu_2'],
                    'dessert' => $day['dessert'],
                ]);
            }

            return $menu;
        });

        return redirect()->route('admin.menus.show', $menu)->with('success', 'Menu créé en brouillon.');
    }

    public function show(WeeklyMenu $menu): View
    {
        $menu->load(['location', 'days.choices.user', 'creator']);

        $eligible = $this->eligibleUsers($menu);
        $summary = $this->buildSummary($menu, $eligible);

        return view('admin.menus.show', [
            'menu' => $menu,
            'summary' => $summary,
            'eligibleCount' => $eligible->count(),
        ]);
    }

    public function publish(WeeklyMenu $menu): RedirectResponse
    {
        if ($menu->status !== WeeklyMenu::DRAFT) {
            return back()->with('error', 'Seul un menu en brouillon peut être publié.');
        }

        $menu->update(['status' => WeeklyMenu::PUBLISHED, 'published_at' => now()]);

        return back()->with('success', 'Menu publié : le personnel concerné peut désormais répondre.');
    }

    public function close(WeeklyMenu $menu): RedirectResponse
    {
        if ($menu->status !== WeeklyMenu::PUBLISHED) {
            return back()->with('error', 'Seul un menu publié peut être clôturé.');
        }

        $menu->update(['status' => WeeklyMenu::CLOSED, 'closed_at' => now()]);

        return back()->with('success', 'Menu clôturé : les réponses sont figées.');
    }

    public function destroy(WeeklyMenu $menu): RedirectResponse
    {
        if ($menu->status === WeeklyMenu::PUBLISHED) {
            return back()->with('error', 'Clôturez le menu avant de le supprimer.');
        }

        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Menu supprimé.');
    }

    public function export(WeeklyMenu $menu, WeeklyMenuExporter $exporter): StreamedResponse
    {
        $menu->load(['location', 'days.choices.user.departments']);
        $eligible = $this->eligibleUsers($menu);

        return $exporter->download($menu, $eligible);
    }

    /**
     * Utilisateurs actifs rattachés à la localisation du menu.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function eligibleUsers(WeeklyMenu $menu)
    {
        return User::query()
            ->where('actif', true)
            ->where('meal_location_id', $menu->meal_location_id)
            ->with('departments')
            ->orderBy('name')
            ->get();
    }

    /**
     * Agrège l'avancement et les totaux par jour.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $eligible
     * @return array<string, mixed>
     */
    private function buildSummary(WeeklyMenu $menu, $eligible): array
    {
        $eligibleIds = $eligible->pluck('id');
        $perDay = [];
        $completeByUser = $eligible->mapWithKeys(fn ($u) => [$u->id => 0])->all();

        foreach ($menu->days as $day) {
            $counts = ['menu_1' => 0, 'menu_2' => 0, 'no_meal' => 0];
            foreach ($day->choices as $choice) {
                if (! $eligibleIds->contains($choice->user_id)) {
                    continue;
                }
                if (isset($counts[$choice->choice])) {
                    $counts[$choice->choice]++;
                    $completeByUser[$choice->user_id] = ($completeByUser[$choice->user_id] ?? 0) + 1;
                }
            }
            $counts['no_response'] = max(0, $eligible->count() - array_sum($counts));
            $perDay[] = ['day' => $day, 'counts' => $counts];
        }

        $completed = collect($completeByUser)->filter(fn ($n) => $n >= $menu->days->count())->count();

        return [
            'perDay' => $perDay,
            'completed' => $completed,
            'incomplete' => $eligible->count() - $completed,
            'pending' => $eligible->reject(fn ($u) => ($completeByUser[$u->id] ?? 0) >= $menu->days->count())->values(),
        ];
    }
}
