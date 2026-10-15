<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\Resource;
use App\Models\Role;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Vue globale de supervision réservée au super-administrateur.
 *
 * Elle agrège les indicateurs opérationnels de l'intranet, sans exposer de
 * mots de passe, de secrets LDAP ou de données d'authentification sensibles.
 */
class MonitoringController extends Controller
{
    public function index(): View
    {
        $metrics = [
            'usersTotal' => User::count(),
            'usersActive' => User::where('actif', true)->count(),
            'usersInactive' => User::where('actif', false)->count(),
            'usersAd' => User::whereNotNull('guid')->count(),
            'usersLocal' => User::whereNull('guid')->count(),
            'departments' => Department::active()->count(),
            'departmentAssignments' => DB::table('department_user')->count(),
            'roleAssignments' => DB::table('role_user')->count(),
            'pendingSubmissions' => FormSubmission::where('status', 'pending')->count(),
            'pendingTimeOff' => TimeOffRequest::where('status', 'pending')->count(),
            'resources' => Resource::where('is_active', true)->count(),
            'announcements' => Announcement::where('is_active', true)->count(),
            'upcomingEvents' => CalendarEvent::where('event_date', '>=', today())->count(),
        ];

        $departments = Department::active()
            ->withCount(['users' => fn ($query) => $query->where('actif', true)])
            ->orderByDesc('users_count')
            ->orderBy('name')
            ->get();

        $roles = Role::withCount('users')->orderByDesc('users_count')->orderBy('nom')->get();

        $recentlySynced = User::whereNotNull('guid')
            ->latest('updated_at')
            ->limit(10)
            ->get(['id', 'name', 'email', 'actif', 'updated_at']);

        $pendingSubmissions = FormSubmission::with(['user', 'form.department'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        $pendingTimeOff = TimeOffRequest::with('user')
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        $recentAudit = AuditLog::with('user')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.monitoring.index', compact(
            'metrics',
            'departments',
            'roles',
            'recentlySynced',
            'pendingSubmissions',
            'pendingTimeOff',
            'recentAudit',
        ));
    }
}
