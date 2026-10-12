<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
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
    
    // Gestion des utilisateurs
    Route::resource('users', UserManagementController::class);
    Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
        ->name('users.reset-password');
    
    // Gestion des rôles
    Route::resource('roles', RoleManagementController::class);
    
    // Synchronisation LDAP
    Route::get('ldap/sync', function() {
        return view('admin.ldap.sync');
    })->name('ldap.sync');
    
    Route::post('ldap/sync', function() {
        Artisan::call('ldap:sync');
        return redirect()->back()->with('success', 'Synchronisation LDAP lancée avec succès');
    })->name('ldap.sync.execute');
    
});
