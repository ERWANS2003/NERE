<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Mise a jour du mot de passe par l'utilisateur lui-meme.
     */
    public function update(Request $request): RedirectResponse
    {
        // Erreurs dans le sac par defaut, comme le reste de l'application : les
        // deux formulaires de "mon compte" partagent la meme page mais des champs
        // differents, ils n'ont donc pas besoin de sacs separes. Un sac nomme
        // ici fonctionnerait, mais les vues lisent `$errors` et n'afficheraient
        // plus rien — un mot de passe actuel errone n'est serait signale que par
        // l'absence de message, ce qui est le pire signal possible.
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Le hash est produit par le cast 'hashed' du modele User : le Hasher
        // verifie de toute facon qu'il ne recoit pas un mot de passe deja hache.
        $request->user()->update([
            'password' => $validated['password'],
        ]);

        // Le verrouillage eventuel doit sauter : changer de mot de passe est
        // justement le reflexe attendu d'un utilisateur bloque.
        $request->user()->clearFailedAttempts();

        return back()->with('statut', 'mot-de-passe-modifie');
    }
}
