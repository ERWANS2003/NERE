<?php

use App\Http\Controllers\Intranet\AnalyticsController;
use App\Http\Controllers\Intranet\AnnouncementController;
use App\Http\Controllers\Intranet\CalendarController;
use App\Http\Controllers\Intranet\DashboardController;
use App\Http\Controllers\Intranet\DepartmentController as IntranetDepartmentController;
use App\Http\Controllers\Intranet\DepartmentShowController;
use App\Http\Controllers\Intranet\DirectoryController;
use App\Http\Controllers\Intranet\FormController;
use App\Http\Controllers\Intranet\NotificationController;
use App\Http\Controllers\Intranet\OrganizationController;
use App\Http\Controllers\Intranet\ResourceController;
use App\Http\Controllers\Intranet\SubmissionController;
use App\Http\Controllers\Intranet\TimeOffApprovalController;
use App\Http\Controllers\Intranet\TimeOffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Intranet Routes
|--------------------------------------------------------------------------
|
| Routes pour l'intranet NÉRÉ Mining.
| Toutes les routes sont protégées par auth et active.
|
*/

Route::middleware(['auth', 'active'])->prefix('intranet')->name('intranet.')->group(function () {

    // Dashboard (Accueil) — vue unique rendue par le contrôleur
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Départements — vitrine (vues maquette, basées sur le code du département)
    Route::prefix('departments')->name('departments.')->group(function () {

        Route::get('/', function () {
            return view('intranet.departments.index');
        })->name('index');

        // CRUD administration des départements (contrôleur), sous /manage
        Route::prefix('manage')->name('manage.')->group(function () {
            Route::get('/', [IntranetDepartmentController::class, 'index'])->name('index');
            Route::get('/create', [IntranetDepartmentController::class, 'create'])->name('create');
            Route::post('/', [IntranetDepartmentController::class, 'store'])->name('store');
            Route::get('/{department}', [IntranetDepartmentController::class, 'show'])->name('show');
            Route::get('/{department}/edit', [IntranetDepartmentController::class, 'edit'])->name('edit');
            Route::put('/{department}', [IntranetDepartmentController::class, 'update'])->name('update');
            Route::delete('/{department}', [IntranetDepartmentController::class, 'destroy'])->name('destroy');
        });

        // Vitrine détail par code (ex: /intranet/departments/it)
        Route::get('/{code}', DepartmentShowController::class)->name('show');
    });

    // Gestion des formulaires
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/create', [FormController::class, 'create'])->name('create');
        Route::post('/', [FormController::class, 'store'])->name('store');
        Route::get('/{form}', [FormController::class, 'show'])->name('show');
        Route::get('/{form}/edit', [FormController::class, 'edit'])->name('edit');
        Route::put('/{form}', [FormController::class, 'update'])->name('update');
        Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy');
    });

    // Gestion des soumissions de formulaires (= "Mes demandes")
    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [SubmissionController::class, 'index'])->name('index');
        Route::post('/forms/{form}', [SubmissionController::class, 'store'])->name('store');
        Route::get('/{submission}', [SubmissionController::class, 'show'])->name('show');
        Route::put('/{submission}', [SubmissionController::class, 'update'])->name('update');
        Route::delete('/{submission}', [SubmissionController::class, 'destroy'])->name('destroy');
    });

    // Annuaire
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory');

    // Organigramme
    Route::get('/orgchart', [OrganizationController::class, 'index'])->name('orgchart');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // Calendrier
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    // Annonces
    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::get('/create', [AnnouncementController::class, 'create'])->name('create');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
        Route::get('/{announcement}', [AnnouncementController::class, 'show'])->name('show');
        Route::get('/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('edit');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
        Route::post('/{announcement}/marquer-lu', [AnnouncementController::class, 'markRead'])->name('mark-read');
    });

    // Ressources
    Route::get('/resources', [ResourceController::class, 'index'])->name('resources');
    Route::get('/resources/ajouter', [ResourceController::class, 'create'])->name('resources.create');
    Route::post('/resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::get('/resources/{resource}', [ResourceController::class, 'show'])->name('resources.show');
    Route::get('/resources/{resource}/download', [ResourceController::class, 'download'])->name('resources.download');
    Route::post('/resources/{resource}/favori', [ResourceController::class, 'toggleFavorite'])->name('resources.favorite');
    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    // Notifications (centre + marquage)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/tout-lu', [NotificationController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('/{notification}/lu', [NotificationController::class, 'markRead'])->name('mark-read');
    });

    // Congés (Time Off)
    Route::prefix('time-off')->name('time-off.')->group(function () {
        Route::get('/', [TimeOffController::class, 'index'])->name('index');
        Route::get('/demande', [TimeOffController::class, 'create'])->name('create');
        Route::post('/', [TimeOffController::class, 'store'])->name('store');

        // Approbation (manager / super-admin)
        Route::get('/approbations', [TimeOffApprovalController::class, 'index'])->name('approvals');
        Route::put('/approbations/{timeOffRequest}', [TimeOffApprovalController::class, 'update'])->name('approvals.update');
    });

});
