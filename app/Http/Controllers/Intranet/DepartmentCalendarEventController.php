<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentCalendarEventController extends Controller
{
    private const EVENT_TYPES = ['meeting', 'event', 'training'];

    public function index(Request $request): View
    {
        $departments = $this->managedDepartments($request->user());
        abort_if($departments->isEmpty(), 403);

        $events = CalendarEvent::with('department')
            ->whereIn('department_id', $departments->modelKeys())
            ->orderByDesc('event_date')
            ->orderByDesc('event_time')
            ->paginate(20);

        return view('intranet.calendar.department-events.index', compact('events', 'departments'));
    }

    public function create(Request $request): View
    {
        return $this->eventForm($request, new CalendarEvent(['type' => 'event', 'all_day' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $event = CalendarEvent::create([
            ...$this->validatedEvent($request),
            'user_id' => null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('intranet.calendar.department-events.index')
            ->with('success', 'Événement ajouté à l’agenda du département.');
    }

    public function edit(Request $request, CalendarEvent $event): View
    {
        $this->authorizeEvent($request->user(), $event);

        return $this->eventForm($request, $event);
    }

    public function update(Request $request, CalendarEvent $event): RedirectResponse
    {
        $this->authorizeEvent($request->user(), $event);
        $event->update($this->validatedEvent($request));

        return redirect()
            ->route('intranet.calendar.department-events.index')
            ->with('success', 'Événement mis à jour.');
    }

    public function destroy(Request $request, CalendarEvent $event): RedirectResponse
    {
        $this->authorizeEvent($request->user(), $event);
        $event->delete();

        return redirect()
            ->route('intranet.calendar.department-events.index')
            ->with('success', 'Événement supprimé de l’agenda.');
    }

    private function eventForm(Request $request, CalendarEvent $event): View
    {
        $departments = $this->managedDepartments($request->user());
        abort_if($departments->isEmpty(), 403);

        $types = collect(self::EVENT_TYPES)
            ->mapWithKeys(fn (string $type) => [
                $type => config("intranet.calendar.event_types.{$type}.name", ucfirst($type)),
            ])
            ->all();

        return view('intranet.calendar.department-events.form', compact('event', 'departments', 'types'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedEvent(Request $request): array
    {
        $departments = $this->managedDepartments($request->user());
        abort_if($departments->isEmpty(), 403);

        $allDay = $request->boolean('all_day');
        $request->merge(['all_day' => $allDay ? '1' : '0']);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::in(self::EVENT_TYPES)],
            'event_date' => ['required', 'date'],
            'event_time' => ['required_unless:all_day,1', 'nullable', 'date_format:H:i'],
            'department_id' => ['required', 'integer', Rule::in($departments->modelKeys())],
            'all_day' => ['nullable', 'boolean'],
        ]);

        $validated['all_day'] = $allDay;
        $validated['event_time'] = $allDay ? null : ($validated['event_time'] ?? null);

        return $validated;
    }

    private function authorizeEvent(User $user, CalendarEvent $event): void
    {
        abort_unless(
            $event->department_id !== null && $user->canManageDepartment($event->department_id),
            403
        );
    }

    /**
     * @return Collection<int, Department>
     */
    private function managedDepartments(User $user): Collection
    {
        return $user->is_super_admin
            ? collect()
            : $user->directedDepartments()->sortBy('name')->values();
    }
}
