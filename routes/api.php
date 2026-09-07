<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController as ApiTicketController;
use App\Models\Departement;
use App\Models\Site;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'user']);
    Route::get('notifications', [AuthController::class, 'notifications']);
    Route::patch('notifications/{notification}/read', [AuthController::class, 'markNotificationRead']);

    Route::get('catalog', fn() => response()->json([
        'departements' => Departement::where('actif', true)->orderBy('nom')->get(),
        'sites' => Site::orderBy('nom')->get(),
        'categories' => TicketCategory::where('actif', true)->orderBy('nom')->get(),
        'priorites' => TicketPriority::orderByDesc('niveau')->get(),
        'statuts' => TicketStatus::orderBy('ordre')->get(),
    ]));

    Route::apiResource('tickets', ApiTicketController::class)
        ->except(['create', 'edit'])
        ->names('api.tickets');
    Route::post('tickets/{ticket}/comments', [ApiTicketController::class, 'comment']);
    Route::post('tickets/{ticket}/attachments', [ApiTicketController::class, 'attachment']);
    Route::post('tickets/{ticket}/transition', [ApiTicketController::class, 'transition']);
});
