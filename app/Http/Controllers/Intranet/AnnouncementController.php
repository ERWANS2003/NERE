<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            ->visibleTo($request->user())
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

        return view('intranet.announcements.create', [
            'audienceDepartments' => $this->audienceDepartments($request->user()),
        ]);
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

        if ($request->hasFile('attachment')) {
            $announcement->replaceAttachment($request->file('attachment'));
        }

        $this->notifyAudience($announcement);

        return redirect()
            ->route('intranet.announcements.show', $announcement)
            ->with('success', 'Annonce publiée.');
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return view('intranet.announcements.edit', [
            'announcement' => $announcement,
            'audienceDepartments' => $this->audienceDepartments($request->user(), $announcement),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $announcement->update($this->validatedData($request, $announcement));

        if ($request->boolean('remove_attachment')) {
            $announcement->removeAttachment();
        }
        if ($request->hasFile('attachment')) {
            $announcement->replaceAttachment($request->file('attachment'));
        }

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
     * @return array{title: string, body: string, priority: string, department_id: ?int}
     */
    private function validatedData(Request $request, ?Announcement $announcement = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'in:'.implode(',', self::PRIORITIES)],
            'department_id' => ['present', 'nullable', 'integer', Rule::in($this->audienceDepartments($request->user(), $announcement)->modelKeys())],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,txt,doc,docx,xls,xlsx,ppt,pptx'],
            'remove_attachment' => ['nullable', 'boolean'],
        ]);

        // Le fichier est enregistré à part.
        unset($validated['attachment'], $validated['remove_attachment']);

        return $validated;
    }

    /**
     * Départements que l'auteur peut viser, en plus de toute l'entreprise. Le
     * public actuel reste proposé même si l'auteur ne dirige plus ce département.
     *
     * @return Collection<int, Department>
     */
    private function audienceDepartments(User $user, ?Announcement $announcement = null): Collection
    {
        return $user->directedDepartments()
            ->when($announcement?->department, fn (Collection $departments) => $departments->push($announcement->department))
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * L'auteur est exclu de la notification : il vient de publier l'annonce.
     */
    private function notifyAudience(Announcement $announcement): void
    {
        $destinataires = $announcement->audience();

        if ($destinataires->isNotEmpty()) {
            Notification::send($destinataires, new AnnouncementPublished($announcement));
        }
    }
}
