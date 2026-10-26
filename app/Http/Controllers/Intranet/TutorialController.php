<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
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
