<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\MenuCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = MenuCampaign::query()
            ->withCount('registrations')
            ->latest('starts_on')
            ->paginate(20);

        return view('menu.manager.index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('menu.manager.create', [
            'weekdays' => collect(range(0, 4))
                ->map(fn (int $offset) => now()->startOfWeek()->addDays($offset)->toDateString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'registration_closes_at' => ['nullable', 'date', 'after:now'],
            'status' => ['required', 'in:draft,open'],
            'days' => ['required', 'array', 'min:1', 'max:5'],
            'days.*.date' => ['required', 'date', 'distinct'],
            'days.*.menu_one' => ['required', 'string', 'max:1000'],
            'days.*.menu_two' => ['required', 'string', 'max:1000'],
            'days.*.dessert' => ['required', 'string', 'max:255'],
        ]);

        $days = collect($validated['days'])
            ->map(fn (array $day): array => [...$day, 'date' => Carbon::parse($day['date'])->startOfDay()])
            ->sortBy('date')
            ->values();
        $firstDate = $days->first()['date'];
        $lastDate = $days->last()['date'];

        if ($firstDate->diffInDays($lastDate) > 6 || $days->contains(fn (array $day) => $day['date']->isWeekend())) {
            throw ValidationException::withMessages([
                'days' => 'Les menus doivent concerner des jours ouvrés compris dans une même semaine.',
            ]);
        }

        $closeAt = isset($validated['registration_closes_at'])
            ? Carbon::parse($validated['registration_closes_at'])
            : null;

        $campaign = DB::transaction(function () use ($request, $validated, $days, $firstDate, $lastDate, $closeAt) {
            $campaign = MenuCampaign::create([
                'title' => $validated['title'],
                'link_token' => Str::random(64),
                'starts_on' => $firstDate->toDateString(),
                'ends_on' => $lastDate->toDateString(),
                'registration_closes_at' => $closeAt,
                'status' => $validated['status'],
                'created_by' => $request->user()->id,
            ]);

            foreach ($days as $day) {
                $campaign->days()->create([
                    ...$day,
                    'date' => $day['date']->toDateString(),
                ]);
            }

            return $campaign;
        });

        return redirect()
            ->route('menu.manager.campaigns')
            ->with('success', 'La semaine de menus a été créée.')
            ->with('createdCampaign', $campaign->id);
    }

    public function close(MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'open', 409, 'Cette campagne n’est pas ouverte.');

        $campaign->update(['status' => 'closed']);

        return back()->with('success', 'Les inscriptions à cette campagne sont clôturées.');
    }
}
