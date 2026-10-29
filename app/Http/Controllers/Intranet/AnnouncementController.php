<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function attachment(Request $request, Announcement $announcement): StreamedResponse
    {
        $this->authorize('view', $announcement);

        abort_unless(
            $announcement->attachment_path && Storage::disk('local')->exists($announcement->attachment_path),
            404,
        );

        return Storage::disk('local')->download($announcement->attachment_path, $announcement->attachment_name);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Announcement::class);

        return view('intranet.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $validated = $this->validatedData($request);

        $announcement = Announcement::create([
            ...$validated,
            'department_id' => null,
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

        return view('intranet.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $announcement->update([
            ...$this->validatedData($request),
            'department_id' => null,
        ]);

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
     * @return array{title: string, body: string, priority: string}
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'in:'.implode(',', self::PRIORITIES)],
        ]);
    }

    /**
     * Les annonces sont des communications globales. L'auteur est exclu de la
     * notification : il vient de publier l'annonce.
     */
    private function notifyAudience(Announcement $announcement): void
    {
        $destinataires = User::where('actif', true)->get();

        $destinataires = $destinataires->reject(
            fn (User $u) => $u->id === $announcement->created_by
        );

        if ($destinataires->isNotEmpty()) {
            Notification::send($destinataires, new AnnouncementPublished($announcement));
        }
    }
}
