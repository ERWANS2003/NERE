<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Department;
use App\Models\Intranet\Submission;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function show(Department $department)
    {
        $this->authorize('view', $department);

        $user  = Auth::user();
        $pivot = $user->intranetPivot($department);

        $services = $department->services()
            ->where('is_active', true)
            ->with(['publishedForms'])
            ->get();

        $mySubmissions = Submission::whereHas(
            'form.service', fn ($q) => $q->where('department_id', $department->id)
        )
            ->where('requester_id', $user->id)
            ->with(['form', 'currentStep'])
            ->latest()
            ->limit(10)
            ->get();

        $queue = null;
        if (in_array($pivot->role, ['technician', 'director'])) {
            $queueQuery = Submission::whereHas(
                'form.service', fn ($q) => $q->where('department_id', $department->id)
            )
                ->whereNotIn('status', Submission::TERMINAL_STATUSES)
                ->with(['requester', 'form', 'assignee', 'currentStep']);

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
