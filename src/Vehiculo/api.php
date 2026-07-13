<?php

use Illuminate\Support\Facades\Route;
use Src\Vehiculo\Application\Controllers\VehiculoController;

Route::middleware(['auth:sanctum', 'role:administrador,recepcionista'])->group(function () {
    Route::apiResource('vehiculos', VehiculoController::class)->names([
        'index' => 'api.vehiculos.index',
        'store' => 'api.vehiculos.store',
        'show' => 'api.vehiculos.show',
        'update' => 'api.vehiculos.update',
        'destroy' => 'api.vehiculos.destroy',
    ]);
});
