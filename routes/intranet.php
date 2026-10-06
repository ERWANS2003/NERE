<?php

use App\Http\Controllers\Intranet\DashboardController;
use App\Http\Controllers\Intranet\DepartmentController as IntranetDepartmentController;
use App\Http\Controllers\Intranet\FormController;
use App\Http\Controllers\Intranet\SubmissionController;
use Illuminate\Support\Facades\Route;

/*
 * Routes pour le système intranet
 * Toutes les routes sont protégées par auth et active
 */

Route::middleware(['auth', 'active'])->prefix('intranet')->name('intranet.')->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Gestion des départements
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/', [IntranetDepartmentController::class, 'index'])->name('index');
        Route::get('/create', [IntranetDepartmentController::class, 'create'])->name('create');
        Route::post('/', [IntranetDepartmentController::class, 'store'])->name('store');
        Route::get('/{department}', [IntranetDepartmentController::class, 'show'])->name('show');
        Route::get('/{department}/edit', [IntranetDepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [IntranetDepartmentController::class, 'update'])->name('update');
        Route::delete('/{department}', [IntranetDepartmentController::class, 'destroy'])->name('destroy');
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
    
    // Gestion des soumissions de formulaires
    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [SubmissionController::class, 'index'])->name('index');
        Route::post('/forms/{form}', [SubmissionController::class, 'store'])->name('store');
        Route::get('/{submission}', [SubmissionController::class, 'show'])->name('show');
        Route::put('/{submission}', [SubmissionController::class, 'update'])->name('update');
        Route::delete('/{submission}', [SubmissionController::class, 'destroy'])->name('destroy');
    });
});