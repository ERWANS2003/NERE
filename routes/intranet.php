<?php
/**
 * Routes INTRANET Néré Mining
 * Préfixe URL  : /intranet
 * Middleware   : auth (utilisateurs connectés uniquement)
 *
 * Chargé depuis bootstrap/app.php (withRouting → then).
 */

use App\Http\Controllers\Intranet\DepartmentController;
use App\Http\Controllers\Intranet\FormController;
use App\Http\Controllers\Intranet\HomeController;
use App\Http\Controllers\Intranet\SubmissionController;
use App\Http\Controllers\Intranet\AttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('intranet')->name('intranet.')->group(function () {

    // ── Accueil (grille de cartes) ────────────────────────────
    Route::get('/', HomeController::class)->name('home');

    // ── Espace département ────────────────────────────────────
    Route::get('/departements/{department}', [DepartmentController::class, 'show'])
        ->name('departments.show');

    // ── Formulaires ───────────────────────────────────────────
    Route::get('/formulaires/{form}', [FormController::class, 'show'])->name('forms.show');

    // ── Soumissions ───────────────────────────────────────────
    Route::get('/mes-demandes',                          [SubmissionController::class, 'mine'])
        ->name('submissions.mine');
    Route::post('/formulaires/{form}/soumettre',          [SubmissionController::class, 'store'])
        ->name('submissions.store');
    Route::get('/demandes/{submission}',                  [SubmissionController::class, 'show'])
        ->name('submissions.show');
    Route::patch('/demandes/{submission}/statut',         [SubmissionController::class, 'updateStatus'])
        ->name('submissions.update-status');
    Route::post('/demandes/{submission}/assigner',        [SubmissionController::class, 'assign'])
        ->name('submissions.assign');
    Route::post('/demandes/{submission}/commenter',       [SubmissionController::class, 'comment'])
        ->name('submissions.comment');
    Route::post('/demandes/{submission}/escalader',       [SubmissionController::class, 'escalate'])
        ->name('submissions.escalate');

    // ── Pièces jointes (téléchargement sécurisé) ──────────────
    Route::get('/pieces-jointes/{attachment}/download',   [AttachmentController::class, 'download'])
        ->name('attachments.download');

    // ── Administration (super admin uniquement) ────────────────
    Route::prefix('admin')->name('admin.')->group(function () {

        // Départements
        Route::get('/departements',                         [\App\Http\Controllers\Intranet\Admin\DepartmentAdminController::class, 'index'])->name('departments.index');
        Route::post('/departements',                        [\App\Http\Controllers\Intranet\Admin\DepartmentAdminController::class, 'store'])->name('departments.store');
        Route::patch('/departements/{department}',          [\App\Http\Controllers\Intranet\Admin\DepartmentAdminController::class, 'update'])->name('departments.update');
        Route::post('/departements/{department}/membres',   [\App\Http\Controllers\Intranet\Admin\DepartmentAdminController::class, 'assignUser'])->name('departments.assign-user');
        Route::delete('/departements/{department}/membres', [\App\Http\Controllers\Intranet\Admin\DepartmentAdminController::class, 'removeUser'])->name('departments.remove-user');

        // Constructeur de formulaires (admin + directeur du département)
        Route::get('/departements/{department}/formulaires',                        [\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'index'])->name('forms.index');
        Route::post('/departements/{department}/formulaires',                       [\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'store'])->name('forms.store');
        Route::get('/departements/{department}/formulaires/{form}/editer',          [\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'edit'])->name('forms.edit');
        Route::post('/departements/{department}/formulaires/{form}/champs',         [\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'addField'])->name('forms.add-field');
        Route::delete('/departements/{department}/formulaires/{form}/champs/{field}',[\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'deleteField'])->name('forms.delete-field');
        Route::post('/departements/{department}/formulaires/{form}/reordonner',     [\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'reorderFields'])->name('forms.reorder');
        Route::patch('/departements/{department}/formulaires/{form}/publier',       [\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'publish'])->name('forms.publish');
        Route::patch('/departements/{department}/formulaires/{form}/archiver',      [\App\Http\Controllers\Intranet\Admin\FormBuilderController::class, 'archive'])->name('forms.archive');

        // Dashboard admin
        Route::get('/tableau-de-bord', [\App\Http\Controllers\Intranet\Admin\DashboardAdminController::class, 'index'])->name('dashboard');
    });
});
