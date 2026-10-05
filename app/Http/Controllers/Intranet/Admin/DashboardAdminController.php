<?php

namespace App\Http\Controllers\Intranet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Department;
use App\Models\Intranet\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->is_super_admin, 403);
            return $next($request);
        });
    }

    public function index()
    {
        /* ── Statistiques globales ───────────────────────────── */
        $stats = [
            'total'     => Submission::count(),
            'open'      => Submission::open()->count(),
            'overdue'   => Submission::overdue()->count(),
            'resolved'  => Submission::where('status', Submission::STATUS_RESOLVED)->count(),
            'closed'    => Submission::where('status', Submission::STATUS_CLOSED)->count(),
            'users'     => User::where('actif', true)->count(),
        ];

        /* ── Par département ─────────────────────────────────── */
        $byDepartment = Department::active()
            ->withCount([
                'services',
                // submissions via relation macro
            ])
            ->get()
            ->map(function (Department $dept) {
                $base = Submission::whereHas(
                    'form.service', fn ($q) => $q->where('department_id', $dept->id)
                );
                return [
                    'name'    => $dept->name,
                    'color'   => $dept->color,
                    'total'   => (clone $base)->count(),
                    'open'    => (clone $base)->open()->count(),
                    'overdue' => (clone $base)->overdue()->count(),
                ];
            });

        /* ── 10 dernières soumissions ────────────────────────── */
        $recent = Submission::with(['form.service.department', 'requester'])
            ->latest()
            ->limit(10)
            ->get();

        /* ── Délai moyen de résolution (en heures) ───────────── */
        $avgResolutionHours = Submission::whereNotNull('closed_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (closed_at - created_at)) / 3600) as avg_hours')
            ->value('avg_hours');

        return view('intranet.admin.dashboard', compact(
            'stats', 'byDepartment', 'recent', 'avgResolutionHours'
        ));
    }
}
