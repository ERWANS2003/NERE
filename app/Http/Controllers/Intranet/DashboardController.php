<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\Resource;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tableau de bord adaptatif : les données visibles dépendent du rôle
     * effectif dans l'intranet, jamais d'un simple masquage de bouton.
     *
     * - super-admin : vue globale et indicateurs de supervision ;
     * - directeur : cockpit de ses départements et files à valider ;
     * - technicien : activité opérationnelle de ses départements ;
     * - utilisateur : espace personnel (demandes, ressources, agenda).
     */
    public function index(Request $request): View
    {
        $user = $request->user()->load('departments');
        $isAdmin = (bool) $user->is_super_admin;
        $memberships = $user->departments;
        $departmentIds = $isAdmin
            ? Department::active()->pluck('id')
            : $memberships->pluck('id');
        $managedDepartmentIds = $isAdmin
            ? $departmentIds
            : $memberships
                ->filter(fn ($department) => $department->pivot->role === 'director')
                ->pluck('id');
        $roles = $memberships->pluck('pivot.role')->unique()->values();
        $isDirector = $isAdmin || $roles->contains('director');
        $isTechnician = $isDirector || $roles->contains('technician');

        $departments = Department::active()
            ->withCount('services')
            ->when(! $isAdmin, fn ($query) => $query->whereIn('id', $departmentIds))
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $teamQuery = User::query()->where('actif', true);
        if (! $isAdmin) {
            $teamQuery->whereHas('departments', fn ($query) => $query->whereIn('departments.id', $departmentIds));
        }

        $submissionQuery = FormSubmission::query();
        if (! $isAdmin && $managedDepartmentIds->isNotEmpty()) {
            $submissionQuery->whereHas('form', fn ($query) => $query->whereIn('department_id', $managedDepartmentIds));
        } elseif (! $isAdmin) {
            $submissionQuery->whereRaw('1 = 0');
        }

        $directReportIds = User::where('manager_id', $user->id)->pluck('id');
        $timeOffQuery = TimeOffRequest::query();
        if (! $isAdmin) {
            $timeOffQuery->whereIn('user_id', $directReportIds);
        }

        $stats = [
            'departments' => $departments->count(),
            'users' => $teamQuery->count(),
            'announcements' => $this->visibleAnnouncements($isAdmin, $departmentIds)->count(),
            'events' => $this->visibleEvents($isAdmin, $departmentIds)->count(),
            'submissions' => FormSubmission::where('user_id', $user->id)->count(),
            'pendingSubmissions' => (clone $submissionQuery)->where('status', 'pending')->count(),
            'pendingTimeOff' => $timeOffQuery->where('status', 'pending')->count(),
        ];

        $recentAnnouncements = $this->visibleAnnouncements($isAdmin, $departmentIds)
            ->with(['department', 'creator'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        $upcomingEvents = $this->visibleEvents($isAdmin, $departmentIds)
            ->with('department')
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->limit(4)
            ->get();

        $recentSubmissions = FormSubmission::with(['form.department'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $roleContext = [
            'label' => $isAdmin ? 'Administrateur général' : ($isDirector ? 'Directeur de département' : ($isTechnician ? 'Technicien' : 'Collaborateur')),
            'isAdmin' => $isAdmin,
            'isDirector' => $isDirector,
            'isTechnician' => $isTechnician,
            'departmentCount' => $departments->count(),
            'managedDepartmentCount' => $managedDepartmentIds->count(),
            'teamCount' => $stats['users'],
            'pendingSubmissions' => $stats['pendingSubmissions'],
            'pendingTimeOff' => $stats['pendingTimeOff'],
        ];

        $adminMonitoring = $isAdmin ? [
            'usersTotal' => User::count(),
            'usersActive' => User::where('actif', true)->count(),
            'usersInactive' => User::where('actif', false)->count(),
            'usersAd' => User::whereNotNull('guid')->count(),
            'usersLocal' => User::whereNull('guid')->count(),
            'pendingSubmissions' => FormSubmission::where('status', 'pending')->count(),
            'pendingTimeOff' => TimeOffRequest::where('status', 'pending')->count(),
            'resources' => Resource::where('is_active', true)->count(),
            'announcements' => Announcement::where('is_active', true)->count(),
            'events' => CalendarEvent::where('event_date', '>=', today())->count(),
            'roleAssignments' => User::whereHas('roles')->count(),
            'departmentAssignments' => \Illuminate\Support\Facades\DB::table('department_user')->count(),
        ] : null;

        return view('intranet.dashboard.index', compact(
            'user',
            'stats',
            'departments',
            'recentAnnouncements',
            'upcomingEvents',
            'recentSubmissions',
            'roleContext',
            'adminMonitoring',
        ));
    }

    private function visibleAnnouncements(bool $isAdmin, Collection $departmentIds)
    {
        return Announcement::query()
            ->where('is_active', true)
            ->when(! $isAdmin, fn ($query) => $query->where(function ($q) use ($departmentIds) {
                $q->whereNull('department_id')->orWhereIn('department_id', $departmentIds);
            }));
    }

    private function visibleEvents(bool $isAdmin, Collection $departmentIds)
    {
        return CalendarEvent::query()
            ->where('event_date', '>=', today())
            ->when(! $isAdmin, fn ($query) => $query->where(function ($q) use ($departmentIds) {
                $q->whereNull('department_id')->orWhereIn('department_id', $departmentIds);
            }));
    }
}
