<?php

namespace App\Http\Controllers\Intranet;

use App\Enums\DepartmentRole;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketNotification;
use App\Services\TicketSlaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $department = $this->itDepartment();
        $user = $request->user();
        $isAgent = $user->canHandleIn($department->id);

        $query = Ticket::query()
            ->with(['requester', 'assignee'])
            ->where('department_id', $department->id);

        if (! $isAgent) {
            $query->where('requester_id', $user->id);
        }

        if ($request->filled('status') && TicketStatus::tryFrom($request->string('status')->toString())) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(fn ($tickets) => $tickets
                ->where('number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%"));
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        return view('intranet.tickets.index', [
            'tickets' => $tickets,
            'isAgent' => $isAgent,
            'statuses' => TicketStatus::options(),
        ]);
    }

    public function create(Request $request): View
    {
        $category = $request->query('category');
        $selectedCategory = is_string($category) && TicketCategory::tryFrom($category)
            ? $category
            : null;
        $suggestedArticles = KnowledgeArticle::query()
            ->published()
            ->withCount([
                'feedback as helpful_count' => fn ($feedback) => $feedback->where('knowledge_article_feedback.helpful', true),
            ])
            ->when(
                $selectedCategory,
                fn ($articles) => $articles->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$selectedCategory])
            )
            ->orderByDesc('helpful_count')
            ->orderBy('title')
            ->limit(4)
            ->get();

        return view('intranet.tickets.create', [
            'categories' => TicketCategory::options(),
            'priorities' => TicketPriority::options(),
            'selectedCategory' => $selectedCategory,
            'suggestedArticles' => $suggestedArticles,
        ]);
    }

    public function store(Request $request, TicketSlaService $ticketSlaService): RedirectResponse
    {
        $department = $this->itDepartment();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,txt,doc,docx,xls,xlsx'],
        ]);

        $ticket = DB::transaction(function () use ($request, $department, $validated, $ticketSlaService) {
            $ticket = Ticket::create([
                'number' => 'pending',
                'department_id' => $department->id,
                'requester_id' => $request->user()->id,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => TicketStatus::New,
            ]);

            $ticket->update(['number' => 'IT-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            $ticketSlaService->setDeadlines($ticket);

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event' => 'created',
            ]);

            return $ticket;
        });

        $this->saveAttachments($request, $ticket);

        $recipients = $this->itAgents($department)->reject(
            fn (User $agent) => $agent->is($request->user())
        );
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketNotification(
                $ticket,
                'Nouveau ticket à traiter',
                'Un nouveau ticket a été soumis au support informatique.'
            ));
        }

        return redirect()
            ->route('intranet.tickets.show', $ticket)
            ->with('success', 'Votre ticket a été créé.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorizeView($request->user(), $ticket);

        $ticket->load([
            'requester',
            'assignee',
            'comments.user',
            'comments.attachments',
            'attachments' => fn ($query) => $query->whereNull('ticket_comment_id'),
            'activities.user',
        ]);

        $department = $this->itDepartment();
        $user = $request->user();
        $canAssign = $user->canValidateIn($department->id);

        return view('intranet.tickets.show', [
            'ticket' => $ticket,
            'isAgent' => $user->canHandleIn($department->id),
            'canAssign' => $canAssign,
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
            'agents' => $this->itTechnicians($department)->sortBy('name')->values(),
        ]);
    }

    public function update(Request $request, Ticket $ticket, TicketSlaService $ticketSlaService): RedirectResponse
    {
        $this->authorizeAgent($request->user(), $ticket);
        $department = $this->itDepartment();
        $canAssign = $request->user()->canValidateIn($department->id);

        $rules = [
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ];

        if ($canAssign) {
            $rules['assignee_id'] = [
                'present',
                'nullable',
                'integer',
                Rule::in($this->itTechnicians($department)->modelKeys()),
            ];
        }

        $validated = $request->validate($rules);

        $before = [
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'assignee_id' => $ticket->assignee_id,
        ];
        $status = TicketStatus::from($validated['status']);
        $priorityChanged = $before['priority'] !== $validated['priority'];
        $reopened = in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)
            && ! in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true);

        $ticket->update([
            'status' => $status,
            'priority' => $validated['priority'],
            'assignee_id' => $canAssign ? $validated['assignee_id'] : $ticket->assignee_id,
            'resolved_at' => $status === TicketStatus::Resolved ? ($ticket->resolved_at ?? now()) : null,
            'closed_at' => $status === TicketStatus::Closed ? ($ticket->closed_at ?? now()) : null,
        ]);

        if ($priorityChanged) {
            $ticketSlaService->setDeadlines($ticket, resetAlerts: true);
        } elseif ($reopened) {
            $ticketSlaService->restartResolutionDeadline($ticket);
        }

        $after = [
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'assignee_id' => $ticket->assignee_id,
        ];
        $changes = array_filter(
            ['status' => ['from' => $before['status'], 'to' => $after['status']],
                'priority' => ['from' => $before['priority'], 'to' => $after['priority']],
                'assignee_id' => ['from' => $before['assignee_id'], 'to' => $after['assignee_id']]],
            fn (array $change, string $key) => $change['from'] !== $change['to'],
            ARRAY_FILTER_USE_BOTH
        );

        if ($changes !== []) {
            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event' => 'updated',
                'changes' => $changes,
            ]);

            $requester = $ticket->requester;
            if ($requester && $requester->actif && ! $requester->is($request->user())) {
                $requester->notify(new TicketNotification(
                    $ticket,
                    'Mise à jour de votre ticket',
                    'Votre ticket a été mis à jour par l’équipe informatique.'
                ));
            }

            if ($ticket->assignee && $ticket->assignee->actif
                && $ticket->assignee->id !== $request->user()->id
                && $ticket->assignee_id !== $before['assignee_id']) {
                $ticket->assignee->notify(new TicketNotification(
                    $ticket,
                    'Ticket qui vous est attribué',
                    'Un ticket du support informatique vous a été attribué.'
                ));
            }
        }

        return redirect()
            ->route('intranet.tickets.show', $ticket)
            ->with('success', 'Le ticket a été mis à jour.');
    }

    public function take(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeAgent($request->user(), $ticket);
        abort_unless($request->user()->canValidateIn($ticket->department_id), 403);
        abort_unless($ticket->status !== TicketStatus::Closed, 422, 'Un ticket fermé ne peut plus être pris en charge.');

        if ($ticket->assignee_id === $request->user()->id && $ticket->status === TicketStatus::InProgress) {
            return redirect()
                ->route('intranet.tickets.show', $ticket)
                ->with('success', 'Vous avez déjà pris ce ticket en charge.');
        }

        $before = [
            'status' => $ticket->status->value,
            'assignee_id' => $ticket->assignee_id,
        ];

        $ticket->update([
            'assignee_id' => $request->user()->id,
            'status' => TicketStatus::InProgress,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event' => 'taken',
            'changes' => [
                'status' => ['from' => $before['status'], 'to' => TicketStatus::InProgress->value],
                'assignee_id' => ['from' => $before['assignee_id'], 'to' => $request->user()->id],
            ],
        ]);

        $requester = $ticket->requester;
        if ($requester && $requester->actif && ! $requester->is($request->user())) {
            $requester->notify(new TicketNotification(
                $ticket,
                'Votre ticket est pris en charge',
                'Le responsable IT a pris en charge votre ticket.'
            ));
        }

        return redirect()
            ->route('intranet.tickets.show', $ticket)
            ->with('success', 'Vous avez pris le ticket en charge.');
    }

    public function comment(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeView($user, $ticket);
        abort_unless($ticket->status !== TicketStatus::Closed, 422, 'Un ticket fermé ne peut plus recevoir de commentaire.');
        $department = $this->itDepartment();
        $isAgent = $user->canHandleIn($department->id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,txt,doc,docx,xls,xlsx'],
        ]);

        $internal = $isAgent && $request->boolean('is_internal');
        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
            'is_internal' => $internal,
        ]);
        $this->saveAttachments($request, $ticket, $comment);

        if ($isAgent && ! $internal && ! $ticket->first_responded_at) {
            $ticket->update(['first_responded_at' => now()]);
        }

        $recipients = collect();
        if ($ticket->requester && ! $ticket->requester->is($user) && ! $internal && $ticket->requester->actif) {
            $recipients->push($ticket->requester);
        }

        if ($isAgent) {
            if ($ticket->assignee && ! $ticket->assignee->is($user) && $ticket->assignee->actif) {
                $recipients->push($ticket->assignee);
            }
        } else {
            $recipients = $recipients->merge(
                $ticket->assignee ? collect([$ticket->assignee]) : $this->itAgents($department)
            );
        }

        $recipients = $recipients
            ->filter(fn (User $recipient) => $recipient->actif && ! $recipient->is($user))
            ->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketNotification(
                $ticket,
                $internal ? 'Nouvelle note interne' : 'Nouveau commentaire sur un ticket',
                $internal ? 'Une note interne a été ajoutée au ticket.' : 'Un nouveau commentaire a été ajouté au ticket.'
            ));
        }

        return redirect()
            ->route('intranet.tickets.show', $ticket)
            ->with('success', 'Votre commentaire a été ajouté.');
    }

    public function download(Request $request, TicketAttachment $attachment)
    {
        $this->authorizeView($request->user(), $attachment->ticket);

        if ($attachment->comment?->is_internal) {
            $this->authorizeAgent($request->user(), $attachment->ticket);
        }

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function itDepartment(): Department
    {
        return Department::query()->where('code', 'IT')->where('is_active', true)->firstOrFail();
    }

    /** @return Collection<int, User> */
    private function itAgents(Department $department): Collection
    {
        return User::query()
            ->where('actif', true)
            ->whereHas('departments', fn ($query) => $query
                ->where('departments.id', $department->id)
                ->whereIn('department_user.role', [DepartmentRole::Technician->value, DepartmentRole::Director->value]))
            ->get();
    }

    /** @return Collection<int, User> */
    private function itTechnicians(Department $department): Collection
    {
        return User::query()
            ->where('actif', true)
            ->whereHas('departments', fn ($query) => $query
                ->where('departments.id', $department->id)
                ->where('department_user.role', DepartmentRole::Technician->value))
            ->get();
    }

    private function authorizeView(User $user, Ticket $ticket): void
    {
        abort_unless(
            $user->is_super_admin
                || $ticket->requester_id === $user->id
                || ($ticket->department_id === $this->itDepartment()->id && $user->canHandleIn($ticket->department_id)),
            403
        );
    }

    private function authorizeAgent(User $user, Ticket $ticket): void
    {
        abort_unless(
            $user->is_super_admin
                || ($ticket->department_id === $this->itDepartment()->id && $user->canHandleIn($ticket->department_id)),
            403
        );
    }

    private function saveAttachments(Request $request, Ticket $ticket, ?TicketComment $comment = null): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('tickets/'.$ticket->id, 'local');
            $ticket->attachments()->create([
                'ticket_comment_id' => $comment?->id,
                'user_id' => $request->user()->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }
}
