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
});
