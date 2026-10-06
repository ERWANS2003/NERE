<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Contrôleur d'authentification alternatif
 * 
 * Ce contrôleur est créé pour gérer les références legacy
 * vers AuthController dans le système.
 */
class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Traite la tentative de connexion
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('accueil', absolute: false));
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Alias pour showLogin pour compatibilité
     */
    public function create(): View
    {
        return $this->showLogin();
    }

    /**
     * Alias pour login pour compatibilité
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        return $this->login($request);
    }

    /**
     * Alias pour logout pour compatibilité
     */
    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request);
    }
}