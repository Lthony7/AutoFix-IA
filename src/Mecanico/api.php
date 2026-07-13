<?php

use Illuminate\Support\Facades\Route;
use Src\Mecanico\Application\Controllers\MecanicoController;

Route::middleware(['auth:sanctum', 'role:administrador'])->group(function () {
    Route::apiResource('mecanicos', MecanicoController::class)->names([
        'index' => 'api.mecanicos.index',
        'store' => 'api.mecanicos.store',
        'show' => 'api.mecanicos.show',
        'update' => 'api.mecanicos.update',
        'destroy' => 'api.mecanicos.destroy',
    ]);
});
