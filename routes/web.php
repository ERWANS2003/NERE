<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route d'accueil - redirige vers le tableau de bord
Route::get('/', function (Request $request) {
    return $request->user() 
        ? redirect()->route('intranet.dashboard') 
        : redirect()->route('login');
})->name('racine');

// Routes d'authentification avec Breeze
// Les routes canoniques sont definies dans routes/auth.php pour eviter les collisions
// de noms de route avec /connexion et /deconnexion.
Route::middleware('guest')->group(function () {
    // Compatibilite historique: ancien chemin /login redirige vers la page de connexion
    Route::get('/login', [AuthenticatedSessionController::class, 'create']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Redirection de l'ancienne URL
    Route::get('/connexion', function () {
        return redirect()->route('login');
    });
});

// Routes protégées - logout + profil
Route::middleware(['auth'])->group(function () {
    // Compatibilite historique: alias /logout sans redeclarer le nom de route global
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    // Profil utilisateur (alias profile.edit pour les vues intranet, profil.* pour les vues profil)
    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profil.edit');
    Route::get('/profil/apercu', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profil.show');
    Route::patch('/profil', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profil.update');
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');

    // API de recherche globale (consommée par le modal Cmd+K, session web)
    Route::get('/api/search/data', [\App\Http\Controllers\Api\SearchController::class, 'data'])->name('api.search.data');
});

// Test route
Route::get('/test', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel + PostgreSQL fonctionne !',
        'timestamp' => now(),
        'users_count' => \App\Models\User::count(),
        'departments_count' => \App\Models\Department::count()
    ]);
});

// Import intranet routes
require __DIR__.'/intranet.php';

// Import auth routes
require __DIR__.'/auth.php';

// Import admin routes
require __DIR__.'/admin.php';
