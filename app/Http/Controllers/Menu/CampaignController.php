<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\MenuCampaign;
use App\Models\MenuGuest;
use App\Models\User;
use App\Notifications\MenuCampaignNotification;
use App\Notifications\MenuChoicesIncomplete;
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
            ->paginate(10);

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
            $this->notifyChoosers($campaign, sendMail: $validated['status'] === 'open' && $request->boolean('send_mail'));
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
        $this->notifyChoosers($campaign, sendMail: false);

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

        $guestIdentifiers = $campaign->registrations()->whereNull('user_id')->pluck('employee_identifier');
        $guests = MenuGuest::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->each(fn (MenuGuest $guest) => $guest->setAttribute('has_chosen', $guestIdentifiers->contains($guest->employeeIdentifier())));

        $missing = $this->missingDaysByUser($campaign);

        return view('menu.manager.tracking', compact('campaign', 'chosen', 'pending', 'guests', 'missing'));
    }

    public function close(MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'open', 409, 'Cette campagne n’est pas ouverte.');

        $campaign->update(['status' => 'closed']);

        return back()->with('success', 'Les inscriptions à cette campagne sont clôturées.');
    }

    public function reopen(MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'closed', 409, 'Seule une semaine clôturée peut être rouverte.');

        // Une date de clôture déjà passée refermerait aussitôt la semaine.
        $campaign->update([
            'status' => 'open',
            'registration_closes_at' => $campaign->registration_closes_at?->isFuture() ? $campaign->registration_closes_at : null,
        ]);

        return back()->with('success', 'La semaine est rouverte : les employés peuvent de nouveau modifier leurs choix.');
    }

    public function destroy(MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'closed', 409, 'Clôturez la semaine avant de la supprimer.');

        $campaign->delete();

        return redirect()->route('menu.manager.campaigns')->with('success', 'La semaine « '.$campaign->title.' » et ses choix ont été supprimés.');
    }

    private function notifyChoosers(MenuCampaign $campaign, bool $reminder = false, bool $sendMail = false): void
    {
        Notification::send(
            User::query()->menuChoosers()->get(),
            new MenuCampaignNotification($campaign, $reminder, $sendMail)
        );
    }

    /**
     * Envoie un rappel par e-mail aux employés qui n'ont pas encore choisi.
     */
    /** Rappel ou avertissement à une seule personne, selon où elle en est. */
    public function remindOne(MenuCampaign $campaign, User $user): RedirectResponse
    {
        abort_unless($campaign->status === 'open', 409, 'Les inscriptions ne sont pas ouvertes.');
        abort_unless(User::query()->menuChoosers()->whereKey($user->id)->exists(), 404);

        $registered = $campaign->registrations()->where('user_id', $user->id)->exists();
        if (! $registered) {
            $user->notify(new MenuCampaignNotification($campaign, reminder: true, sendMail: true));

            return back()->with('success', "Rappel envoyé à {$user->name}.");
        }

        $days = $this->missingDaysByUser($campaign)[$user->id] ?? [];
        abort_if($days === [], 409, 'Cette personne a choisi un plat pour chaque jour.');
        $user->notify(new MenuChoicesIncomplete($campaign, $days));

        return back()->with('success', "Avertissement envoyé à {$user->name} pour ".count($days).' jour(s) sans plat.');
    }

    /** Avertit tous les inscrits qui n'ont pas choisi de plat pour chaque jour. */
    public function warnIncomplete(MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'open', 409, 'Les inscriptions ne sont pas ouvertes.');

        $missing = $this->missingDaysByUser($campaign);
        $users = User::query()->menuChoosers()->whereKey(array_keys($missing))->get();
        foreach ($users as $user) {
            $user->notify(new MenuChoicesIncomplete($campaign, $missing[$user->id]));
        }

        return back()->with('success', $users->isEmpty()
            ? 'Tous les inscrits ont choisi un plat pour chaque jour.'
            : "Avertissement envoyé à {$users->count()} personne(s) ayant des jours sans plat.");
    }

    /**
     * Jours sans plat choisi, par employé inscrit.
     *
     * @return array<int, list<string>>
     */
    private function missingDaysByUser(MenuCampaign $campaign): array
    {
        $days = $campaign->days()->orderBy('date')->get();
        $missing = [];

        foreach ($campaign->registrations()->whereNotNull('user_id')->with('choices:id,registration_id,menu_day_id')->get() as $registration) {
            $chosenDayIds = $registration->choices->pluck('menu_day_id');
            $labels = $days->reject(fn ($day) => $chosenDayIds->contains($day->id))
                ->map(fn ($day) => $day->date->locale('fr')->translatedFormat('l j F'))
                ->values()->all();
            if ($labels !== []) {
                $missing[$registration->user_id] = $labels;
            }
        }

        return $missing;
    }

    public function sendReminder(Request $request, MenuCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'open', 409, 'Les inscriptions ne sont pas ouvertes.');

        $registeredUserIds = $campaign->registrations()->whereNotNull('user_id')->pluck('user_id');
        $pending = User::query()
            ->menuChoosers()
            ->whereNotIn('id', $registeredUserIds)
            ->get();

        if ($pending->isNotEmpty()) {
            Notification::send($pending, new MenuCampaignNotification($campaign, reminder: true, sendMail: true));
            $campaign->update(['reminder_sent_at' => now()]);
        }

        return back()->with(
            'success',
            $pending->isNotEmpty()
                ? "Rappel envoyé par e-mail à {$pending->count()} employé(s) n'ayant pas encore choisi."
                : "Tous les employés ont déjà choisi leurs plats."
        );
    }
}
