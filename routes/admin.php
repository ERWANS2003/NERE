<?php

use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WeeklyMenuController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes d'administration
|--------------------------------------------------------------------------
|
| Routes réservées aux super administrateurs pour gérer les utilisateurs,
| rôles, et configuration du système.
|
*/

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Supervision globale
    Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring');

    // Gestion des utilisateurs
    Route::resource('users', UserManagementController::class);
    Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
        ->name('users.reset-password');
    
    // Gestion des rôles
    Route::resource('roles', RoleManagementController::class);

    // Menus hebdomadaires du personnel
    Route::get('menus', [WeeklyMenuController::class, 'index'])->name('menus.index');
    Route::get('menus/create', [WeeklyMenuController::class, 'create'])->name('menus.create');
    Route::post('menus', [WeeklyMenuController::class, 'store'])->name('menus.store');
    Route::get('menus/{menu}', [WeeklyMenuController::class, 'show'])->name('menus.show');
    Route::post('menus/{menu}/publier', [WeeklyMenuController::class, 'publish'])->name('menus.publish');
    Route::post('menus/{menu}/cloturer', [WeeklyMenuController::class, 'close'])->name('menus.close');
    Route::get('menus/{menu}/export', [WeeklyMenuController::class, 'export'])->name('menus.export');
    Route::delete('menus/{menu}', [WeeklyMenuController::class, 'destroy'])->name('menus.destroy');
    
    // Synchronisation LDAP
    Route::get('ldap/sync', function() {
        return view('admin.ldap.sync');
    })->name('ldap.sync');
    
    Route::post('ldap/sync', function() {
        Artisan::call('ldap:sync');
        return redirect()->back()->with('success', 'Synchronisation LDAP lancée avec succès');
    })->name('ldap.sync.execute');
    
});
