<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\ContentManagementController;
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

    // Gestion des communications et du calendrier
    Route::get('announcements', [ContentManagementController::class, 'announcements'])->name('announcements.index');
    Route::get('announcements/create', [ContentManagementController::class, 'createAnnouncement'])->name('announcements.create');
    Route::post('announcements', [ContentManagementController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('announcements/{announcement}/edit', [ContentManagementController::class, 'editAnnouncement'])->name('announcements.edit');
    Route::put('announcements/{announcement}', [ContentManagementController::class, 'updateAnnouncement'])->name('announcements.update');
    Route::delete('announcements/{announcement}', [ContentManagementController::class, 'destroyAnnouncement'])->name('announcements.destroy');
    Route::get('calendar', [ContentManagementController::class, 'calendar'])->name('calendar.index');
    Route::get('calendar/create', [ContentManagementController::class, 'createEvent'])->name('calendar.create');
    Route::post('calendar', [ContentManagementController::class, 'storeEvent'])->name('calendar.store');
    Route::get('calendar/{event}/edit', [ContentManagementController::class, 'editEvent'])->name('calendar.edit');
    Route::put('calendar/{event}', [ContentManagementController::class, 'updateEvent'])->name('calendar.update');
    Route::delete('calendar/{event}', [ContentManagementController::class, 'destroyEvent'])->name('calendar.destroy');

    Route::get('ldap/sync', fn () => redirect()->route('admin.settings'))
        ->name('ldap.sync');
    Route::post('ldap/sync', [AdminSettingsController::class, 'synchronizeLdap'])
        ->name('ldap.sync.execute');
});
