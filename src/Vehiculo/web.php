<?php

use Illuminate\Support\Facades\Route;
use Src\Vehiculo\Application\Controllers\VehiculoWebController;

Route::middleware(['auth', 'role:administrador,recepcionista'])->group(function () {
    Route::resource('vehiculos', VehiculoWebController::class)->except(['show']);
});
