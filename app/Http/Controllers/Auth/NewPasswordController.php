<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Affiche le formulaire de choix d'un nouveau mot de passe.
     *
     * Le jeton et l'adresse viennent tous deux du lien : le jeton est un parametre
     * de route, l'adresse un parametre de chaine de requete, appendu par le
     * generateur de notification. Sans les deux, le formulaire n'affiche ni le
     * jeton a renvoyer ni l'adresse du compte a reinitialiser.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'token' => (string) $request->route('token'),
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Enregistre le nouveau mot de passe.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // Cast 'hashed' : le mot de passe est hache a la persistance.
                $user->forceFill([
                    'password' => $password,
                    // L'ancien "se souvenir de moi" doit mourir avec l'ancien mot
                    // de passe : un poste oublie ne doit pas rester connecte.
                    'remember_token' => Str::random(60),
                ])->save();

                $user->clearFailedAttempts();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('statut', trans($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => trans($status)]);
    }
}
