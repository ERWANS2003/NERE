<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentManagementController extends Controller
{
    private const ANNOUNCEMENT_PRIORITIES = ['urgent', 'important', 'info'];

    private const EVENT_TYPES = ['meeting', 'event', 'training'];

    public function announcements(): View
    {
        return view('admin.content.announcements.index', [
            'announcements' => Announcement::with(['department', 'creator'])->latest('created_at')->paginate(20),
        ]);
    }

    public function createAnnouncement(): View
    {
        return view('admin.content.announcements.form', [
            'announcement' => new Announcement(['priority' => 'info', 'is_active' => true]),
        ]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request);
        $active = $request->boolean('is_active');
        $announcement = Announcement::create([
            ...$validated,
            'department_id' => null,
            'created_by' => $request->user()->id,
            'is_active' => $active,
            'published_at' => $active ? now() : null,
        ]);

        if ($active) {
            $this->notifyAudience($announcement);
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce enregistrée.');
    }

    public function editAnnouncement(Announcement $announcement): View
    {
        return view('admin.content.announcements.form', [
            'announcement' => $announcement,
        ]);
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request);
        $wasActive = $announcement->is_active;
        $active = $request->boolean('is_active');
        $announcement->update([
            ...$validated,
            'department_id' => null,
            'is_active' => $active,
            'published_at' => $active ? ($announcement->published_at ?? now()) : null,
        ]);

        if ($active && ! $wasActive) {
            $this->notifyAudience($announcement);
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce mise à jour.');
    }

    public function destroyAnnouncement(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => false, 'published_at' => null]);

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce archivée.');
    }

    public function deleteAnnouncement(Announcement $announcement): RedirectResponse
    {
        // Retire aussi les notifications qui pointeraient vers l'annonce supprimée.
        DatabaseNotification::where('type', AnnouncementPublished::class)
            ->whereLike('data', '%"announcement_id":'.$announcement->id.',%')
            ->delete();
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce supprimée.');
    }

    public function calendar(): View
    {
        return view('admin.content.calendar.index', [
            'events' => CalendarEvent::with(['department', 'user', 'creator'])
                ->orderByDesc('event_date')
                ->paginate(20),
            'types' => $this->eventTypes(),
        ]);
    }

    public function createEvent(): View
    {
        return $this->eventForm(new CalendarEvent(['type' => 'event', 'all_day' => true]));
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        CalendarEvent::create([
            ...$this->validateEvent($request),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.calendar.index')->with('success', 'Événement ajouté au calendrier.');
    }

    public function editEvent(CalendarEvent $event): View
    {
        return $this->eventForm($event);
    }

    public function updateEvent(Request $request, CalendarEvent $event): RedirectResponse
    {
        $event->update($this->validateEvent($request));

        return redirect()->route('admin.calendar.index')->with('success', 'Événement mis à jour.');
    }

    public function destroyEvent(CalendarEvent $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.calendar.index')->with('success', 'Événement supprimé du calendrier.');
    }

    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(self::ANNOUNCEMENT_PRIORITIES)],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function validateEvent(Request $request): array
    {
        $allDay = $request->boolean('all_day');
        $request->merge(['all_day' => $allDay ? '1' : '0']);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::in(self::EVENT_TYPES)],
            'event_date' => ['required', 'date'],
            'event_time' => ['required_unless:all_day,1', 'nullable', 'date_format:H:i'],
            'all_day' => ['nullable', 'boolean'],
        ]);

        $validated['all_day'] = $allDay;
        $validated['event_time'] = $allDay ? null : ($validated['event_time'] ?? null);
        $validated['department_id'] = null;
        $validated['user_id'] = null;

        return $validated;
    }

    private function eventForm(CalendarEvent $event): View
    {
        return view('admin.content.calendar.form', [
            'event' => $event,
            'types' => $this->eventTypes(),
        ]);
    }

    /** @return array<string, string> */
    private function eventTypes(): array
    {
        return collect(self::EVENT_TYPES)
            ->mapWithKeys(fn (string $type) => [$type => config("intranet.calendar.event_types.{$type}.name", ucfirst($type))])
            ->all();
    }

    private function notifyAudience(Announcement $announcement): void
    {
        $recipients = User::query()
            ->where('actif', true)
            ->get()
            ->reject(fn (User $user) => $user->is($announcement->creator));

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AnnouncementPublished($announcement));
        }
    }
}
