<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Page d'accueil : la liste des services dont l'agent a le droit de se servir.
 *
 * C'est le point d'entree du portail, pas un tableau de bord : un employe doit
 * voir en un coup d'oeil vers qui s'adresser, sans avoir a choisir un service au
 * hasard.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $utilisateur = $request->user();

        /*
         * Les droits sont charges une seule fois : la page affiche le role de
         * l'agent sur chaque service, soit six lookups de role. Sans
         * `loadAuthorisations`, chacun triggerait sa requete sur le pivot.
         */
        $utilisateur->loadAuthorisations();

        return view('accueil', [
            'utilisateur' => $utilisateur,
            'services' => Department::query()
                ->visibleTo($utilisateur)
                ->active()
                ->ordered()
                ->get(),
        ]);
    }
}
