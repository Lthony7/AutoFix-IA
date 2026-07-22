<?php

use Illuminate\Support\Facades\Route;
use Src\Servicio\Application\Controllers\ServicioController;

Route::middleware(['auth:sanctum', 'role:administrador,recepcionista'])->group(function () {
    Route::apiResource('servicios', ServicioController::class)->names([
        'index' => 'api.servicios.index',
        'store' => 'api.servicios.store',
        'show' => 'api.servicios.show',
        'update' => 'api.servicios.update',
        'destroy' => 'api.servicios.destroy',
    ]);
});
