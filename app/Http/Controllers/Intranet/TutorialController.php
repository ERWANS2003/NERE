<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->tutorial_completed_at === null) {
            $user->forceFill(['tutorial_completed_at' => now()])->save();
        }

        return back()->with('status', 'Tutoriel terminé.');
    }
}
