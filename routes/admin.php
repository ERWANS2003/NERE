<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\UserManagementController;
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
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/ldap/sync', [AdminSettingsController::class, 'synchronizeLdap'])
        ->name('settings.ldap.sync');

    // Gestion des utilisateurs
    Route::resource('users', UserManagementController::class);
    Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
        ->name('users.reset-password');

    // Gestion des rôles
    Route::resource('roles', RoleManagementController::class);

    Route::get('ldap/sync', fn () => redirect()->route('admin.settings'))
        ->name('ldap.sync');
    Route::post('ldap/sync', [AdminSettingsController::class, 'synchronizeLdap'])
        ->name('ldap.sync.execute');
});
