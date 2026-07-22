<?php

use Illuminate\Support\Facades\Route;
use Src\Pago\Application\Controllers\PagoController;

Route::middleware(['auth:sanctum', 'role:administrador,recepcionista'])->group(function () {
    Route::apiResource('pagos', PagoController::class)->names([
        'index' => 'api.pagos.index',
        'store' => 'api.pagos.store',
        'show' => 'api.pagos.show',
        'update' => 'api.pagos.update',
        'destroy' => 'api.pagos.destroy',
    ]);
});
