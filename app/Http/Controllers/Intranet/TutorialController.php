<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Support\IntranetGuides;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorialController extends Controller
{
    /** Centre d'aide : les guides du rôle de l'utilisateur, en version interactive et écrite. */
    public function help(Request $request): View
    {
        return view('intranet.help.index', [
            'guides' => collect(IntranetGuides::for($request->user()))->groupBy('role'),
        ]);
    }

    /** Étapes d'un guide, chargées par le script au lancement. */
    public function guide(Request $request, string $guide): JsonResponse
    {
        $guides = IntranetGuides::for($request->user());
        abort_unless(isset($guides[$guide]), 404);

        return response()->json(['steps' => $guides[$guide]['steps']]);
    }

    public function complete(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->tutorial_completed_at === null) {
            $user->forceFill(['tutorial_completed_at' => now()])->save();
        }

        // Le guide valide sans recharger la page ; le formulaire reste un repli.
        if ($request->expectsJson()) {
            return response()->json(['completed_at' => $user->tutorial_completed_at->toIso8601String()]);
        }

        return back()->with('status', 'Tutoriel terminé.');
    }
}
