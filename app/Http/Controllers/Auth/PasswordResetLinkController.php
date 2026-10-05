<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Affiche le formulaire de demande de reinitialisation.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Envoie le lien de reinitialisation.
     *
     * Anti-enumeration : un compte inconnu ne doit pas se distinguer d'un lien
     * reellement envoye, sinon le formulaire permettrait de recenser les adresses
     * e-mail employees. La seule erreur vraiment annoncee reste la limitation de
     * debit, qui concerne la demande elle-meme et non l'existence du compte.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::INVALID_USER) {
            return back()
                ->withInput($request->only('email'))
                ->with('statut', trans(Password::RESET_LINK_SENT));
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->withInput($request->only('email'))->with('statut', trans($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => trans($status)]);
    }
}
