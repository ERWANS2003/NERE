<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// API interne consommée par le frontend (fetch/axios) ou par des intégrations externes (ERP, SCADA read-only, etc.)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tickets', TicketController::class)->except(['create', 'edit']);
});
