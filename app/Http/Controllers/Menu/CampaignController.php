<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\MenuCampaign;
use App\Models\User;
use App\Notifications\MenuCampaignNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

    public function create(Request $request): View
    {
        return view('menu.manager.create', [
            'week' => $request->old('week', now()->startOfWeek()->format('o-\WW')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week' => ['required', 'regex:/^\d{4}-W\d{2}$/'],
            'registration_closes_at' => ['nullable', 'date', 'after:now'],
            'status' => ['required', 'in:draft,open'],
            'days' => ['required', 'array', 'size:5'],
            'days.*.menu_one' => ['required', 'string', 'max:1000'],
            'days.*.menu_two' => ['required', 'string', 'max:1000'],
            'days.*.dessert' => ['required', 'string', 'max:255'],
        ]);

        if (array_keys($validated['days']) !== range(0, 4)) {
            throw ValidationException::withMessages([
                'days' => 'Renseignez les menus du lundi au vendredi.',
            ]);
        }

        [$isoYear, $isoWeek] = array_map('intval', explode('-W', $validated['week']));
        $firstDate = Carbon::now()->setISODate($isoYear, $isoWeek, 1)->startOfDay();
        if ($firstDate->format('o-\WW') !== $validated['week']) {
            throw ValidationException::withMessages([
                'week' => 'La semaine sélectionnée n’existe pas.',
            ]);
        }
        $lastDate = $firstDate->copy()->addDays(4);
        $days = collect($validated['days'])
            ->map(fn (array $day, int $offset): array => [
                ...$day,
                'date' => $firstDate->copy()->addDays($offset)->toDateString(),
            ]);

        $closeAt = isset($validated['registration_closes_at'])
            ? Carbon::parse($validated['registration_closes_at'])
            : null;

        $title = 'Menus du '.$firstDate->locale('fr')->translatedFormat('d').' au '.$lastDate->locale('fr')->translatedFormat('d F Y');
        $campaign = DB::transaction(function () use ($request, $validated, $days, $firstDate, $lastDate, $closeAt, $title) {
            $campaign = MenuCampaign::create([
                'title' => $title,
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
                    ...$day,
                ]);
            }

            return $campaign;
        });

        if ($campaign->status === 'open') {
            $this->notifyChoosers($campaign);
        }

        return redirect()
            ->route('menu.manager.campaigns')
            ->with('success', 'La semaine de menus a été créée.')
            ->with('createdCampaign', $campaign->id);
    }

    public function publish(MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'draft', 409, 'Cette semaine est déjà publiée.');

        $campaign->update(['status' => 'open']);
        $this->notifyChoosers($campaign);

        return back()->with('success', 'La semaine de menus est publiée : les employés désignés sont notifiés.');
    }

    public function tracking(MenuCampaign $campaign): View
    {
        abort_if($campaign->status === 'draft', 404);

        $registeredUserIds = $campaign->registrations()->whereNotNull('user_id')->pluck('user_id');
        [$chosen, $pending] = User::query()
            ->menuChoosers()
            ->with('departments:id,name')
            ->orderBy('name')
            ->get()
            ->partition(fn (User $user) => $registeredUserIds->contains($user->id));

        return view('menu.manager.tracking', compact('campaign', 'chosen', 'pending'));
    }

    public function close(MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'open', 409, 'Cette campagne n’est pas ouverte.');

        $campaign->update(['status' => 'closed']);

        return back()->with('success', 'Les inscriptions à cette campagne sont clôturées.');
    }

    private function notifyChoosers(MenuCampaign $campaign): void
    {
        Notification::send(User::query()->menuChoosers()->get(), new MenuCampaignNotification($campaign));
    }
}
