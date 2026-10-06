<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profil.show', [
            'user' => $request->user(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('profil.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Mise a jour des coordonnees personnelles.
     *
     * Ni le matricule ni le statut du compte ne sont modifiables ici : le
     * matricule est attribue par les RH, et la desactivation d'un compte est une
     * decision administrative. Un salarie ne doit pas pouvoir s'effacer de
     * l'organigramme en supprimant son propre compte depuis son poste.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated())->save();

        return redirect()
            ->route('profil.edit')
            ->with('statut', 'profil-modifie');
    }
}
