<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KnowledgeArticleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Phase 3 : Authentification
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login']);
    Route::get('/mot-de-passe-oublie', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

    // Phase 7 : Tableau de bord
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Phase 4 : Module Tickets
    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/commentaires', [TicketController::class, 'storeComment'])->name('tickets.comments.store');
    Route::post('tickets/{ticket}/pieces-jointes', [TicketController::class, 'storeAttachment'])->name('tickets.attachments.store');

    // Phase 8 : Gestion des actifs
    Route::resource('actifs', AssetController::class)->only(['index', 'store'])->names('assets');
    Route::post('actifs/{asset}/lier-ticket', [AssetController::class, 'lierTicket'])->name('assets.link-ticket');

    // Phase 9 : Base de connaissances
    Route::get('base-connaissances', [KnowledgeArticleController::class, 'index'])->name('knowledge.index');
    Route::get('base-connaissances/suggestions', [KnowledgeArticleController::class, 'suggerer'])->name('knowledge.suggest');
    Route::post('base-connaissances', [KnowledgeArticleController::class, 'store'])->name('knowledge.store');
    Route::get('base-connaissances/{article}', [KnowledgeArticleController::class, 'show'])->name('knowledge.show');

    // Phase 11 : Rapports
    Route::get('rapports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('rapports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('rapports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('rapports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');

    // Phase 12 : Administration (réservé aux Admins)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('utilisateurs', UserController::class)->only(['index', 'store', 'update', 'destroy'])->names('users');

        Route::get('parametres/categories', [SettingsController::class, 'categories'])->name('settings.categories');
        Route::post('parametres/categories', [SettingsController::class, 'storeCategory'])->name('settings.categories.store');

        Route::get('parametres/sites', [SettingsController::class, 'sites'])->name('settings.sites');
        Route::post('parametres/sites', [SettingsController::class, 'storeSite'])->name('settings.sites.store');

        Route::get('parametres/slas', [SettingsController::class, 'slas'])->name('settings.slas');
        Route::post('parametres/slas', [SettingsController::class, 'storeSla'])->name('settings.slas.store');

        Route::get('parametres/matrice-priorites', [SettingsController::class, 'priorityMatrix'])->name('settings.priority-matrix');
        Route::post('parametres/matrice-priorites', [SettingsController::class, 'updatePriorityMatrix'])->name('settings.priority-matrix.update');

        Route::get('parametres/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
        Route::post('parametres/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    });
});
