<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Centre de notifications intranet, indépendant du type d'émetteur.
 *
 * Chaque notification expose un format `data` normalisé (category, title,
 * message, url, icon) : cette couche n'a donc pas à connaître les annonces,
 * les demandes ni le calendrier.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return view('intranet.notifications.index', compact('notifications'));
    }

    /**
     * Marque une notification comme lue puis redirige vers son contenu.
     * La notification appartient forcément à l'utilisateur (findOrFail sur
     * sa propre relation).
     */
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $notif = $request->user()->notifications()->findOrFail($notification);
        $notif->markAsRead();

        $url = $notif->data['url'] ?? route('intranet.notifications.index');

        return redirect()->to($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()
            ->back()
            ->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}
