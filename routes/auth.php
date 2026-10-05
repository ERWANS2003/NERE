<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

/*
 * Pas d'inscription publique : un compte est cree par l'administration, avec le
 * matricule attribue par les RH. Une page "register" accessible a tous
 * transformerait l'intranet en site ouvert, et le matricule n'est pas
 * auto-attribuable.
 *
 * Pas de verification d'email non plus : les comptes sont coches par un humain a la
 * creation, le matricule servant d'identifiant metier. Garder ce parcours
 * obligerait chaque salarie a gerer une boite mail dont personne ne surveille les
 * liens.
 */

Route::middleware('guest')->group(function () {
    Route::get('connexion', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('connexion', [AuthenticatedSessionController::class, 'store']);

    Route::get('mot-de-passe/oubie', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('mot-de-passe/oubie', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('mot-de-passe/reinitialisation/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('mot-de-passe/reinitialisation', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('mot-de-passe/confirmation', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('mot-de-passe/confirmation', [ConfirmablePasswordController::class, 'store']);

    Route::put('mot-de-passe', [PasswordController::class, 'update'])->name('password.update');

    Route::post('deconnexion', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
