<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Attachment;
use App\Models\Intranet\Comment;
use App\Models\Intranet\Form;
use App\Models\Intranet\Submission;
use App\Models\Intranet\SubmissionEvent;
use App\Models\Intranet\SubmissionValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    /* ── Mes demandes ─────────────────────────────────────────── */

    public function mine()
    {
        $submissions = Submission::where('requester_id', Auth::id())
            ->with(['form.service.department'])
            ->latest()
            ->paginate(20);

        return view('intranet.submissions.mine', compact('submissions'));
    }

    /* ── Soumettre ────────────────────────────────────────────── */

    public function store(Request $request, Form $form)
    {
        abort_unless($form->isPublished(), 404);
        $this->authorize('submitTo', $form);

        $fields   = $form->fields()->orderBy('position')->get();
        $rules    = [];
        $fieldMap = [];

        foreach ($fields as $field) {
            $key          = 'field_' . $field->id;
            $rules[$key]  = $field->laravelRules();
            $fieldMap[$key] = $field;
        }

        $validated = $request->validate($rules);
        $user      = Auth::user();

        DB::transaction(function () use ($validated, $fieldMap, $form, $user, $request) {
            $prefix = strtoupper($form->service->department->code);
            $year   = now()->year;
            $seq    = Submission::withTrashed()->whereYear('created_at', $year)->count() + 1;
            $ref    = sprintf('%s-%d-%05d', $prefix, $year, $seq);

            $firstStep = $form->workflowSteps()->orderBy('order')->first();
            $status    = $firstStep?->needs_approval
                ? Submission::STATUS_VALIDATING
                : Submission::STATUS_SUBMITTED;

            $submission = Submission::create([
                'reference'       => $ref,
                'form_id'         => $form->id,
                'requester_id'    => $user->id,
                'current_step_id' => $firstStep?->id,
                'status'          => $status,
                'priority'        => 'normale',
                'due_at'          => $firstStep?->sla_hours
                    ? now()->addHours($firstStep->sla_hours)
                    : null,
            ]);

            foreach ($fieldMap as $key => $field) {
                if ($field->type === 'file') continue;
                $value = $validated[$key] ?? null;
                if (is_array($value)) $value = json_encode($value);
                SubmissionValue::create([
                    'submission_id' => $submission->id,
                    'form_field_id' => $field->id,
                    'value'         => $value,
                ]);
            }

            foreach ($fieldMap as $key => $field) {
                if ($field->type !== 'file' || ! $request->hasFile($key)) continue;
                $file = $request->file($key);
                $path = $file->store('intranet/submissions/' . $submission->id, 'private');
                Attachment::create([
                    'submission_id' => $submission->id,
                    'uploaded_by'   => $user->id,
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime'          => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }

            SubmissionEvent::create([
                'submission_id' => $submission->id,
                'actor_id'      => $user->id,
                'action'        => 'created',
                'payload'       => ['status' => $status, 'reference' => $submission->reference],
            ]);

            session()->flash('_intranet_sub_id', $submission->id);
        });

        $subId = session('_intranet_sub_id');

        return redirect()->route('intranet.submissions.show', $subId)
            ->with('success', 'Votre demande a été soumise avec succès.');
    }

    /* ── Détail ───────────────────────────────────────────────── */

    public function show(Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load([
            'form.service.department',
            'requester', 'assignee', 'currentStep',
            'values.field', 'events.actor',
            'comments.author', 'attachments.uploader',
        ]);

        $user  = Auth::user();
        $dept  = $submission->form->service->department;
        $pivot = $user->intranetPivot($dept);

        $technicians = $dept->users()
            ->wherePivot('role', 'technician')
            ->get();

        return view('intranet.submissions.show', compact(
            'submission', 'dept', 'pivot', 'technicians'
        ));
    }

    /* ── Changer statut ───────────────────────────────────────── */

    public function updateStatus(Request $request, Submission $submission)
    {
        $this->authorize('act', $submission);
        abort_if($submission->isTerminal(), 422, 'Cette demande est déjà terminée.');

        $newStatus = $request->validate([
            'status' => 'required|in:en_cours,en_attente,resolue,cloturee,rejetee,annulee',
        ])['status'];

        // Annulation : vérification spécifique
        if ($newStatus === Submission::STATUS_CANCELLED) {
            $this->authorize('cancel', $submission);
        }

        // Validation/rejet : directeur seulement
        if (in_array($newStatus, [Submission::STATUS_REJECTED, 'cloturee'])) {
            $this->authorize('validate', $submission);
        }

        $old = $submission->status;
        $submission->update([
            'status'    => $newStatus,
            'closed_at' => in_array($newStatus, Submission::TERMINAL_STATUSES) ? now() : null,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => Auth::id(),
            'action'        => 'status_changed',
            'payload'       => ['from' => $old, 'to' => $newStatus],
        ]);

        return back()->with('success', 'Statut mis à jour.');
    }

    /* ── Assigner ─────────────────────────────────────────────── */

    public function assign(Request $request, Submission $submission)
    {
        $this->authorize('assign', $submission);

        $data = $request->validate(['assignee_id' => 'required|exists:users,id']);

        $old = $submission->assignee_id;
        $submission->update([
            'assignee_id' => $data['assignee_id'],
            'status'      => Submission::STATUS_ASSIGNED,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => Auth::id(),
            'action'        => 'assigned',
            'payload'       => ['from' => $old, 'to' => $data['assignee_id']],
        ]);

        return back()->with('success', 'Demande assignée.');
    }

    /* ── Commenter ────────────────────────────────────────────── */

    public function comment(Request $request, Submission $submission)
    {
        $this->authorize('comment', $submission);

        $data       = $request->validate(['body' => 'required|string|max:2000', 'is_internal' => 'boolean']);
        $isInternal = (bool) ($data['is_internal'] ?? false);

        if ($isInternal) {
            $this->authorize('commentInternal', $submission);
        }

        Comment::create([
            'submission_id' => $submission->id,
            'author_id'     => Auth::id(),
            'body'          => $data['body'],
            'is_internal'   => $isInternal,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => Auth::id(),
            'action'        => 'commented',
            'payload'       => ['is_internal' => $isInternal],
        ]);

        return back()->with('success', 'Commentaire ajouté.');
    }

    /* ── Escalader ────────────────────────────────────────────── */

    public function escalate(Request $request, Submission $submission)
    {
        $this->authorize('escalate', $submission);
        abort_if($submission->isTerminal(), 422, 'Demande déjà terminée.');

        $pivot        = Auth::user()->intranetPivot($submission->form->service->department);
        $currentLevel = $submission->currentStep?->tech_level ?? ($pivot?->tech_level ?? 1);

        $nextStep = $submission->form->workflowSteps()
            ->where('tech_level', '>', $currentLevel)
            ->orderBy('tech_level')
            ->first();

        abort_unless($nextStep, 422, 'Aucun niveau supérieur disponible.');

        $submission->update([
            'current_step_id' => $nextStep->id,
            'assignee_id'     => null,
            'status'          => Submission::STATUS_ESCALATED,
            'due_at'          => $nextStep->sla_hours
                ? now()->addHours($nextStep->sla_hours)
                : $submission->due_at,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => Auth::id(),
            'action'        => 'escalated',
            'payload'       => [
                'from_level' => $currentLevel,
                'to_level'   => $nextStep->tech_level,
                'step'       => $nextStep->name,
            ],
        ]);

        return back()->with('success', 'Demande escaladée vers le niveau ' . $nextStep->tech_level . '.');
    }
}
