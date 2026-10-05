<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Attachment;
use App\Models\Intranet\Comment;
use App\Models\Intranet\Form;
use App\Models\Intranet\Submission;
use App\Models\Intranet\SubmissionEvent;
use App\Models\Intranet\SubmissionValue;
use App\Models\Intranet\WorkflowStep;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    /* ─────────────────────────────────────────────────────────
     | Mes demandes (toutes les soumissions du user connecté)
     ───────────────────────────────────────────────────────── */
    public function mine()
    {
        $submissions = Submission::where('requester_id', Auth::id())
            ->with(['form.service.department'])
            ->latest()
            ->paginate(20);

        return view('intranet.submissions.mine', compact('submissions'));
    }

    /* ─────────────────────────────────────────────────────────
     | Soumettre un formulaire
     ───────────────────────────────────────────────────────── */
    public function store(Request $request, Form $form)
    {
        abort_unless($form->isPublished(), 404);

        $user = Auth::user();
        $dept = $form->service->department;

        if (! $user->is_super_admin) {
            abort_unless($dept->users()->where('users.id', $user->id)->exists(), 403);
        }

        // Construire dynamiquement les règles de validation
        $fields       = $form->fields()->orderBy('position')->get();
        $rules        = [];
        $fieldMap     = [];

        foreach ($fields as $field) {
            $key            = 'field_' . $field->id;
            $rules[$key]    = $field->laravelRules();
            $fieldMap[$key] = $field;
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $fieldMap, $form, $user, $request) {

            // Référence unique : CODE-ANNÉE-SÉQUENCE
            $prefix = strtoupper($form->service->department->code);
            $year   = now()->year;
            $seq    = Submission::whereYear('created_at', $year)->count() + 1;
            $ref    = sprintf('%s-%d-%05d', $prefix, $year, $seq);

            // Première étape du workflow
            $firstStep = $form->workflowSteps()->orderBy('order')->first();

            $status = Submission::STATUS_SUBMITTED;
            if ($firstStep?->needs_approval) {
                $status = Submission::STATUS_VALIDATING;
            }

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

            // Stocker les valeurs des champs
            foreach ($fieldMap as $key => $field) {
                $value = $validated[$key] ?? null;
                if ($field->type === 'file') {
                    continue; // traité séparément
                }
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                SubmissionValue::create([
                    'submission_id' => $submission->id,
                    'form_field_id' => $field->id,
                    'value'         => $value,
                ]);
            }

            // Pièces jointes (fichiers)
            foreach ($fieldMap as $key => $field) {
                if ($field->type !== 'file') continue;
                if (! $request->hasFile($key)) continue;

                $file  = $request->file($key);
                $path  = $file->store(
                    'intranet/submissions/' . $submission->id,
                    'private'
                );

                Attachment::create([
                    'submission_id' => $submission->id,
                    'uploaded_by'   => $user->id,
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'mime'          => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }

            // Événement de création
            SubmissionEvent::create([
                'submission_id' => $submission->id,
                'actor_id'      => $user->id,
                'action'        => 'created',
                'payload'       => ['status' => $status, 'reference' => $submission->reference],
            ]);

            session()->flash('submission_ref', $submission->reference);
            session()->flash('submission_id',  $submission->id);
        });

        $subId = session('submission_id');

        return redirect()->route('intranet.submissions.show', $subId)
            ->with('success', 'Demande ' . session('submission_ref') . ' créée avec succès.');
    }

    /* ─────────────────────────────────────────────────────────
     | Détail d'une demande
     ───────────────────────────────────────────────────────── */
    public function show(Submission $submission)
    {
        $user = Auth::user();
        $this->authorizeView($submission, $user);

        $submission->load([
            'form.service.department',
            'requester',
            'assignee',
            'currentStep',
            'values.field',
            'events.actor',
            'comments.author',
            'attachments.uploader',
        ]);

        $dept    = $submission->form->service->department;
        $pivot   = $user->is_super_admin
            ? (object) ['role' => 'director', 'tech_level' => null]
            : $dept->users()->where('users.id', $user->id)->first()?->pivot;

        // Techniciens disponibles pour assignation
        $technicians = $dept->users()
            ->wherePivot('role', 'technician')
            ->get();

        return view('intranet.submissions.show', compact(
            'submission', 'dept', 'pivot', 'technicians'
        ));
    }

    /* ─────────────────────────────────────────────────────────
     | Changer le statut
     ───────────────────────────────────────────────────────── */
    public function updateStatus(Request $request, Submission $submission)
    {
        $user = Auth::user();
        $this->authorizeAction($submission, $user);

        $request->validate([
            'status' => 'required|string|in:en_cours,en_attente,resolue,cloturee,rejetee,annulee',
        ]);

        $oldStatus = $submission->status;
        $newStatus = $request->status;

        // Seul le demandeur peut annuler une demande non prise en charge
        if ($newStatus === Submission::STATUS_CANCELLED) {
            $canCancel = $user->is_super_admin
                || ($submission->requester_id === $user->id
                    && in_array($oldStatus, [Submission::STATUS_DRAFT, Submission::STATUS_SUBMITTED]));
            abort_unless($canCancel, 403);
        }

        $submission->update([
            'status'     => $newStatus,
            'closed_at'  => in_array($newStatus, Submission::TERMINAL_STATUSES) ? now() : null,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => $user->id,
            'action'        => 'status_changed',
            'payload'       => ['from' => $oldStatus, 'to' => $newStatus],
        ]);

        return back()->with('success', 'Statut mis à jour.');
    }

    /* ─────────────────────────────────────────────────────────
     | Assigner à un technicien
     ───────────────────────────────────────────────────────── */
    public function assign(Request $request, Submission $submission)
    {
        $user = Auth::user();
        $dept = $submission->form->service->department;

        // Seuls directeur et super admin peuvent assigner
        $pivot = $user->is_super_admin
            ? (object) ['role' => 'director']
            : $dept->users()->where('users.id', $user->id)->first()?->pivot;

        abort_unless($pivot && $pivot->role === 'director', 403);

        $request->validate(['assignee_id' => 'required|exists:users,id']);

        $old = $submission->assignee_id;
        $submission->update([
            'assignee_id' => $request->assignee_id,
            'status'      => Submission::STATUS_ASSIGNED,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => $user->id,
            'action'        => 'assigned',
            'payload'       => ['from' => $old, 'to' => $request->assignee_id],
        ]);

        return back()->with('success', 'Demande assignée.');
    }

    /* ─────────────────────────────────────────────────────────
     | Commenter
     ───────────────────────────────────────────────────────── */
    public function comment(Request $request, Submission $submission)
    {
        $user = Auth::user();
        $this->authorizeView($submission, $user);

        $request->validate([
            'body'        => 'required|string|max:2000',
            'is_internal' => 'boolean',
        ]);

        $dept  = $submission->form->service->department;
        $pivot = $user->is_super_admin
            ? (object) ['role' => 'director']
            : $dept->users()->where('users.id', $user->id)->first()?->pivot;

        $isInternal = $request->boolean('is_internal');

        // Commentaire interne : seulement techniciens/directeurs
        if ($isInternal) {
            abort_unless(
                $user->is_super_admin
                || in_array($pivot?->role, ['technician', 'director']),
                403, 'Commentaire interne réservé aux techniciens.'
            );
        }

        Comment::create([
            'submission_id' => $submission->id,
            'author_id'     => $user->id,
            'body'          => $request->body,
            'is_internal'   => $isInternal,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => $user->id,
            'action'        => 'commented',
            'payload'       => ['is_internal' => $isInternal],
        ]);

        return back()->with('success', 'Commentaire ajouté.');
    }

    /* ─────────────────────────────────────────────────────────
     | Escalader au niveau supérieur
     ───────────────────────────────────────────────────────── */
    public function escalate(Request $request, Submission $submission)
    {
        $user = Auth::user();
        $dept = $submission->form->service->department;

        $pivot = $user->is_super_admin
            ? (object) ['role' => 'technician', 'tech_level' => 3]
            : $dept->users()->where('users.id', $user->id)->first()?->pivot;

        abort_unless(
            $pivot && in_array($pivot->role, ['technician', 'director']),
            403
        );

        // Trouver la prochaine étape de niveau supérieur
        $currentLevel = $submission->currentStep?->tech_level ?? ($pivot->tech_level ?? 1);
        $nextStep     = $submission->form->workflowSteps()
            ->where('tech_level', '>', $currentLevel)
            ->orderBy('tech_level')
            ->first();

        abort_unless($nextStep, 422, 'Aucun niveau supérieur disponible pour cette demande.');

        $submission->update([
            'current_step_id' => $nextStep->id,
            'assignee_id'     => null, // réassignation requise
            'status'          => Submission::STATUS_ESCALATED,
            'due_at'          => $nextStep->sla_hours
                ? now()->addHours($nextStep->sla_hours)
                : $submission->due_at,
        ]);

        SubmissionEvent::create([
            'submission_id' => $submission->id,
            'actor_id'      => $user->id,
            'action'        => 'escalated',
            'payload'       => [
                'from_level' => $currentLevel,
                'to_level'   => $nextStep->tech_level,
                'step'       => $nextStep->name,
            ],
        ]);

        return back()->with('success', 'Demande escaladée vers le niveau ' . $nextStep->tech_level . '.');
    }

    /* ─────────────────────────────────────────────────────────
     | Helpers d'autorisation
     ───────────────────────────────────────────────────────── */

    /** Peut voir la demande : demandeur | assigné | technicien/directeur du dept | super admin */
    private function authorizeView(Submission $sub, $user): void
    {
        if ($user->is_super_admin || $sub->requester_id === $user->id || $sub->assignee_id === $user->id) {
            return;
        }
        $dept   = $sub->form->service->department;
        $member = $dept->users()->where('users.id', $user->id)
            ->wherePivotIn('role', ['technician', 'director'])
            ->exists();
        abort_unless($member, 403);
    }

    /** Peut agir sur la demande : technicien/directeur du dept | super admin */
    private function authorizeAction(Submission $sub, $user): void
    {
        if ($user->is_super_admin) return;
        $dept   = $sub->form->service->department;
        $member = $dept->users()->where('users.id', $user->id)
            ->wherePivotIn('role', ['technician', 'director'])
            ->exists();
        abort_unless(
            $member || $sub->requester_id === $user->id,
            403
        );
    }
}
