<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Les URL sont en francais comme l'interface : un utilisateur qui lit une URL
 * dans un mail ou dans un ticket doit pouvoir la comprendre sans connaitre
 * l'anglais technique.
 */

Route::get('/', function (Request $request) {
    // Une seule route racine, sans middleware `guest` : declarer "/" en `guest`
    // ferait boucler la redirection des utilisateurs connectes
    // (connecte -> "/" -> connexion -> connecte).
    return $request->user()
        ? redirect()->route('accueil')
        : redirect()->route('login');
})->name('racine');

/*
 * Le middleware `active` est pose sur tout le groupe authentifie, pas route par
 * route. Une session deja ouverte ne disparait pas quand l'administration
 * desactive un compte : sans cette contrainte sur chaque route, un desactive
 * pourrait continuer a naviguer et a modifier son profil jusqu'a expiration de sa
 * session. Les routes suivantes n'ont donc rien a ajouter.
 */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/accueil', HomeController::class)->name('accueil');

    /*
     * Le service est designe par son code metier (IT, HSE...), pas par son
     * identifiant interne : l'URL reste lisible dans un mail et stable quand la
     * base est reconstruite. Le motif borne le segment a ce qu'un code peut
     * contenir, le reste tombant en 404 par le controleur.
     */
    Route::get('/departements/{code}', [DepartmentController::class, 'show'])
        ->where('code', '[A-Z0-9]{2,16}')
        ->name('departement.show');

    Route::get('/mon-compte', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::patch('/mon-compte', [ProfileController::class, 'update'])->name('profil.update');
});

require __DIR__.'/auth.php';
