<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Notifications\AnnouncementPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    private const PRIORITIES = ['urgent', 'important', 'info'];

    /**
     * Liste avec filtre priorité et recherche texte (titre + contenu).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);

        $query = Announcement::query()
            ->with(['department', 'creator'])
            ->where('is_active', true);

        if ($request->filled('priority') && in_array($request->priority, self::PRIORITIES, true)) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $terme = trim($request->string('search'));
            $query->where(function ($q) use ($terme) {
                $q->where('title', 'ILIKE', "%{$terme}%")
                  ->orWhere('body', 'ILIKE', "%{$terme}%");
            });
        }

        $announcements = $query->latest('published_at')->paginate(10)->withQueryString();

        return view('intranet.announcements.index', compact('announcements'));
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $this->authorize('view', $announcement);

        $announcement->load(['department', 'creator']);

        return view('intranet.announcements.show', compact('announcement'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Announcement::class);

        $departments = $this->publishableDepartments($request->user());

        return view('intranet.announcements.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $validated = $this->validatedData($request);

        $announcement = Announcement::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->notifyAudience($announcement);

        return redirect()
            ->route('intranet.announcements.show', $announcement)
            ->with('success', 'Annonce publiée.');
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        $departments = $this->publishableDepartments($request->user());

        return view('intranet.announcements.edit', compact('announcement', 'departments'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $announcement->update($this->validatedData($request));

        return redirect()
            ->route('intranet.announcements.show', $announcement)
            ->with('success', 'Annonce mise à jour.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        // Désactivation plutôt que suppression : une annonce déjà notifiée/lue
        // garde son historique (table notifications, liens déjà partagés).
        $announcement->update(['is_active' => false]);

        return redirect()
            ->route('intranet.announcements.index')
            ->with('success', 'Annonce retirée.');
    }

    /**
     * Marque la notification d'annonce correspondante comme lue pour
     * l'utilisateur courant (appelé au clic sur le badge de l'en-tête).
     */
    public function markRead(Request $request, Announcement $announcement): RedirectResponse
    {
        // Filtre en PHP plutôt qu'en SQL : la colonne "data" de la table
        // "notifications" standard de Laravel est un `text`, pas un `jsonb` -
        // l'opérateur PostgreSQL "->>'" n'y est pas applicable.
        $request->user()->unreadNotifications()
            ->get()
            ->filter(fn ($n) => (int) ($n->data['announcement_id'] ?? null) === $announcement->id)
            ->each(fn ($n) => $n->markAsRead());

        return redirect()->route('intranet.announcements.show', $announcement);
    }

    /**
     * @return array{title: string, body: string, priority: string, department_id: int|null}
     */
    private function validatedData(Request $request): array
    {
        $user = $request->user();

        // Le middleware global ConvertEmptyStringsToNull transforme déjà "" en
        // null sur une vraie requête HTTP ; cette ligne protège le même code
        // appelé autrement (tests, requêtes construites à la main).
        if ($request->input('department_id') === '') {
            $request->merge(['department_id' => null]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'in:' . implode(',', self::PRIORITIES)],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        // Un directeur ne peut publier que pour un département qu'il dirige
        // (jamais "toute l'entreprise" : null est réservé à l'admin général).
        if (! $user->is_super_admin) {
            $autorises = $this->publishableDepartments($user)->pluck('id');

            abort_unless(
                $validated['department_id'] !== null && $autorises->contains((int) $validated['department_id']),
                403,
                "Vous ne pouvez publier que pour un département que vous dirigez."
            );
        }

        return $validated;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Department>
     */
    private function publishableDepartments($user): \Illuminate\Support\Collection
    {
        return $user->is_super_admin
            ? Department::active()->orderBy('name')->get()
            : $user->directedDepartments()->sortBy('name')->values();
    }

    /**
     * Notifie le public de l'annonce : tout le monde si department_id est
     * null, sinon seulement les membres de ce département. L'auteur est
     * toujours exclu : il vient de la publier, pas besoin de le lui notifier.
     */
    private function notifyAudience(Announcement $announcement): void
    {
        $destinataires = $announcement->department_id === null
            ? \App\Models\User::where('actif', true)->get()
            : \App\Models\User::where('actif', true)
                ->whereHas('departments', fn ($q) => $q->where('departments.id', $announcement->department_id))
                ->get();

        $destinataires = $destinataires->reject(
            fn (\App\Models\User $u) => $u->id === $announcement->created_by
        );

        if ($destinataires->isNotEmpty()) {
            Notification::send($destinataires, new AnnouncementPublished($announcement));
        }
    }
}
