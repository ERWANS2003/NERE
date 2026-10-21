<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\FormSubmission;
use App\Notifications\SubmissionReviewed;
use App\Support\DynamicFormSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    /**
     * Liste des demandes de l'utilisateur connecté (filtrable par statut).
     */
    public function index(Request $request): View
    {
        $query = FormSubmission::with(['form.department'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $request->status);
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();

        return view('intranet.submissions.index', compact('submissions'));
    }

    /**
     * File de traitement des demandes des départements de l'utilisateur.
     */
    public function queue(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasDepartmentProcessingAccess(), 403);

        $departmentIds = $user->is_super_admin
            ? Department::query()->pluck('id')
            : $user->departments()
                ->wherePivotIn('role', ['technician', 'director'])
                ->pluck('departments.id');

        $query = FormSubmission::with(['form.department', 'user', 'assignee'])
            ->whereHas('form', fn ($forms) => $forms->whereIn('department_id', $departmentIds));

        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $assignedToMeCount = (clone $query)
            ->where('status', 'pending')
            ->where('assigned_to', $user->id)
            ->count();

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->input('assignment') === 'mine') {
            $query->where('assigned_to', $user->id);
        } elseif ($request->input('assignment') === 'unassigned') {
            $query->whereNull('assigned_to');
        }

        if ($request->filled('department') && $departmentIds->contains((int) $request->department)) {
            $query->whereHas('form', fn ($forms) => $forms->where('department_id', $request->integer('department')));
        }

        $submissions = $query->latest()->paginate(20)->withQueryString();
        $departments = Department::query()->whereKey($departmentIds)->orderBy('name')->get();

        return view('intranet.submissions.queue', compact(
            'submissions',
            'pendingCount',
            'assignedToMeCount',
            'departments',
        ));
    }

    /**
     * Enregistre une nouvelle demande (soumission d'un formulaire).
     */
    public function store(Request $request, DepartmentForm $form): RedirectResponse
    {
        $form->loadMissing('department');
        abort_unless($form->is_active && $form->department?->is_active, 404);

        $fields = DynamicFormSchema::fields($form->form_schema);
        $validated = $request->validate(DynamicFormSchema::submissionRules([
            'fields' => $fields,
        ]));
        $submittedData = array_intersect_key(
            $validated['data'],
            array_column($fields, null, 'key')
        );

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'user_id' => $request->user()->id,
            'data' => $submittedData,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('intranet.submissions.show', $submission)
            ->with('success', 'Votre demande a été soumise avec succès.');
    }

    /**
     * Affiche une demande. L'utilisateur ne voit que ses propres demandes
     * (sauf s'il peut traiter le département concerné).
     */
    public function show(Request $request, FormSubmission $submission): View
    {
        $submission->load(['form.department', 'user', 'reviewer', 'assignee']);

        $user = $request->user();
        $departmentId = $submission->form?->department_id;

        $autorise = $submission->user_id === $user->id
            || $user->is_super_admin
            || ($departmentId !== null && $user->canHandleIn($departmentId));

        abort_unless($autorise, 403);

        return view('intranet.submissions.show', compact('submission'));
    }

    /**
     * Met à jour le statut d'une demande (validation / rejet par un traiteur).
     */
    public function update(Request $request, FormSubmission $submission): RedirectResponse
    {
        $submission->loadMissing('form');
        $departmentId = $submission->form?->department_id;

        abort_unless(
            $request->user()->is_super_admin
                || ($departmentId !== null && $request->user()->canValidateIn($departmentId)),
            403
        );
        abort_unless($submission->status === 'pending', 409);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->update([
            'status' => $validated['status'],
            'review_comment' => $validated['review_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Prévient le demandeur du verdict (sauf s'il s'est auto-traité).
        $demandeur = $submission->user;
        if ($demandeur && $demandeur->id !== $request->user()->id && $demandeur->actif) {
            $demandeur->notify(new SubmissionReviewed($submission));
        }

        return redirect()
            ->route('intranet.submissions.show', $submission)
            ->with('success', 'La demande a été mise à jour.');
    }

    /**
     * Prend en charge une demande en attente pour un technicien ou un chef.
     */
    public function take(Request $request, FormSubmission $submission): RedirectResponse
    {
        $submission->loadMissing('form');
        $departmentId = $submission->form?->department_id;
        abort_unless($departmentId !== null && $request->user()->canHandleIn($departmentId), 403);
        abort_unless($submission->status === 'pending', 409);

        $updated = FormSubmission::query()
            ->whereKey($submission->getKey())
            ->where('status', 'pending')
            ->where(function ($query) use ($request): void {
                $query->whereNull('assigned_to')
                    ->orWhere('assigned_to', $request->user()->id);
            })
            ->update([
                'assigned_to' => $request->user()->id,
                'assigned_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return back()->with('error', 'Cette demande est déjà prise en charge par un autre membre de l’équipe.');
        }

        return redirect()
            ->route('intranet.submissions.show', $submission)
            ->with('success', 'La demande vous a été attribuée.');
    }

    /**
     * Supprime une demande (le propriétaire tant qu'elle est en attente).
     */
    public function destroy(Request $request, FormSubmission $submission): RedirectResponse
    {
        abort_unless(
            $submission->user_id === $request->user()->id && $submission->status === 'pending',
            403
        );

        $submission->delete();

        return redirect()
            ->route('intranet.submissions.index')
            ->with('success', 'La demande a été supprimée.');
    }
}
