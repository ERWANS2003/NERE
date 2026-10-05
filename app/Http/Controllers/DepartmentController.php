<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Fiche d'un service.
 *
 * L'autorisation n'est pas verifiee ici : elle est Portee par le scope
 * `visibleTo`, seule source de verite partagee avec l'accueil et les policies.
 */
class DepartmentController extends Controller
{
    public function show(Request $request, string $code): View
    {
        $departement = Department::query()
            ->visibleTo($request->user())
            ->active()
            ->firstWhere('code', $code);

        /*
         * 404 et non 403. Un 403 confirmerait au demandeur que le service existe
         * alors qu'il n'a pas le droit de le voir : le portail deviendrait un
         * annuaire des services confidentiels.
         */
        abort_if($departement === null, 404);

        $identifiant = $departement->getKey();

        return view('departement', [
            'departement' => $departement,
            'role' => $request->user()->roleIn($identifiant),
            'niveau' => $request->user()->effectiveTechLevel($identifiant),
        ]);
    }
}
