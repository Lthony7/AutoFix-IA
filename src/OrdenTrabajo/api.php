<?php

use Illuminate\Support\Facades\Route;
use Src\OrdenTrabajo\Application\Controllers\OrdenTrabajoController;

Route::middleware(['auth:sanctum', 'role:administrador,recepcionista'])->group(function () {
    Route::apiResource('ordenes-trabajo', OrdenTrabajoController::class)->names([
        'index' => 'api.ordenes-trabajo.index',
        'store' => 'api.ordenes-trabajo.store',
        'show' => 'api.ordenes-trabajo.show',
        'update' => 'api.ordenes-trabajo.update',
        'destroy' => 'api.ordenes-trabajo.destroy',
    ]);

    Route::put('ordenes-trabajo/{id}/asignar-mecanico', [OrdenTrabajoController::class, 'asignarMecanico'])
        ->name('api.ordenes-trabajo.asignar-mecanico');
    Route::put('ordenes-trabajo/{id}/cambiar-estado', [OrdenTrabajoController::class, 'cambiarEstado'])
        ->name('api.ordenes-trabajo.cambiar-estado');
});
