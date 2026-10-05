<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Department;
use App\Models\Intranet\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    /** Espace département : services + mes demandes + file de traitement */
    public function show(Department $department)
    {
        $user = Auth::user();

        // Autorisation : super admin ou membre du département
        if (! $user->is_super_admin) {
            $pivot = $department->users()->where('users.id', $user->id)->first()?->pivot;
            abort_unless($pivot !== null, 403, 'Accès non autorisé à ce département.');
        }

        $pivot = $user->is_super_admin
            ? (object) ['role' => 'director', 'tech_level' => null]
            : $department->users()->where('users.id', $user->id)->first()->pivot;

        $services = $department->services()
            ->where('is_active', true)
            ->with(['publishedForms'])
            ->get();

        // Mes demandes dans ce département
        $mySubmissions = Submission::whereHas(
            'form.service',
            fn ($q) => $q->where('department_id', $department->id)
        )
            ->where('requester_id', $user->id)
            ->with(['form', 'currentStep'])
            ->latest()
            ->limit(10)
            ->get();

        // File de traitement (technicien : son niveau ; directeur : tout)
        $queue = null;
        if (in_array($pivot->role, ['technician', 'director'])) {
            $queueQuery = Submission::whereHas(
                'form.service',
                fn ($q) => $q->where('department_id', $department->id)
            )
                ->whereNotIn('status', \App\Models\Intranet\Submission::TERMINAL_STATUSES)
                ->with(['requester', 'form', 'assignee', 'currentStep']);

            // Technicien : seulement son niveau ou assigné à lui
            if ($pivot->role === 'technician' && $pivot->tech_level) {
                $queueQuery->where(function ($q) use ($user, $pivot) {
                    $q->where('assignee_id', $user->id)
                      ->orWhere(function ($q2) use ($pivot) {
                          $q2->whereHas('currentStep', fn ($s) =>
                              $s->where('tech_level', $pivot->tech_level)
                          )->whereNull('assignee_id');
                      });
                });
            }

            $queue = $queueQuery->latest()->limit(20)->get();
        }

        return view('intranet.departments.show', compact(
            'department', 'pivot', 'services', 'mySubmissions', 'queue'
        ));
    }
}
