<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirme le mot de passe de l'utilisateur.
     *
     * La session d'authentification herite du matricule ou de l'email selon ce que
     * l'utilisateur a saisi : on reutilise donc l'identifiant stocke en session
     * plutot que de forcer `email`, sans quoi la validation echouerait pour
     * quelqu'un connecte avec son matricule.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            $request->user()->getAuthIdentifierName() => $request->user()->getAuthIdentifier(),
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => trans('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('accueil', absolute: false));
    }
}
