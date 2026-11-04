<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\FormSubmission;
use App\Models\KnowledgeArticle;
use App\Models\MenuCampaign;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(): View
    {
        $months = collect(range(5, 0))->map(function (int $offset): array {
            $month = now()->startOfMonth()->subMonths($offset);

            return [
                'label' => $month->translatedFormat('M Y'),
                'users' => User::whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->count(),
                'submissions' => FormSubmission::whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->count(),
            ];
        });

        $approvalDurations = TimeOffRequest::query()
            ->whereNotNull('approved_at')
            ->where('created_at', '>=', now()->subMonths(6))
            ->get(['created_at', 'approved_at'])
            ->map(fn (TimeOffRequest $request) => $request->created_at->diffInHours($request->approved_at))
            ->filter();

        $moduleUsage = [
            'Tickets support' => Ticket::count(),
            'Demandes' => FormSubmission::count(),
            'Articles' => KnowledgeArticle::count(),
            'Ressources' => Resource::count(),
            'Annonces' => Announcement::count(),
            'Campagnes menu' => MenuCampaign::count(),
        ];

        $requestsByDepartment = DB::table('departments')
            ->leftJoin('department_forms', 'departments.id', '=', 'department_forms.department_id')
            ->leftJoin('form_submissions', 'department_forms.id', '=', 'form_submissions.form_id')
            ->select('departments.id', 'departments.name', DB::raw('count(form_submissions.id) as requests_count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('requests_count')
            ->limit(10)
            ->get();

        return view('admin.reports.index', [
            'months' => $months,
            'userStatus' => ['Actifs' => User::where('actif', true)->count(), 'Inactifs' => User::where('actif', false)->count()],
            'roleDistribution' => Role::withCount('users')->orderByDesc('users_count')->get(),
            'moduleUsage' => $moduleUsage,
            'requestsByDepartment' => $requestsByDepartment,
            'averageApprovalHours' => $approvalDurations->isEmpty() ? null : round($approvalDurations->average(), 1),
            'failedJobsLast30Days' => DB::table('failed_jobs')->where('failed_at', '>=', now()->subDays(30))->count(),
        ]);
    }
}
