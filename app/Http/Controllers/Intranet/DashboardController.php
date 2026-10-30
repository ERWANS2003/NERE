<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\MenuCampaign;
use App\Models\MenuChoice;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Intranet\CalendarFeed;
use App\Support\IntranetModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord intranet (page d'accueil).
     */
    public function index(
        Request $request,
        CalendarFeed $calendarFeed,
        IntranetModuleRegistry $modules,
    ): View
    {
        $user = $request->user();
        $enabledModules = $modules->all();
        $upcomingCalendarEvents = $calendarFeed->between(
            today(),
            today()->copy()->addYear(),
            $user
        );

        $isSystemAdmin = $user->is_super_admin;
        $canProcessRequests = $user->hasDepartmentProcessingAccess();
        $openTicketStatuses = ['new', 'in_progress', 'waiting'];

        $stats = [
            'events' => count($upcomingCalendarEvents),
            'submissions' => FormSubmission::where('user_id', $user->id)->count(),
            'open_submissions' => FormSubmission::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'open_tickets' => Ticket::where('requester_id', $user->id)
                ->whereIn('status', $openTicketStatuses)
                ->count(),
        ];

        if ($isSystemAdmin) {
            $stats['departments'] = Department::active()->count();
            $stats['users'] = User::where('actif', true)->count();
            $stats['submissions'] = FormSubmission::count();
        }

        // Tous les départements actifs pour la grille d'accueil.
        $departments = Department::active()
            ->withCount('services')
            ->ordered()
            ->get();

        // Widget : 3 dernières annonces publiées.
        $recentAnnouncements = Announcement::with(['department', 'creator'])
            ->visibleTo($user)
            ->where('is_active', true)
            ->latest('published_at')
            ->limit(3)
            ->get();

        // Menus du personnel publiés, pour les employés qui choisissent leurs plats.
        $openMenuCampaigns = collect();
        if ($user->canChooseMenu()) {
            $openMenuCampaigns = MenuCampaign::query()
                ->openForRegistration()
                ->withExists(['registrations as already_chosen' => fn ($query) => $query->where('user_id', $user->id)])
                ->orderBy('starts_on')
                ->get();
        }

        // Plat choisi pour aujourd'hui, s'il y en a un.
        $publishedToday = fn ($query) => $query->whereDate('date', today())
            ->whereHas('campaign', fn ($campaign) => $campaign->where('status', '!=', 'draft'));
        $todayMenuChoice = $user->canChooseMenu()
            ? MenuChoice::query()
                ->whereHas('registration', fn ($query) => $query->where('user_id', $user->id))
                ->whereHas('day', $publishedToday)
                ->with('day')
                ->first()
            : null;

        // Widget : 4 prochains événements de l'agenda, jours fériés compris.
        $upcomingEvents = array_slice($upcomingCalendarEvents, 0, 4);

        // Les équipes voient aussi les demandes de leur département qu'elles peuvent traiter.
        $departmentIds = Department::query()
            ->pluck('id')
            ->filter(fn (int $departmentId) => $user->canHandleIn($departmentId));

        if ($canProcessRequests) {
            $stats['departments_to_process'] = $departmentIds->count();
            $stats['pending_department_submissions'] = FormSubmission::query()
                ->where('status', 'pending')
                ->whereHas('form', fn ($forms) => $forms->whereIn('department_id', $departmentIds))
                ->count();
        }

        $recentSubmissions = FormSubmission::with(['form.department', 'user'])
            ->where(fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhereHas('form', fn ($forms) => $forms->whereIn('department_id', $departmentIds)))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (FormSubmission $submission) => [
                'reference' => '#'.str_pad((string) $submission->id, 5, '0', STR_PAD_LEFT),
                'title' => $submission->form?->name ?? 'Formulaire supprimé',
                'department' => $submission->form?->department?->name ?? '—',
                'status' => $submission->status,
                'date' => $submission->created_at,
                'requester' => $submission->user?->name ?? 'Compte supprimé',
                'url' => route('intranet.submissions.show', $submission->id),
                'kind' => 'Formulaire',
            ]);

        $recentTickets = Ticket::with(['department', 'requester'])
            ->where(fn ($query) => $query
                ->where('requester_id', $user->id)
                ->orWhereIn('department_id', $departmentIds))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Ticket $ticket) => [
                'reference' => $ticket->number,
                'title' => $ticket->subject,
                'department' => $ticket->department?->name ?? '—',
                'status' => $ticket->status->value,
                'date' => $ticket->created_at,
                'requester' => $ticket->requester?->name ?? 'Compte supprimé',
                'url' => route('intranet.tickets.show', $ticket),
                'kind' => 'Ticket',
            ]);

        $recentRequests = $recentSubmissions
            ->concat($recentTickets)
            ->sortByDesc('date')
            ->take(5)
            ->values();

        return view('intranet.dashboard.index', compact(
            'user',
            'isSystemAdmin',
            'canProcessRequests',
            'stats',
            'departments',
            'recentAnnouncements',
            'upcomingEvents',
            'recentRequests',
            'openMenuCampaigns',
            'todayMenuChoice',
            'enabledModules'
        ));
    }
}
