<?php

use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Menu\CampaignController;
use App\Http\Controllers\Menu\MenuExportController;
use App\Http\Controllers\Menu\RegistrationController;
use App\Http\Controllers\ProfileController;
use App\Models\Department;
use App\Models\User;
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

// Routes protegees du portail, du profil et de la recherche
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/accueil', HomeController::class)->name('accueil');

    Route::get('/departements/{code}', [DepartmentController::class, 'show'])
        ->where('code', '[A-Z0-9]{2,16}')
        ->name('departement.show');

    // Compatibilite historique: alias /logout sans redeclarer le nom de route global
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    // Profil utilisateur (alias profile.edit pour les vues intranet, profil.* pour les vues profil)
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::get('/profil/apercu', [ProfileController::class, 'show'])->name('profil.show');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Anciennes URL conservees pour les liens historiques et les collaborateurs.
    Route::get('/mon-compte', [ProfileController::class, 'edit']);
    Route::patch('/mon-compte', [ProfileController::class, 'update']);

    // API de recherche globale (consommée par le modal Cmd+K, session web)
    Route::get('/api/search/data', [SearchController::class, 'data'])->name('api.search.data');
});

// Test route
Route::get('/test', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel + PostgreSQL fonctionne !',
        'timestamp' => now(),
        'users_count' => User::count(),
        'departments_count' => Department::count(),
    ]);
});

// Import intranet routes
require __DIR__.'/intranet.php';

// Import auth routes
require __DIR__.'/auth.php';

// Import admin routes
require __DIR__.'/admin.php';

Route::middleware(['auth', 'active'])
    ->prefix('menu')
    ->name('menu.')
    ->group(function (): void {
        Route::get('/', [RegistrationController::class, 'index'])->name('portal');

        Route::middleware('menu.manager')->prefix('gestion')->name('manager.')->group(function (): void {
            Route::get('/', [CampaignController::class, 'index'])->name('campaigns');
            Route::get('/creer', [CampaignController::class, 'create'])->name('create');
            Route::post('/', [CampaignController::class, 'store'])->name('store');
            Route::post('/campagnes/{campaign}/cloturer', [CampaignController::class, 'close'])->name('close');
            Route::get('/campagnes/{campaign}/exporter', MenuExportController::class)->name('export');
        });

        Route::get('/{campaign:link_token}', [RegistrationController::class, 'show'])->name('registration.show');
        Route::post('/{campaign:link_token}', [RegistrationController::class, 'store'])->name('registration.store');
    });
