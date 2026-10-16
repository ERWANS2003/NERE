<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord intranet (page d'accueil).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Statistiques générales affichées en haut du tableau de bord.
        $stats = [
            'departments' => Department::active()->count(),
            'users' => User::where('actif', true)->count(),
            'announcements' => Announcement::where('is_active', true)->count(),
            'events' => CalendarEvent::where('event_date', '>=', today())->count(),
            'submissions' => FormSubmission::where('user_id', $user->id)->count(),
        ];

        // Tous les départements actifs pour la grille d'accueil.
        $departments = Department::active()
            ->withCount('services')
            ->ordered()
            ->get();

        // Widget : 3 dernières annonces publiées.
        $recentAnnouncements = Announcement::with(['department', 'creator'])
            ->where('is_active', true)
            ->latest('published_at')
            ->limit(3)
            ->get();

        // Widget : 4 prochains événements de l'agenda.
        $upcomingEvents = CalendarEvent::with('department')
            ->where('event_date', '>=', today())
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->limit(4)
            ->get();

        // Section : 5 dernières demandes (soumissions de formulaires) de l'utilisateur.
        $recentSubmissions = FormSubmission::with(['form.department'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('intranet.dashboard.index', compact(
            'user',
            'stats',
            'departments',
            'recentAnnouncements',
            'upcomingEvents',
            'recentSubmissions'
        ));
    }
}
